# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

The Stingle API server — the PHP backend for StinglePhotos (end-to-end encrypted photo storage). It is built on the **Stingle PHP Framework** (`alexamiryan/stingle`, pulled in via Composer to `vendor/alexamiryan/stingle/`), a custom MVC, package-based framework. Most "magic" (routing, the `Reg` service locator, `ConfigManager`, package loading, `DbAccessor`/`QueryBuilder`) lives in the framework, not in this repo. When behavior is unexplained here, read the framework source under `vendor/alexamiryan/stingle/`.

The app stores encrypted blobs in S3-compatible storage (Wasabi by default) and metadata in MySQL. The server is zero-knowledge: it handles key bundles and encrypted params but never sees plaintext.

The framework is also authored by the project owner — treat `vendor/alexamiryan/stingle/` as first-party code you can read and reason about, not an opaque third-party library. Its core lives in `vendor/alexamiryan/stingle/core/` and `init/init.php`. See **Framework internals** below.

## Running & tooling

Everything runs inside Docker. There is **no local PHP/test toolchain** and **no unit test suite** — don't look for PHPUnit.

```bash
./setup.sh                 # first-time: installs Docker, builds image, runs interactive setup
./bin/setup-internal.sh    # re-run setup partially (see flags below); runs bin/setup.php inside container
./bin/dockerShell.sh       # bash shell inside the web container
./bin/composer.sh <args>   # run composer inside the container (e.g. ./bin/composer.sh update)
./bin/dockerRestartWeb.sh  # restart just the web container
./bin/updateStinglePHP-FW.sh  # update only the alexamiryan/stingle framework dependency
```

`bin/*.sh` wrappers `source .env` for `$CONTAINER_NAME` and shell into `docker compose -p $CONTAINER_NAME exec web ...`. The project root is bind-mounted to `/var/www/html`, so code edits are live without rebuilding.

`setup-internal.sh` flags: `--full`, `--mysql`, `--mysqlPass`, `--systemKeys`, `--storage`, `--backup`, `--hostname`, `--backup-cron`, `--update-addons-cron`. Services: `web` (Apache+PHP), `mysql-server` (MySQL 8), `memcached`, `phpmyadmin` (exposed on `127.0.0.1:8082`).

CLI/cron entry point is `cgi.php` (refuses to run if `$_SERVER['REMOTE_ADDR']` is set, i.e. web-only-blocked). It maps `key=value` argv into `$_GET` and includes `index.php`. Cron controllers live under `controllers/default/v2/cron/` and `exit` unless `IS_CGI` is defined.

## Request lifecycle

1. `.htaccess` rewrites every non-file request to `index.php`.
2. `index.php` scans `addons/` into `ADDONS_PATHS`, loads `configs/config.inc.php` (+ each addon's `configs/config.inc.php`), then hands off to `vendor/alexamiryan/stingle/init/init.php`.
3. The framework's `SiteNavigation`/`APIVersioning` packages parse the URL into navigation levels and dispatch to a controller **file** (not a class).

### Routing → controller files

URL path segments map directly to PHP files under `controllers/<host>/<version>/<section>/<action>.php`. `host` is `default`; version defaults to `v2` (`currentApiVersion = 2`, `replaceWithVersionIfAbsent = 2` in `config.system.inc.php`). So `POST /login/login` runs [controllers/default/v2/login/login.php](controllers/default/v2/login/login.php). A `common.php` in a section dir runs as shared setup for that section (e.g. [controllers/default/v2/common.php](controllers/default/v2/common.php) loads the `SPSync_v2` plugin for all requests).

Controllers are **procedural scripts**, not classes. They read `$_POST`/`$_GET`, call managers via `Reg::get(...)`, set output via `Reg::get('ao')->set(...)`, and signal errors via `Reg::get('error')->add(...)` + `Reg::get('ao')->setStatusNotOk()`. Output is JSON (`ApiOutput` package), shaped `{status: "ok"|"nok", parts: {...}}`.

### Key conventions inside controllers

- `isLogined()` — gate an endpoint behind a valid session token; outputs `logout:1` + exits on failure. Defined in [incs/helpers/functions.inc.php](incs/helpers/functions.inc.php).
- `getApiRequestSecureParams()` — decrypt the E2E-encrypted `params` blob using the user's key bundle (`SPKeyManager`).
- `recordRequest('<limitName>')` — increments the `RequestLimiter` counter; limits configured in [configs/config.site.inc.php](configs/config.site.inc.php) (`Security.RequestLimiter.limits`).
- `C('text')` — i18n/translation wrapper (framework `Language` package).
- `Reg::get('ao')` is the API output object; `Reg::get('usr')` the authenticated user; `Reg::get('spsync')`, `Reg::get('spkeys')`, `Reg::get('userMgr')`, etc. are package managers.

## Packages (business logic)

Logic lives in **packages**, not controllers. A *package* (Module) contains one or more *plugins*. Enabled ones are listed in [configs/config.packages.inc.php](configs/config.packages.inc.php) as `$CONFIG['Packages'][] = array("Module", "Plugin1;Plugin2")`. Plugins resolve from **three roots, in this override order** (first match wins, so site/addon code can shadow a framework plugin of the same name):

1. Addons: `addons/<name>/packages/<Module>/<Plugin>/`
2. Site packages: `incs/packages/<Module>/<Plugin>/` (`SITE_PACKAGES_PATH`) — project-specific, e.g. `StinglePhotos/SPSync_v2`, `Users/SiteUser`.
3. Framework packages: `vendor/alexamiryan/stingle/packages/<Module>/<Plugin>/` (`STINGLE_PATH`) — reusable, e.g. `Db`, `Security`, `SiteNavigation`, `File/S3Transport`.

The framework ships many reusable plugins beyond what this app enables — before building new infra, check `vendor/alexamiryan/stingle/packages/`: `Db` (Db, QueryBuilder, Memcache, Migrations, Mongo), `Crypto` (AES256, GPG), `Security` (Security, RequestLimiter, OneTimeCodes, IpFilter, FormKey), `Users` (Users, UserSessions, GoogleAuth, Yubikey, WebPushNotifications, …), `File` (S3Transport, FileUploader), plus `JobQueue`, `Links`, `Mail`, `Messaging`, `Notifications`, `Logger`, `Pager`, `Language`, `GeoIP`, `Image`, `Chat`, `Comet`, etc.

The core domain manager is [SPSyncManager](incs/packages/StinglePhotos/SPSync_v2/Managers/SPSyncManager.class.php) (gallery/trash/album/contact sync, file storage layout in S3, the `SP`-prefixed encrypted file format). Managers extend `DbAccessor` and use `QueryBuilder`/`Tbl`/`Field` for SQL.

## Framework internals (vendor/alexamiryan/stingle)

Bootstrap is `init/init.php`. Read it first when something feels like "magic." The key subsystems:

**Service locator — `Reg`** ([core/Reg.class.php](vendor/alexamiryan/stingle/core/Reg.class.php)): a static global registry. `Reg::register('name', $obj, $override)` / `Reg::get('name')` / `Reg::isRegistered('name')`. Almost every cross-cutting object is reached via `Reg::get(...)` — `sql` (DB query), `usr`, `userMgr`, `userSess`, `userAuth`, `spsync`, `spkeys`, `ao` (API output), `error`, `packageMgr`, `keybase`, etc. The string names are not arbitrary: each plugin's `DefaultConfig.inc.php` declares an `Objects` map (e.g. Db maps object `Query` → registry name `sql`).

**Plugin contract** — a plugin folder `<Module>/<Plugin>/` contains:
- `Loader<Plugin>.class.php` extends `Loader`. Override `includes()` to `stingleInclude()` its class files; `customInitBeforeObjects()`/`customInitAfterObjects()` for setup (e.g. `Tbl::registerTableNames(...)`); and one `load<ObjectName>()` per declared object that builds the object and calls `$this->register($obj)` (registry name taken from the config `Objects` map). See [LoaderSPSync_v2](incs/packages/StinglePhotos/SPSync_v2/LoaderSPSync_v2.class.php) for the canonical example.
- `Dependency<Plugin>.class.php` extends `Dependency` — declares dependent packages/plugins (`addPlugin(...)`), resolved recursively before load.
- `DefaultConfig.inc.php` — a `$defaultConfig` array with `AuxConfig` (settings, overridable from site config), `Objects` (object→registry-name map), and `Hooks` (hook-name→method map).

**Dependency resolution & conflict tables** ([core/PackageManager.class.php](vendor/alexamiryan/stingle/core/PackageManager.class.php)): builds the full dependency graph, detects loops, topologically orders by priority, then builds *allowance tables* for objects and hooks. Two plugins at the same priority declaring the same object/hook name → a `RuntimeException` conflict at boot. A higher-priority (more depended-upon, or site/addon-overriding) plugin wins. This is why you can't blindly register an already-registered object name without passing the override flag.

**Hook lifecycle** ([core/HookManager.class.php](vendor/alexamiryan/stingle/core/HookManager.class.php)): `init.php` fires these in order — `BeforePackagesLoad` → *(load all packages)* → `AfterPackagesLoad` → `BeforeRequestParser` → `RequestParser` → `BeforeController` → `Controller` → `AfterController` → `BeforeOutput` → `Output` → `AfterOutput`. Plugins hook in by defining `hook<Method>()` methods + a `Hooks` config entry; the app registers its own hooks in [configs/config.system.inc.php](configs/config.system.inc.php) `$CONFIG['Hooks']` (e.g. exception handling → [incs/helpers/hooks.inc.php](incs/helpers/hooks.inc.php)). The actual routing/dispatch is the framework's response to the `RequestParser`/`Controller` hooks.

**Controller dispatch** ([SiteNavigation/.../Controller.class.php](vendor/alexamiryan/stingle/packages/SiteNavigation/SiteNavigation/Managers/Controller.class.php) `exec()`): walks the parsed nav levels under `controllers/<host>/`, and at **each** level includes `config.php`, `helpers.php`, and `common.php` if present (so those cascade down the path), then includes the final `<lastLevel>.php` action file (or `actions/<action>.php` if an `action` param is set). Addon controller dirs are searched before the site's.

**BootCompiler / caching** (`init.php`, controlled by `Stingle->BootCompiler`): when enabled, the merged global config and a single concatenated `classes.php` of all plugin class files are cached — in **APCu** if available, otherwise serialized files under `Stingle->CoreCachePath` (the `cache/` dir). Object/hook *allowance tables* are cached the same way (`AllowanceTablesCache`). **Consequence for development:** after editing plugin code or config you may be served stale cached classes/config — clear APCu (restart PHP/Apache: `./bin/dockerRestartWeb.sh`) and/or the `cache/` files. `index.php` has a commented `define('DISABLE_APCU', true)` to bypass APCu while debugging.

## Addons

Drop a self-contained module into `addons/<name>/` and the framework auto-discovers it — no core changes. An addon mirrors the project layout (`configs/`, `controllers/`, `packages/`, `incs/`, `view/`, `bin/`, `crontab/`, `init.inc.php`, `composer.json`). Its `configs/config.inc.php` is loaded after the main config, and its `config.packages.inc.php` appends more `$CONFIG['Packages']` (e.g. `addon-api-stingle-org` adds `Billing` with Stripe/Google billing and `StingleSiteUser`).

Composer uses `wikimedia/composer-merge-plugin` to merge every `addons/*/composer.json` into the root install — after adding/updating an addon, run `./bin/composer.sh update`.

## Configuration

`configs/config.inc.php` includes, in order: `config.db.inc.php`, `config.debug.inc.php`, `config.packages.inc.php`, `config.site.inc.php`, `config.system.inc.php`, then `configsSite/config.override.inc.php` **if it exists**. The override file is generated by the setup script, holds all secrets/site-specific values, and is gitignored — **never commit it; back it up separately.** Tracked config files contain placeholders (`***REMOVED***`) and safe defaults; real keys/credentials go only in the override.

`config.system.inc.php` wires framework essentials: `Hooks` (exception handling → DB log / email / Keybase via [incs/helpers/hooks.inc.php](incs/helpers/hooks.inc.php)), API versioning, and cookie/session disabling (the API is stateless — auth is a `token` POST param, not cookies).

## Conventions & gotchas

- PHP 7.3 target (`composer.json` platform pin). Requires `ext-sodium`, `ext-openssl`, `ext-curl`.
- No automated tests; verify changes by hitting endpoints against a running container.
- The framework is a private dependency (`git@github.com:alexamiryan/stingle.git` VCS repo) — Composer needs SSH access to that repo.
- `composer.lock` is a **symlink** → `composer/composer.lock` (see commit "Moved composer.lock to separate dir"). On Windows checkouts with `core.symlinks=false`, git can't represent it and reports it as modified/deleted with `Function not implemented` — this is a checkout artifact, not a real change. The real file is intact under `composer/`. **Do not `git add` composer.lock on Windows.** The addon ships its own `vendor/`.
- Error responses must stay shaped `{status:"nok"}` — exception handlers in `hooks.inc.php` enforce this and log via `DBLogger`/Keybase rather than leaking stack traces.

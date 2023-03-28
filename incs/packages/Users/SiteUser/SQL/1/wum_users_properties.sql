ALTER TABLE `wum_users_properties`
    ADD `pwsalt` varchar(256) DEFAULT NULL,
    ADD `base_quota` int(10) UNSIGNED NOT NULL DEFAULT '0',
    ADD `space_used` int(10) UNSIGNED NOT NULL DEFAULT '0',
    ADD `space_quota` int(10) UNSIGNED NOT NULL DEFAULT '0';

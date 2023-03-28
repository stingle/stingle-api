FROM php:8.0-apache-buster
WORKDIR /var/www/html/
RUN a2enmod rewrite
RUN a2enmod ssl
RUN apt-get update
RUN apt-get upgrade -y
RUN apt-get install -y vim unzip certbot python-certbot-apache libz-dev libmemcached-dev mariadb-client cron && \
    pecl install memcache && \
    docker-php-ext-enable memcache
RUN apt-get install -y libcurl4-openssl-dev pkg-config libssl-dev
RUN pecl config-set php_ini /etc/php.ini
RUN docker-php-ext-install mysqli

COPY ./docker/ssl/ssl.crt /etc/apache2/ssl/ssl.crt
COPY ./docker/ssl/ssl.key /etc/apache2/ssl/ssl.key
RUN mkdir -p /var/run/apache2/
COPY ./docker/config/000-default.conf /etc/apache2/sites-enabled/000-default.conf
COPY ./docker/config/php.ini "$PHP_INI_DIR/php.ini"
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Cron
COPY ./docker/cron/crontab /etc/cron.d/stingle-crontab
RUN chmod 0644 /etc/cron.d/stingle-crontab && crontab /etc/cron.d/stingle-crontab

RUN ln -s /usr/local/bin/php /usr/bin/php

RUN pecl install apcu && docker-php-ext-enable apcu

COPY . /var/www/html
RUN /bin/bash -c 'chmod -R 777 /var/www/html/cache'

EXPOSE 80
EXPOSE 443
CMD ["apache2-foreground"]

#
FROM php:7.4-fpm-alpine

COPY ./config/php.ini /usr/local/etc/php/php.ini
COPY ./config/php-fpm.d/* /usr/local/etc/php-fpm.d/

RUN apk add wget curl ca-certificates git zip unzip libzip-dev

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod uga+x /usr/local/bin/install-php-extensions && sync

RUN install-php-extensions zip opcache intl pdo_mysql
RUN install-php-extensions imagick gd

RUN php -r "copy('https://raw.githubusercontent.com/composer/getcomposer.org/master/web/installer', 'composer-setup.php');" && \
    php composer-setup.php && \
    php -r "unlink('composer-setup.php');" && \
    mv composer.phar /usr/bin/composer && \
    chmod 0755 /usr/bin/composer

WORKDIR /var/www/html

CMD chmod go+w -R /var/www/html/var/cache && php-fpm

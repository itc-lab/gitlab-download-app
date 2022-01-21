FROM php:8.1.1-fpm-alpine3.15
WORKDIR /opt/gitlab-download-app
RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini
RUN mkdir cache && chmod 1777 cache && chown -R www-data .
RUN apk add --no-cache zip diffutils
RUN docker-php-ext-install pcntl
CMD crond && docker-php-entrypoint php-fpm

FROM php:8-fpm-alpine

ENV PHPGROUP=facile
ENV PHPUSER=facile

RUN adduser -g ${PHPGROUP} -s /bin/sh -D ${PHPUSER}

RUN sed -i "s/user = www-data/user = ${PHPUSER}/g" /usr/local/etc/php-fpm.d/www.conf
RUN sed -i "s/group = www-data/group = ${PHPGROUP}/g" /usr/local/etc/php-fpm.d/www.conf

RUN mkdir -p /var/www/html/public

RUN docker-php-ext-install pdo pdo_mysql opcache
ADD opcache.ini /usr/local/etc/php/conf.d/opcache.ini

RUN apk upgrade --update
RUN apk add --no-cache freetype libpng libjpeg-turbo freetype-dev libpng-dev libjpeg-turbo-dev
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install gd
RUN docker-php-ext-enable gd
RUN apk del --no-cache freetype-dev libpng-dev libjpeg-turbo-dev
RUN rm -rf /tmp/*

CMD ["php-fpm", "-y", "/usr/local/etc/php-fpm.conf", "-R"]

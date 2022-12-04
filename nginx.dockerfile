FROM nginx:1.22.1-alpine

ENV NGINXUSER=moon
ENV NGINXGROUP=moon

RUN mkdir -p /var/www/html/public
RUN mkdir -p /var/www/html/storage
RUN mkdir -p /var/www/html/bootstrap/cache

ADD default.conf /etc/nginx/conf.d/default.conf

RUN sed -i "s/user www-data/user ${NGINXUSER}/g" /etc/nginx/nginx.conf

RUN adduser -g ${NGINXGROUP} -s /bin/sh -D ${NGINXUSER}

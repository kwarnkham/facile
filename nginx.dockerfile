FROM nginx:1.22.1-alpine

ENV NGINXUSER=root
ENV NGINXGROUP=root

RUN mkdir -p /var/www/html/public

ADD default.conf /etc/nginx/conf.d/default.conf

RUN sed -i "s/user www-data/user ${NGINXUSER}/g" /etc/nginx/nginx.conf

RUN adduser -g ${NGINXGROUP} -s /bin/sh -D ${NGINXUSER}

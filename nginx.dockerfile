FROM nginx:1.22.1-alpine

ENV NGINXUSER=facile
ENV NGINXGROUP=facile

RUN mkdir -p /var/www/html/public

ADD default.conf /etc/nginx/conf.d/default.conf

RUN sed -i "s/user www-data/user ${NGINXUSER}/g" /etc/nginx/nginx.conf

RUN chown -R ${NGINXUSER}.${NGINXGROUP} storage

RUN chown -R ${NGINXUSER}.${NGINXGROUP} bootstrap/cache

RUN adduser -g ${NGINXGROUP} -s /bin/sh -D ${NGINXUSER}

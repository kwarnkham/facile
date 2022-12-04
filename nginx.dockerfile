FROM nginx:1.22.1-alpine

ENV NGINXUSER=facile
ENV NGINXGROUP=facile

RUN mkdir -p /var/www/html/public

ADD default.conf /etc/nginx/conf.d/default.conf

RUN sed -i "s/user www-data/user ${NGINXUSER}/g" /etc/nginx/nginx.conf

RUN adduser -g ${NGINXGROUP} -s /bin/sh -D ${NGINXUSER}

RUN chown -R ${NGINXUSER}.${NGINXGROUP} /var/www/html/facile/storage
RUN chown -R ${NGINXUSER}.${NGINXGROUP} /var/www/html/facile/bootstrap/cache

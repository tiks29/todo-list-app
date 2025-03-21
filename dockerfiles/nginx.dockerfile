FROM nginx:1.27.4

COPY ./nginx/nginx.conf /etc/nginx/conf.d/default.conf
COPY ./app /var/www/html

WORKDIR /var/www/html
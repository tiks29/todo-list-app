FROM composer:latest

COPY ./app /var/www/html

WORKDIR /var/www/html

ENTRYPOINT [ "composer", "--ignore-platform-reqs" ]
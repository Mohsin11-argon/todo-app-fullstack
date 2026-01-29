FROM php:8.2-apache
RUN docker-php-ext-install pdo pdo_mysql mysqli


WORKDIR /var/www/html


COPY . .


RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

EXPOSE 80

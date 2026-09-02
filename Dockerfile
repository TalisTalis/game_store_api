FROM php:8.3-apache
RUN docker-php-ext-install pdo pdo_pgsql
RUN a2enmod rewrite
COPY . /var/www/html
WORKDIR /var/www/html
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install
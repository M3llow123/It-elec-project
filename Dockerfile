FROM php:8.1-apache

# Enable mysqli and rewrite module
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli \
    && a2enmod rewrite

# Copy app code
COPY . /var/www/html

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html

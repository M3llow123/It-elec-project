# Use PHP 8.2 with Apache
FROM php:8.2-apache

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install MySQLi extension for database connection
RUN docker-php-ext-install mysqli

# Copy app files to Apache server's public directory
COPY ./public /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

# Expose default HTTP port
EXPOSE 80

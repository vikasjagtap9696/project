FROM php:8.1-apache

# Install PostgreSQL client + headers (VERY IMPORTANT)
RUN apt-get update && apt-get install -y libpq-dev

# Install PostgreSQL extensions for PHP
RUN docker-php-ext-install pdo pdo_pgsql pgsql

# Enable Apache rewrite
RUN a2enmod rewrite

# Copy your project files
COPY . /var/www/html/

EXPOSE 80
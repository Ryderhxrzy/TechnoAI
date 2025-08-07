# Gamitin ang official PHP image na may Apache
FROM php:8.2-apache

# 1. I-copy ang lahat ng files ng project sa container
COPY . /var/www/html/

# 2. I-enable ang Apache rewrite module (para sa .htaccess)
RUN a2enmod rewrite

# 3. Expose ang port 80 (default ng Apache)
EXPOSE 80

# 4. (Optional) Install dependencies (kung gumagamit ka ng Composer)
# RUN apt-get update && apt-get install -y git unzip && \
#     curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
#     composer install
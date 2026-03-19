FROM php:8.3-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libonig-dev \
    && docker-php-ext-install pdo pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install Composer dependencies
RUN composer install

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Enable Apache modules
RUN a2enmod rewrite

# Configure Apache
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/000-default.conf \
    /etc/apache2/apache2.conf

EXPOSE 80

CMD ["apache2-foreground"]

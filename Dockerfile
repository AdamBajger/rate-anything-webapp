# Use official PHP image with Apache
FROM php:8.2-apache

# Install PHP YAML extension
RUN apt-get update && \
    apt-get install -y libyaml-dev && \
    pecl install yaml && \
    docker-php-ext-enable yaml && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]

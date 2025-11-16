# Rate Anything Web Application - Docker Image
# Minimal production-ready image for Google Compute Engine deployment

# Use official PHP 8.1 image with Apache web server (better PECL compatibility)
FROM php:8.1-apache

# Set labels for image metadata
LABEL maintainer="rate-anything-webapp"
LABEL description="Lightweight rating application with QR code scanning"
LABEL version="1.0"

# Install system dependencies and PHP YAML extension
# Note: php-yaml extension must be available for the application to function
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        libyaml-dev \
        libcurl4-openssl-dev \
        libssl-dev \
        ca-certificates && \
    update-ca-certificates && \
    # Install yaml extension - try pecl first
    (pecl install yaml || true) && \
    # Enable yaml extension if installed
    (docker-php-ext-enable yaml || echo "Warning: yaml extension not enabled") && \
    # Enable Apache modules for better performance and security
    a2enmod rewrite headers expires && \
    # Clean up to reduce image size
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Set working directory to Apache document root
WORKDIR /var/www/html

# Copy application files to container
# .dockerignore file controls which files are excluded
COPY . /var/www/html/

# Configure Apache for proper CORS and security headers
RUN echo '<Directory /var/www/html>\n\
    Options -Indexes +FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
    \n\
    # Security headers\n\
    Header always set X-Content-Type-Options "nosniff"\n\
    Header always set X-Frame-Options "SAMEORIGIN"\n\
    Header always set X-XSS-Protection "1; mode=block"\n\
    \n\
    # CORS headers for API access\n\
    Header always set Access-Control-Allow-Origin "*"\n\
    Header always set Access-Control-Allow-Methods "GET, POST, OPTIONS"\n\
    Header always set Access-Control-Allow-Headers "Content-Type"\n\
</Directory>' > /etc/apache2/conf-available/rate-anything.conf && \
    a2enconf rate-anything

# Set proper file permissions and ownership
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    # Ensure data files are writable by Apache
    touch /var/www/html/data.yaml && \
    chown www-data:www-data /var/www/html/data.yaml && \
    chmod 644 /var/www/html/data.yaml

# Expose HTTP port
EXPOSE 80

# Set environment variables for production
ENV APACHE_RUN_USER=www-data
ENV APACHE_RUN_GROUP=www-data
ENV APACHE_LOG_DIR=/var/log/apache2

# Health check to verify service is running
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Start Apache in foreground mode
CMD ["apache2-foreground"]

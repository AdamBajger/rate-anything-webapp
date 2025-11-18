FROM php:8.4-fpm-alpine

RUN set -eux; apk update

RUN set -eux; apk add --no-cache nginx yaml

RUN set -eux; apk add --no-cache --virtual .build-deps \
      $PHPIZE_DEPS \
      yaml-dev

RUN set -eux; pecl install yaml && docker-php-ext-enable yaml

RUN set -eux; apk del .build-deps

RUN set -eux; mkdir -p /run/nginx

# Ensure nginx has a minimal top-level configuration so conf.d/*.conf
# files containing `server {}` blocks are included inside the `http` context.
COPY nginx/nginx.conf /etc/nginx/nginx.conf
COPY nginx/fastcgi_params /etc/nginx/fastcgi_params

WORKDIR /var/www/html

# Copy application and nginx configs from the build context
COPY . /var/www/html/
COPY nginx/default.conf /etc/nginx/conf.d/default.conf

RUN adduser -S www-data; \
    chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["sh","-c","php-fpm -F & nginx -g 'daemon off;'"]

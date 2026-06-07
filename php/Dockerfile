FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    nginx \
    fcgi \
    openrc \
    supervisor \
    libzip-dev \
    oniguruma-dev \
    libxml2-dev \
    curl \
    dcron \
    bash \
    mysql-client \
    redis \
    git \
    libjpeg-turbo-dev \
    libpng-dev \
    freetype-dev

RUN docker-php-ext-install pdo pdo_mysql mysqli

RUN docker-php-ext-install zip mbstring

RUN docker-php-ext-install xml

RUN docker-php-ext-install gd

RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

COPY nginx.conf /etc/nginx/http.d/nginx.conf
COPY php.ini /usr/local/etc/php/conf.d/custom.ini
COPY supervisord.conf /etc/supervisord.conf

# Copy the Easy!Appointments application code into the image.
# In dev compose this is overridden by a bind mount, but for production
# the image must be self-contained.
COPY . /var/www/html/

RUN mkdir -p /var/www/html/storage \
    /var/www/html/logs \
    /run \
    /var/spool/cron

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/logs

RUN crontab -r || true

CMD ["supervisord", "-c", "/etc/supervisord.conf"]

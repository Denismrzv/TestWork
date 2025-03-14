FROM php:8.1-fpm

RUN pecl install xdebug-3.1.6 \
    && docker-php-ext-enable xdebug

CMD ["php-fpm"]
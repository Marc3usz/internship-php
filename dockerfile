FROM php:8.0-apache
# found this on stack overflow
RUN apt-get update \
  && apt-get install -y --no-install-recommends libpq-dev \
  && docker-php-ext-install mysqli pdo_pgsql pdo_mysql

RUN apt-get update && \
    apt-get upgrade -y && \
    apt-get install -y git

# Install Composer and phpmailer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer init --description internship
RUN composer require vlucas/phpdotenv
RUN composer require phpmailer/phpmailer
RUN composer install
FROM php:8.2-apache

WORKDIR /var/www/html

# Устанавливаем зависимости PHP
RUN apt-get update && apt-get install -y \
    unzip libpng-dev libjpeg-dev libfreetype6-dev libzip-dev git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

# Копируем исходники EspoCRM (твой форк)
COPY . .

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
CMD ["apache2-foreground"]

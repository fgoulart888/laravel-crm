FROM php:8.2-apache

# Pacotes de SO e libs p/ extensões PHP
RUN apt-get update && apt-get install -y \
    git unzip \
    libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    libicu-dev libxml2-dev libonig-dev libssl-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) pdo_mysql mbstring gd zip exif intl bcmath opcache calendar \
 && a2enmod rewrite \
 && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . /var/www/html

# Permissões Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Instala deps PHP ignorando IMAP (não precisamos agora)
RUN composer install --no-dev --prefer-dist --optimize-autoloader --ignore-platform-req=ext-imap

# VirtualHost aponta para /public
RUN sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# Entrypoint embutido
RUN bash -lc 'cat > /usr/local/bin/docker-entrypoint.sh << "EOF"\n\
#!/usr/bin/env bash\n\
set -e\n\
php artisan key:generate --force || true\n\
php artisan storage:link || true\n\
php artisan migrate --force\n\
php artisan db:seed --force || true\n\
exec \"$@\"\n\
EOF\n\
chmod +x /usr/local/bin/docker-entrypoint.sh'

ENV APACHE_RUN_USER=www-data
ENV APACHE_RUN_GROUP=www-data

EXPOSE 80
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]

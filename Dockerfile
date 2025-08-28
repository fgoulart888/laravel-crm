FROM php:8.2-apache

# Pacotes de SO necessários (nomes corretos para Debian Bookworm)
RUN apt-get update && apt-get install -y \
    git unzip \
    libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    libicu-dev libxml2-dev libonig-dev \
    libkrb5-dev libc-client2007e-dev libssl-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) pdo_mysql mbstring gd zip exif intl bcmath opcache calendar \
 # IMAP (com Kerberos + SSL). Em Debian use o prefixo /usr para OpenSSL.
 && docker-php-ext-configure imap --with-kerberos --with-imap-ssl=/usr \
 && docker-php-ext-install imap \
 && a2enmod rewrite \
 && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Código
WORKDIR /var/www/html
COPY . /var/www/html

# Permissões Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Dependências PHP
RUN composer install --no-dev --prefer-dist --optimize-autoloader

# VirtualHost aponta para /public
RUN sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# Entrypoint embutido (sem arquivo extra)
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

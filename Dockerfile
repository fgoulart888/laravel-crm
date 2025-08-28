FROM php:8.2-apache

# Pacotes necessários para extensões
RUN apt-get update && apt-get install -y \
    libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    libicu-dev libc-client-dev libkrb5-dev \
    git unzip libonig-dev libxml2-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) pdo_mysql mbstring gd zip exif intl bcmath opcache calendar \
 && docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
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

# Instala dependências sem dev e otimiza autoloader
RUN composer install --no-dev --prefer-dist --optimize-autoloader

# Deixa o Apache com DocumentRoot em /public e .htaccess ativo
RUN sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# Entrypoint: prepara app e sobe apache
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENV APACHE_RUN_USER=www-data
ENV APACHE_RUN_GROUP=www-data

EXPOSE 80
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]

FROM php:8.2-apache

# Pacotes de SO e libs p/ extensões PHP
RUN apt-get update && apt-get install -y \
    git unzip curl \
    libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    libicu-dev libxml2-dev libonig-dev libssl-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) pdo_mysql mbstring gd zip exif intl bcmath opcache calendar \
 && a2enmod rewrite \
 && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ↓↓↓ PULL DA RELEASE ESTÁVEL v2.1.2 DO KRAYIN ↓↓↓
RUN rm -rf /var/www/html/* && \
    git clone -b v2.1.2 --depth 1 https://github.com/krayin/laravel-crm.git /var/www/html

# Instala dependências do Laravel (mesma flag do seu 2.1: ignora IMAP)
RUN composer install --no-dev --prefer-dist --optimize-autoloader --ignore-platform-req=ext-imap

# VirtualHost aponta para /public (igual ao seu 2.1)
RUN sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# Permissões Laravel (igual ao seu 2.1)
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
    find storage -type d -exec chmod 775 {} \; && \
    find storage -type f -exec chmod 664 {} \; && \
    chmod -R 775 bootstrap/cache

# Copia SUAS customizações por cima (logo, traduções, etc.)
# -> coloque seus arquivos na pasta overrides/ com a mesma estrutura do app
COPY overrides/ /var/www/html/

# Entrypoint (prepara .env, links, caches, migrate, seed)
COPY docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENV APACHE_RUN_USER=www-data
ENV APACHE_RUN_GROUP=www-data

EXPOSE 80
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]

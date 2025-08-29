FROM php:8.2-apache

# Pacotes de SO e libs p/ extensões PHP
RUN apt-get update && apt-get install -y \
    git unzip curl \
    libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    libicu-dev libxml2-dev libonig-dev libssl-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) pdo_mysql mbstring gd zip exif intl bcmath opcache calendar \
 && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# --- versão do Krayin vinda de arquivo ---
COPY KRAYIN_REF /tmp/KRAYIN_REF

# ↓↓↓ Clone da release do Krayin baseada no arquivo de versão ↓↓↓
RUN rm -rf /var/www/html/* && \
    KRAYIN_REF="$(cat /tmp/KRAYIN_REF)" && \
    echo "Building with KRAYIN_REF=$KRAYIN_REF" && \
    git clone -b "$KRAYIN_REF" --depth 1 https://github.com/krayin/laravel-crm.git /var/www/html

# Instala dependências do Laravel
RUN composer install --no-dev --prefer-dist --optimize-autoloader --ignore-platform-req=ext-imap

# Apache: DocumentRoot aponta para /public
RUN sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# Habilita módulos necessários (rewrite + headers p/ ler X-Forwarded-Proto)
RUN a2enmod rewrite headers

# Marca HTTPS=on quando o proxy enviar X-Forwarded-Proto=https
COPY docker/apache-forwarded-https.conf /etc/apache2/conf-available/forwarded-https.conf
RUN a2enconf forwarded-https

# Permissões Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
    find storage -type d -exec chmod 775 {} \; && \
    find storage -type f -exec chmod 664 {} \; && \
    chmod -R 775 bootstrap/cache

# Copia SUAS customizações (logo, traduções) por cima do core
COPY overrides/ /var/www/html/

# Entrypoint (prepara .env, links, caches, migrate, seed)
COPY docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENV APACHE_RUN_USER=www-data
ENV APACHE_RUN_GROUP=www-data

EXPOSE 80
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]

FROM dunglas/frankenphp:1.3-php8.3

# Définir le répertoire de travail
WORKDIR /app

# Copier le code source dans le conteneur
COPY . .

# Install required system packages
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        locales apt-utils git libicu-dev g++ libpng-dev libxml2-dev libzip-dev libonig-dev libxslt-dev unzip libpq-dev nodejs npm wget \
        apt-transport-https lsb-release ca-certificates \
    && rm -rf /var/lib/apt/lists/* # Clean up apt cache to reduce image size

# Set locales
RUN echo "en_US.UTF-8 UTF-8" > /etc/locale.gen \
    && echo "fr_FR.UTF-8 UTF-8" >> /etc/locale.gen \
    && locale-gen

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    && mv composer.phar /usr/local/bin/composer

# Install Symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

# Install PHP extensions
RUN docker-php-ext-configure intl \
    && docker-php-ext-install \
        pdo pdo_mysql pdo_pgsql opcache intl zip calendar dom mbstring gd xsl

# Exposer le port 80
EXPOSE 80

# Démarrer FrankenPHP
CMD ["frankenphp", "php-server", "-r", "/app/public"]


FROM php:8.4-apache

# Mettre à jour apt et installer les paquets nécessaires
RUN apt-get update && apt-get install -y \
    gcc \
    curl \
    libcurl4 \
    procps \
    zip \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Installer les extensions PHP
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Installer GD et Xdebug via le script officiel
RUN (curl -sSLf https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions -o - || echo 'return 1') | sh -s gd xdebug

EXPOSE 80

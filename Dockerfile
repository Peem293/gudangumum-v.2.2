FROM php:8.4-fpm

# Menggunakan paket manajemen Debian yang sangat stabil terhadap isu DNS
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    libicu-dev \
    autoconf \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    zlib1g-dev \
    curl \
    openssl \
    && rm -rf /var/lib/apt/lists/*

# Download dan ekstrak source Redis secara manual
RUN mkdir -p /usr/src/php/extensions \
    && curl -k -fsSL https://pecl.php.net/get/redis -o redis.tgz \
    && mkdir -p /usr/src/php/ext/redis \
    && tar -xf redis.tgz -C /usr/src/php/ext/redis --strip-components=1 \
    && rm redis.tgz

# Kompilasi dan aktifkan semua ekstensi Laravel + Filament sekaligus
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure intl \
    && docker-php-ext-install pdo_pgsql intl gd zip bcmath pcntl sockets redis

# Jalur OpenSSL bawaan Debian (otomatis terdeteksi oleh sistem)
ENV OPENSSL_CONF=/etc/ssl/openssl.cnf

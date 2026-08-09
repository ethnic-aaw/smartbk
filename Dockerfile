FROM php:8.2-apache

# Dependencies untuk ekstensi gd (jpeg/png/webp) + zip
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libzip-dev \
        zip \
        unzip \
    # gd dengan dukungan webp (dipakai resize foto di includes/upload.php)
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    # mysqli untuk DB; fileinfo & mbstring sudah built-in di image ini
    && docker-php-ext-install -j"$(nproc)" gd mysqli \
    # mod_rewrite + mod_headers dibutuhkan api/.htaccess (CORS)
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Vhost Apache: AllowOverride All agar .htaccess berfungsi
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf

# Ganti port listen bawaan (80) menjadi PORT_APP agar tidak bentrok di host
COPY docker/ports.conf /etc/apache2/ports.conf

# Konfigurasi PHP production (memory, upload, opcache, timezone)
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-smartbk.ini

# Salin source aplikasi
COPY . /var/www/html

# Siapkan folder upload & beri izin tulis untuk www-data
RUN mkdir -p /var/www/html/assets/uploads/foto_siswa \
             /var/www/html/assets/uploads/lampiran_konsultasi \
             /var/www/html/assets/uploads/kop \
    && chown -R www-data:www-data /var/www/html/assets/uploads

EXPOSE 9000
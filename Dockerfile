# Dockerfile
FROM php:8.4-fpm

# 1. Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libcurl4-openssl-dev \
    libssl-dev

# 2. Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# 3. Install PHP extensions (Standard Laravel)
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# 4. Install & Enable MongoDB and Redis via PECL
RUN pecl install mongodb redis \
    && docker-php-ext-enable mongodb redis

# 5. Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 6. Set working directory
WORKDIR /var/www

# 7. Add user for laravel application (optional but recommended for permissions)
RUN groupadd -g 1000 www \
    && useradd -u 1000 -ms /bin/bash -g www www

# 8. Copy existing application directory contents
COPY . /var/www

# 9. Copy existing application directory permissions
COPY --chown=www:www . /var/www

RUN mkdir -p /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# 10. Change current user to www
USER www

# 11. Expose port 29000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]

FROM php:8.2-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    zip \
    unzip \
    git \
    curl \
    libonig-dev \
    libxml2-dev \
    libpq-dev

# Install Node.js for Vite/frontend builds
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions (intl + xml family needed by PhpSpreadsheet / Flysystem; zip for Composer dist installs).
# Do not use make -j here: xmlreader needs dom headers built first; parallel jobs can race and fail (dom_ce.h missing).
RUN docker-php-ext-install \
    pdo_mysql pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip intl \
    dom xml simplexml xmlreader xmlwriter

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Update Apache DocumentRoot to point to Laravel's public directory
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Set working directory
WORKDIR /var/www/html

ENV COMPOSER_ALLOW_SUPERUSER=1

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy the application code
COPY . .

# Set proper permissions for Laravel
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Install dependencies and build assets
# Install without scripts first (post-autoload-dump runs artisan and fails without .env / APP_KEY).
RUN composer install --no-interaction --optimize-autoloader --no-dev --no-scripts \
    && ([ -f .env ] || cp .env.example .env) \
    && php artisan key:generate --force --no-interaction \
    && php artisan package:discover --ansi --force --no-interaction \
    && php artisan config:clear --no-interaction
RUN npm install
RUN npm run build

# Expose port 80
EXPOSE 80

# Start Apache with auto migration and seeding
CMD bash -c "php artisan migrate --force && php artisan db:seed --class=AdminSeeder --force && apache2-foreground"

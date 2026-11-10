FROM php:8.2-apache

# Install FFmpeg, libass, curl, git, and zip
RUN apt-get update && apt-get install -y \
    ffmpeg \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-install pdo pdo_pgsql pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory to public
WORKDIR /var/www/html
COPY . /var/www/html/

# Configure Apache DocumentRoot
ENV APACHE_DOCUMENT_ROOT /var/www/html/backend/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

EXPOSE 80
CMD ["apache2-foreground"]


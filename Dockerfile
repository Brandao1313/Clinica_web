FROM php:8.2-apache

# pdo_sqlite e sqlite3 já vêm habilitados na imagem oficial do PHP

# Habilita mod_rewrite (usado pelo .htaccess)
RUN a2enmod rewrite

# Configura o DocumentRoot para a raiz do projeto
ENV APACHE_DOCUMENT_ROOT=/var/www/html

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Permite que .htaccess sobrescreva configurações
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

WORKDIR /var/www/html

COPY . /var/www/html

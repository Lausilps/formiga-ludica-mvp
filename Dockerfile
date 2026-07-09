FROM php:8.2-apache

# Instala extensões necessárias
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Habilita mod_rewrite do Apache
RUN a2enmod rewrite

# Copia o projeto
COPY . /var/www/html/

# Permissões
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Configuração do Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

EXPOSE 80
FROM php:8.2-apache

# Instala extensões necessárias
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Corrige o erro de MPM do Apache
RUN a2dismod mpm_event mpm_worker && a2enmod mpm_prefork rewrite

# Copia o projeto
COPY . /var/www/html/

# Permissões
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Configuração do Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

EXPOSE 80
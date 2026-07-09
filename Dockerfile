FROM php:8.2-cli

# Instala extensões necessárias
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Instala curl e outras dependências
RUN apt-get update && apt-get install -y libcurl4-openssl-dev \
    && docker-php-ext-install curl

# Copia o projeto
COPY . /app

WORKDIR /app

EXPOSE 80

CMD ["php", "-S", "0.0.0.0:80", "-t", "/app"]
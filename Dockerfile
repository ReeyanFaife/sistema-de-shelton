FROM php:8.2-apache

# Instala suporte ao PostgreSQL no PHP
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Copia os arquivos do projeto para o diretório do servidor
COPY . /var/www/html/

EXPOSE 80

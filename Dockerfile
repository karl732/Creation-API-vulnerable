# Image PHP + Apache (HTTP + HTTPS avec certificats dans certs/)
FROM php:8.2-apache

# Activer mysqli pour la connexion MySQL
RUN docker-php-ext-install mysqli

# Activer mod_ssl ; ne pas définir Listen 443 ici (déjà dans l'image via mod_ssl) → évite "Cannot define multiple Listeners"
RUN a2enmod ssl \
    && rm -f /etc/apache2/sites-enabled/default-ssl.conf
COPY docker/apache-ssl.conf /etc/apache2/sites-enabled/banking-ssl.conf

# DocumentRoot : contenu du dossier www
COPY www/ /var/www/html/

# Permissions pour Apache
RUN chown -R www-data:www-data /var/www/html

# HTTP (80) et HTTPS (443)
EXPOSE 80 443

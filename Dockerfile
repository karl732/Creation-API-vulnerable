# Image PHP + Apache (HTTP uniquement - environnement de test vulnérable)
FROM php:8.2-apache

# Activer mysqli pour la connexion MySQL
RUN docker-php-ext-install mysqli

# Désactiver le site SSL (conflit Listen avec le site par défaut, on reste en HTTP uniquement)
RUN rm -f /etc/apache2/sites-enabled/ssl.conf /etc/apache2/sites-enabled/default-ssl.conf

# DocumentRoot : contenu du dossier www
COPY www/ /var/www/html/

# Permissions pour Apache
RUN chown -R www-data:www-data /var/www/html

# Pas de HTTPS volontairement - application de test de vulnérabilités
EXPOSE 80

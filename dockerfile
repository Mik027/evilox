# Utiliser une image de base PHP avec Nginx
FROM php:8.0-fpm

# Installer Python, Nginx, nano et les dépendances nécessaires
RUN apt-get update && apt-get install -y python3 python3-pip cron nginx nano && pip3 install requests beautifulsoup4

# Créer les répertoires pour les résultats et les scripts
RUN mkdir -p /app/scrap-results /app/scrap-script

# Déclarer la variable SCRAP_RESULTS_PATH
ENV SCRAP_RESULTS_PATH=/app/scrap-results

# Copier les fichiers nécessaires
COPY scrap-evilox.py /app/scrap-script/
COPY index.php /app/scrap-results/
COPY default.conf /etc/nginx/conf.d/default.conf

# Exposer le port 4000
EXPOSE 4000

# Définir le fuseau horaire à Paris
RUN ln -snf /usr/share/zoneinfo/Europe/Paris /etc/localtime && echo "Europe/Paris" > /etc/timezone

# Ajouter la tâche cron existante
RUN echo "1 0 * * * root /usr/bin/python3 /app/scrap-script/scrap-evilox.py >> /var/log/cron.log 2>&1" > /etc/cron.d/scrap-evilox && \
    chmod 0644 /etc/cron.d/scrap-evilox && \
    crontab /etc/cron.d/scrap-evilox

# Démarrer le service cron et PHP
CMD ["sh", "-c", "mkdir -p /var/log && touch /var/log/cron.log && service nginx start && php-fpm & cron -f & tail -f /var/log/nginx/access.log /var/log/nginx/error.log /var/log/cron.log"]
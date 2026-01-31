#!/bin/bash

echo "🚀 Début du déploiement..."

# Mode maintenance
php artisan down

# Récupérer les dernières modifications
git pull origin main

# Installer les dépendances
composer install --no-dev --optimize-autoloader

# Exécuter les migrations
php artisan migrate --force

# Synchroniser les codes-barres
php artisan products:sync-barcodes

# Vider les caches
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Réinstaller le lien symbolique storage
php artisan storage:link

# Permissions
chmod -R 755 storage bootstrap/cache

# Fin du mode maintenance
php artisan up

echo "✅ Déploiement terminé !"

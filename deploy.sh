#!/bin/bash

echo "🚀 Début du déploiement..."

# Arrêter en cas d'erreur
set -e

# Mode maintenance
php artisan down || true

# Récupérer les dernières modifications
git pull origin main

# Installer les dépendances
composer install --no-dev --optimize-autoloader

# Exécuter les migrations
echo "📦 Exécution des migrations..."
php artisan migrate --force

# Synchroniser les codes-barres (uniquement si migrations OK)
echo "🔄 Synchronisation des codes-barres..."
php artisan products:sync-barcodes || echo "⚠️ Sync des codes-barres ignoré (normal si première installation)"

# Vider les caches
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Réinstaller le lien symbolique storage
php artisan storage:link || true

# Permissions
chmod -R 755 storage bootstrap/cache

# Fin du mode maintenance
php artisan up

echo "✅ Déploiement terminé !"

<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Company;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Nettoyage de la base de données (Conservation Users, Roles, Permissions)...\n";

try {
    DB::beginTransaction();

    // Forcer la suppression des tables dans l'ordre inverse des dépendances ou désactiver brutalement
    if (DB::getDriverName() === 'sqlite') {
        DB::statement('PRAGMA foreign_keys = OFF');
    }

    $preservedTables = [
        'users',
        'roles',
        'permissions',
        'model_has_roles',
        'model_has_permissions',
        'role_has_permissions',
        'companies',
        'company_user',
        'migrations',
        'failed_jobs',
        'password_reset_tokens',
        'sessions',
        'cache',
        'cache_locks',
        'custom_roles',
        'user_custom_role',
        'sqlite_sequence', // Important pour SQLite
    ];

    // Récupérer les tables (Compatible SQLite/MySQL)
    $allTables = [];
    if (DB::getDriverName() === 'sqlite') {
        $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        foreach ($tables as $t) {
            $allTables[] = $t->name;
        }
    } else {
        $tables = DB::select('SHOW TABLES');
        foreach ($tables as $t) {
            $key = "Tables_in_" . env('DB_DATABASE');
            $allTables[] = $t->$key ?? array_values((array)$t)[0];
        }
    }

    // Ordre de nettoyage suggéré (Enfants d'abord)
    $priority = [
        'stock_movements',
        'sale_items',
        'sales',
        'payments',
        'cash_sessions',
        'product_warehouse',
        'products',
        'warehouses',
        'customers',
        'suppliers',
    ];

    // Supprimer d'abord les tables prioritaires
    foreach ($priority as $table) {
        if (in_array($table, $allTables)) {
             echo "Suppression prioritaire : $table\n";
             DB::table($table)->delete();
             if (DB::getDriverName() === 'sqlite') {
                DB::statement("DELETE FROM sqlite_sequence WHERE name = '$table'");
             }
        }
    }

    foreach ($allTables as $table) {
        if (!in_array($table, $preservedTables) && !in_array($table, $priority)) {
            echo "Suppression des données de la table : $table\n";
            try {
                DB::table($table)->delete();
                // Reset autoincrement
                if (DB::getDriverName() === 'sqlite') {
                    DB::statement("DELETE FROM sqlite_sequence WHERE name = '$table'");
                }
            } catch (\Exception $e) {
                // Fallback
                 DB::table($table)->delete();
            }
        }
    }
    
    // Réactiver les contraintes
    if (DB::getDriverName() === 'sqlite') {
        DB::statement('PRAGMA foreign_keys = ON');
    } else {
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
    
    // Réinitialisation des produits et entrepots basiques pour tester rapidement ?
    // Non, l'utilisateur a demandé de vider "sauf users...", donc je vide tout le reste (produits, ventes, stocks).
    // Il faudra recréer un entrepôt et un produit pour tester.
    
    // Je vais quand même réinjecter les données de base indispensables SI l'utilisateur n'a plus rien.
    // Typiquement : Un entrepôt par défaut et un settings comptable par défaut pour chaque compagnie restante ?
    // L'utilisateur a dit "vide les données", donc je respecte à la lettre.
    
    DB::commit();
    echo "\nBase de données nettoyée avec succès.\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "Erreur lors du nettoyage : " . $e->getMessage() . "\n";
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $newPerms = [
            ['name' => 'Vendre (nouvelle vente)', 'slug' => 'pos.sell', 'module' => 'pos', 'action' => 'create'],
            ['name' => 'Encaisser une facture', 'slug' => 'pos.collect', 'module' => 'pos', 'action' => 'create'],
        ];

        foreach ($newPerms as $perm) {
            \App\Models\Permission::firstOrCreate(['slug' => $perm['slug']], $perm);
        }

        $sell = \App\Models\Permission::where('slug', 'pos.sell')->first();
        $collect = \App\Models\Permission::where('slug', 'pos.collect')->first();

        if ($sell && $collect) {
            $roles = \App\Models\Role::whereIn('slug', ['admin', 'administrateur', 'manager', 'cashier', 'caissier'])->get();
            foreach ($roles as $role) {
                $role->permissions()->syncWithoutDetaching([$sell->id, $collect->id]);
            }

            $vendeurRoles = \App\Models\Role::whereIn('slug', ['vendeur'])->get();
            foreach ($vendeurRoles as $role) {
                $role->permissions()->syncWithoutDetaching([$sell->id]);
            }
        }
    }

    public function down(): void
    {
        $perms = \App\Models\Permission::whereIn('slug', ['pos.sell', 'pos.collect'])->get();
        foreach ($perms as $perm) {
            $perm->roles()->detach();
            $perm->delete();
        }
    }
};

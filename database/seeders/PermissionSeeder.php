<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Permission::modules() as $module => $moduleName) {
            $permissions = Permission::generateForModule($module, $moduleName);
            foreach ($permissions as $permission) {
                Permission::updateOrCreate(
                    ['slug' => $permission['slug']],
                    $permission
                );
            }
        }

        $this->command->info('Permissions créées/mises à jour avec succès !');
    }
}

<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $modules = Permission::modules();

        foreach ($modules as $module => $moduleName) {
            $permissions = Permission::generateForModule($module, $moduleName);
            foreach ($permissions as $permission) {
                Permission::updateOrCreate(
                    ['slug' => $permission['slug']],
                    $permission
                );
            }
        }
    }

    public function down(): void
    {
        $newModules = ['quotes', 'deliveries', 'pos', 'warehouses', 'transfers', 'inventory', 'hr', 'employees', 'accounting', 'banking'];

        foreach ($newModules as $module) {
            Permission::where('module', $module)->delete();
        }
    }
};

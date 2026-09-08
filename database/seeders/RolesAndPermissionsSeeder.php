<?php

namespace Database\Seeders;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = collect(PermissionName::cases())
            ->map(fn (PermissionName $permission) => Permission::firstOrCreate(['name' => $permission->value]));

        $superadmin = Role::firstOrCreate(['name' => RoleName::SuperAdmin->value]);
        $superadmin->syncPermissions($permissions);

        $admin = Role::firstOrCreate(['name' => RoleName::Admin->value]);
        $admin->syncPermissions([
            PermissionName::ManageUsers->value,
            PermissionName::ManageCompanies->value,
            PermissionName::ApproveUsers->value,
            PermissionName::ApproveCompanies->value,
        ]);

        Role::firstOrCreate(['name' => RoleName::Producer->value]);
        Role::firstOrCreate(['name' => RoleName::Buyer->value]);
    }
}

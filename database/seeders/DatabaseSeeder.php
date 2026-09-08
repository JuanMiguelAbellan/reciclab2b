<?php

namespace Database\Seeders;

use App\Enums\CompanyType;
use App\Enums\RoleName;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $superadmin = User::factory()->create([
            'first_name' => 'Super',
            'last_name' => 'Administrador',
            'email' => 'superadmin@reciclab2b.test',
        ]);
        $superadmin->assignRole(RoleName::SuperAdmin->value);

        $admin = User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'ReciclaB2B',
            'email' => 'admin@reciclab2b.test',
        ]);
        $admin->assignRole(RoleName::Admin->value);

        $producerUser = User::factory()->create([
            'first_name' => 'Paco',
            'last_name' => 'Productor',
            'email' => 'productor@reciclab2b.test',
        ]);
        $producerUser->assignRole(RoleName::Producer->value);
        $producerCompany = Company::factory()->create([
            'trade_name' => 'Reciclajes del Sur',
            'company_type' => CompanyType::Producer,
            'is_current_supplier' => true,
        ]);
        $producerCompany->users()->attach($producerUser, ['is_primary' => true]);

        $buyerUser = User::factory()->create([
            'first_name' => 'Berta',
            'last_name' => 'Compradora',
            'email' => 'comprador@reciclab2b.test',
        ]);
        $buyerUser->assignRole(RoleName::Buyer->value);
        $buyerCompany = Company::factory()->create([
            'trade_name' => 'Distribuciones Levante',
            'company_type' => CompanyType::Distributor,
            'is_current_client' => true,
        ]);
        $buyerCompany->users()->attach($buyerUser, ['is_primary' => true]);

        // Usuario aprobado sin empresa todavía, para probar el flujo de alta de empresa.
        User::factory()->create([
            'first_name' => 'Usuario',
            'last_name' => 'de prueba',
            'email' => 'test@example.com',
        ]);
    }
}

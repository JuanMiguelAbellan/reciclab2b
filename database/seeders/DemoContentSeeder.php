<?php

namespace Database\Seeders;

use App\Enums\CompanyType;
use App\Enums\MaterialType;
use App\Enums\OrderStatus;
use App\Enums\RoleName;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Offer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Puebla la demo pública en Railway con cuentas y contenido navegable
 * (mercado con ofertas reales, un chat con mensajes, pedidos en distintos
 * estados, y una empresa pendiente de aprobar) para que un visitante del
 * portfolio pueda entrar con cualquiera de los roles y ver la aplicación
 * real, no solo la landing pública.
 *
 * Solo pensado para producción: se ejecuta en cada arranque del contenedor
 * (ver Dockerfile.railway) pero es un no-op tras la primera vez, así que es
 * seguro dejarlo ahí de forma permanente.
 */
class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('email', 'superadmin@reciclab2b.test')->exists()) {
            return;
        }

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

        $producer1 = User::factory()->create([
            'first_name' => 'Paco',
            'last_name' => 'Productor',
            'email' => 'productor@reciclab2b.test',
        ]);
        $producer1->assignRole(RoleName::Producer->value);
        $producerCompany1 = Company::factory()->create([
            'trade_name' => 'Reciclajes del Sur',
            'company_type' => CompanyType::Producer,
            'province' => 'Sevilla',
            'municipality' => 'Sevilla',
            'is_current_supplier' => true,
        ]);
        $producerCompany1->users()->attach($producer1, ['is_primary' => true]);

        $producer2 = User::factory()->create([
            'first_name' => 'Elena',
            'last_name' => 'Ruiz',
            'email' => 'productor2@reciclab2b.test',
        ]);
        $producer2->assignRole(RoleName::Producer->value);
        $producerCompany2 = Company::factory()->create([
            'trade_name' => 'EcoPlásticos Levante',
            'company_type' => CompanyType::Producer,
            'province' => 'Valencia',
            'municipality' => 'Valencia',
            'is_current_supplier' => true,
        ]);
        $producerCompany2->users()->attach($producer2, ['is_primary' => true]);

        $buyer = User::factory()->create([
            'first_name' => 'Berta',
            'last_name' => 'Compradora',
            'email' => 'comprador@reciclab2b.test',
        ]);
        $buyer->assignRole(RoleName::Buyer->value);
        $buyerCompany = Company::factory()->create([
            'trade_name' => 'Distribuciones Levante',
            'company_type' => CompanyType::Distributor,
            'province' => 'Valencia',
            'municipality' => 'Valencia',
            'is_current_client' => true,
        ]);
        $buyerCompany->users()->attach($buyer, ['is_primary' => true]);

        // Usuario aprobado sin empresa todavía, para el flujo de alta de empresa.
        User::factory()->create([
            'first_name' => 'Usuario',
            'last_name' => 'de prueba',
            'email' => 'test@example.com',
        ]);

        // Una empresa (y su usuario) recién registradas, pendientes de aprobar
        // en el panel de admin — así el rol admin/superadmin tiene algo que
        // revisar, no solo una tabla vacía.
        $pendingUser = User::factory()->pending()->create([
            'first_name' => 'Nueva',
            'last_name' => 'Recicladora',
            'email' => 'pendiente@reciclab2b.test',
        ]);
        $pendingCompany = Company::factory()->pending()->create([
            'trade_name' => 'Norte Recicla S.L.',
            'company_type' => CompanyType::Producer,
            'province' => 'Zaragoza',
            'municipality' => 'Zaragoza',
        ]);
        $pendingCompany->users()->attach($pendingUser, ['is_primary' => true]);

        // Ofertas publicadas con coordenadas reales, para que /mercado tenga
        // contenido real que filtrar y ver en el mapa.
        $offer1 = Offer::factory()->published()->create([
            'company_id' => $producerCompany1->id,
            'created_by' => $producer1->id,
            'material' => MaterialType::Cardboard,
            'quantity_tons' => 120,
            'price_per_ton' => 145,
            'province' => 'Sevilla',
            'municipality' => 'Sevilla',
            'description' => 'Cartón compactado de recogida industrial, humedad controlada.',
            'exact_latitude' => 37.3891,
            'exact_longitude' => -5.9845,
        ]);
        $offer1->refreshPublicLocation();
        $offer1->save();

        $offer2 = Offer::factory()->published()->create([
            'company_id' => $producerCompany1->id,
            'created_by' => $producer1->id,
            'material' => MaterialType::Plastic,
            'quantity_tons' => 60,
            'price_per_ton' => 380,
            'province' => 'Sevilla',
            'municipality' => 'Dos Hermanas',
            'description' => 'Film plástico agrícola triturado, listo para reprocesado.',
            'exact_latitude' => 37.2836,
            'exact_longitude' => -5.9223,
        ]);
        $offer2->refreshPublicLocation();
        $offer2->save();

        Offer::factory()->paused()->create([
            'company_id' => $producerCompany1->id,
            'created_by' => $producer1->id,
            'material' => MaterialType::Cardboard,
            'province' => 'Sevilla',
            'municipality' => 'Sevilla',
            'exact_latitude' => 37.3891,
            'exact_longitude' => -5.9845,
        ])->refreshPublicLocation();

        $offer3 = Offer::factory()->published()->create([
            'company_id' => $producerCompany2->id,
            'created_by' => $producer2->id,
            'material' => MaterialType::Plastic,
            'quantity_tons' => 90,
            'price_per_ton' => 410,
            'province' => 'Valencia',
            'municipality' => 'Valencia',
            'description' => 'PET postconsumo, balas de 500kg.',
            'exact_latitude' => 39.4699,
            'exact_longitude' => -0.3763,
        ]);
        $offer3->refreshPublicLocation();
        $offer3->save();

        $offer4 = Offer::factory()->published()->create([
            'company_id' => $producerCompany2->id,
            'created_by' => $producer2->id,
            'material' => MaterialType::Cardboard,
            'quantity_tons' => 200,
            'price_per_ton' => 130,
            'province' => 'Valencia',
            'municipality' => 'Paterna',
            'description' => 'Cartón ondulado de excedente de packaging.',
            'exact_latitude' => 39.5027,
            'exact_longitude' => -0.4406,
        ]);
        $offer4->refreshPublicLocation();
        $offer4->save();

        // Un chat real, con mensajes en ambos sentidos, sobre la primera oferta.
        $conversation = Conversation::factory()->create([
            'offer_id' => $offer1->id,
            'buyer_company_id' => $buyerCompany->id,
        ]);

        $exchange = [
            [$buyer, '¿Este cartón viene de recogida mixta o solo de packaging?'],
            [$producer1, 'Solo packaging, sin mezcla con otros residuos. Podemos mandar ficha técnica si la necesitáis.'],
            [$buyer, 'Perfecto, nos interesan los 120 t. ¿Podéis entregar en Valencia?'],
            [$producer1, 'Sí, transporte incluido en el precio hasta 150km. Os paso pedido formal ahora mismo.'],
        ];
        foreach ($exchange as $i => [$sender, $body]) {
            Message::factory()->create([
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'body' => $body,
                'created_at' => now()->subMinutes((count($exchange) - $i) * 7),
            ]);
        }

        // Pedidos en distintos estados, para ver el ciclo completo desde
        // ambos roles (comprador y vendedor).
        Order::factory()->create([
            'offer_id' => $offer1->id,
            'buyer_company_id' => $buyerCompany->id,
            'created_by' => $buyer->id,
            'conversation_id' => $conversation->id,
            'quantity_tons' => 120,
            'price_per_ton' => 145,
            'status' => OrderStatus::Pending,
        ]);

        Order::factory()->completed()->create([
            'offer_id' => $offer3->id,
            'buyer_company_id' => $buyerCompany->id,
            'created_by' => $buyer->id,
            'responded_by' => $producer2->id,
            'quantity_tons' => 40,
            'price_per_ton' => 410,
        ]);
    }
}

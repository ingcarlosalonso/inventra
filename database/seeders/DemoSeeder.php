<?php

namespace Database\Seeders;

use App\Models\CashMovementType;
use App\Models\Client;
use App\Models\Courier;
use App\Models\Currency;
use App\Models\DailyCash;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderState;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Permission;
use App\Models\PointOfSale;
use App\Models\Presentation;
use App\Models\PresentationType;
use App\Models\Product;
use App\Models\ProductMovementType;
use App\Models\ProductPresentation;
use App\Models\ProductType;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleState;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPermissionsAndRoles();
        $this->seedConfiguration();
        $this->seedUsers();
        $this->seedSuppliers();
        $this->seedClients();
        $this->seedProducts();
        $this->seedSales();
        $this->seedOrders();
        $this->seedDailyCash();
    }

    private function seedPermissionsAndRoles(): void
    {
        $this->command->info('Seeding permissions and roles...');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'list_users', 'create_edit_delete_users',
            'list_roles', 'create_edit_delete_roles',
            'list_clients', 'create_edit_delete_clients',
            'list_suppliers', 'create_edit_delete_suppliers',
            'list_products', 'create_edit_delete_products',
            'bulk_update_product_price',
            'create_edit_delete_product_types',
            'create_edit_delete_presentation_types',
            'create_edit_delete_presentations',
            'create_edit_delete_product_movement_types',
            'list_receptions', 'create_edit_delete_receptions',
            'list_sales', 'create_edit_delete_sales',
            'create_edit_delete_sale_states',
            'create_edit_delete_payment_methods',
            'create_edit_delete_points_of_sale',
            'list_quotes', 'create_edit_delete_quotes',
            'list_orders', 'create_edit_delete_orders',
            'create_edit_delete_order_states',
            'create_edit_delete_couriers',
            'list_daily_cashes', 'enable_close_daily_cash',
            'create_edit_delete_cash_movement_types',
            'list_reports', 'list_report_sales', 'list_report_products',
            'list_report_payments', 'list_report_inventory',
            'list_report_daily_cashes', 'list_report_orders',
            'list_report_clients', 'list_report_purchases',
            'create_edit_delete_currencies',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $adminRole = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
        $adminRole->syncPermissions(Permission::all());

        $vendedorRole = Role::firstOrCreate(['name' => 'Vendedor', 'guard_name' => 'web']);
        $vendedorRole->syncPermissions([
            'list_clients', 'create_edit_delete_clients',
            'list_products',
            'list_sales', 'create_edit_delete_sales',
            'list_quotes', 'create_edit_delete_quotes',
            'list_orders', 'create_edit_delete_orders',
            'list_daily_cashes',
        ]);

        $depositeroRole = Role::firstOrCreate(['name' => 'Depositero', 'guard_name' => 'web']);
        $depositeroRole->syncPermissions([
            'list_products', 'create_edit_delete_products',
            'list_receptions', 'create_edit_delete_receptions',
            'list_orders',
        ]);
    }

    private function seedConfiguration(): void
    {
        $this->command->info('Seeding configuration...');

        Currency::firstOrCreate(['name' => 'Peso Argentino'], ['symbol' => '$', 'iso_code' => 'ARS', 'is_default' => true, 'is_active' => true]);
        Currency::firstOrCreate(['name' => 'Dólar Estadounidense'], ['symbol' => 'US$', 'iso_code' => 'USD', 'is_default' => false, 'is_active' => true]);

        $pos = PointOfSale::firstOrCreate(
            ['number' => 1],
            ['name' => 'Casa Central', 'address' => 'Av. Corrientes 1234, CABA', 'is_active' => true]
        );
        PointOfSale::firstOrCreate(
            ['number' => 2],
            ['name' => 'Sucursal Norte', 'address' => 'Av. Santa Fe 5678, CABA', 'is_active' => true]
        );

        SaleState::firstOrCreate(['name' => 'Pendiente'], ['color' => '#F59E0B', 'is_default' => true, 'is_final_state' => false, 'is_active' => true, 'sort_order' => 1]);
        SaleState::firstOrCreate(['name' => 'Confirmada'], ['color' => '#3B82F6', 'is_default' => false, 'is_final_state' => false, 'is_active' => true, 'sort_order' => 2]);
        SaleState::firstOrCreate(['name' => 'Entregada'], ['color' => '#10B981', 'is_default' => false, 'is_final_state' => true, 'is_active' => true, 'sort_order' => 3]);
        SaleState::firstOrCreate(['name' => 'Cancelada'], ['color' => '#EF4444', 'is_default' => false, 'is_final_state' => true, 'is_active' => true, 'sort_order' => 4]);

        OrderState::firstOrCreate(['name' => 'Nuevo'], ['color' => '#8B5CF6', 'is_default' => true, 'is_final_state' => false, 'is_active' => true, 'sort_order' => 1]);
        OrderState::firstOrCreate(['name' => 'En preparación'], ['color' => '#F59E0B', 'is_default' => false, 'is_final_state' => false, 'is_active' => true, 'sort_order' => 2]);
        OrderState::firstOrCreate(['name' => 'En camino'], ['color' => '#3B82F6', 'is_default' => false, 'is_final_state' => false, 'is_active' => true, 'sort_order' => 3]);
        OrderState::firstOrCreate(['name' => 'Entregado'], ['color' => '#10B981', 'is_default' => false, 'is_final_state' => true, 'is_active' => true, 'sort_order' => 4]);

        PaymentMethod::firstOrCreate(['name' => 'Efectivo'], ['is_active' => true]);
        PaymentMethod::firstOrCreate(['name' => 'Tarjeta de Débito'], ['is_active' => true]);
        PaymentMethod::firstOrCreate(['name' => 'Tarjeta de Crédito'], ['is_active' => true]);
        PaymentMethod::firstOrCreate(['name' => 'Transferencia Bancaria'], ['is_active' => true]);
        PaymentMethod::firstOrCreate(['name' => 'Mercado Pago'], ['is_active' => true]);

        CashMovementType::firstOrCreate(['name' => 'Depósito'], ['is_income' => true, 'is_active' => true]);
        CashMovementType::firstOrCreate(['name' => 'Retiro de caja'], ['is_income' => false, 'is_active' => true]);
        CashMovementType::firstOrCreate(['name' => 'Pago a proveedor'], ['is_income' => false, 'is_active' => true]);
        CashMovementType::firstOrCreate(['name' => 'Gastos operativos'], ['is_income' => false, 'is_active' => true]);

        ProductMovementType::firstOrCreate(['name' => 'Ajuste de inventario'], ['is_income' => true, 'is_active' => true]);
        ProductMovementType::firstOrCreate(['name' => 'Merma'], ['is_income' => false, 'is_active' => true]);
        ProductMovementType::firstOrCreate(['name' => 'Devolución de cliente'], ['is_income' => true, 'is_active' => true]);
        ProductMovementType::firstOrCreate(['name' => 'Rotura'], ['is_income' => false, 'is_active' => true]);

        $unitType = PresentationType::firstOrCreate(['name' => 'Unidad'], ['abbreviation' => 'u', 'is_active' => true]);
        $weightType = PresentationType::firstOrCreate(['name' => 'Peso'], ['abbreviation' => 'kg', 'is_active' => true]);
        $volumeType = PresentationType::firstOrCreate(['name' => 'Volumen'], ['abbreviation' => 'l', 'is_active' => true]);

        Presentation::firstOrCreate(['presentation_type_id' => $unitType->id, 'quantity' => 1], ['is_active' => true]);
        Presentation::firstOrCreate(['presentation_type_id' => $unitType->id, 'quantity' => 6], ['is_active' => true]);
        Presentation::firstOrCreate(['presentation_type_id' => $unitType->id, 'quantity' => 12], ['is_active' => true]);
        Presentation::firstOrCreate(['presentation_type_id' => $weightType->id, 'quantity' => 1], ['is_active' => true]);
        Presentation::firstOrCreate(['presentation_type_id' => $weightType->id, 'quantity' => 0.5], ['is_active' => true]);
        Presentation::firstOrCreate(['presentation_type_id' => $volumeType->id, 'quantity' => 1], ['is_active' => true]);
    }

    private function seedUsers(): void
    {
        $this->command->info('Seeding users...');

        $admin = User::firstOrCreate(
            ['email' => 'admin@demo.com'],
            ['name' => 'Carlos Administrador', 'password' => Hash::make('password'), 'email_verified_at' => now()]
        );
        $admin->syncRoles(['Administrador']);

        $vendedor1 = User::firstOrCreate(
            ['email' => 'maria@demo.com'],
            ['name' => 'María González', 'password' => Hash::make('password'), 'email_verified_at' => now()]
        );
        $vendedor1->syncRoles(['Vendedor']);

        $vendedor2 = User::firstOrCreate(
            ['email' => 'juan@demo.com'],
            ['name' => 'Juan Pérez', 'password' => Hash::make('password'), 'email_verified_at' => now()]
        );
        $vendedor2->syncRoles(['Vendedor']);

        $depositero = User::firstOrCreate(
            ['email' => 'lucas@demo.com'],
            ['name' => 'Lucas Rodríguez', 'password' => Hash::make('password'), 'email_verified_at' => now()]
        );
        $depositero->syncRoles(['Depositero']);
    }

    private function seedSuppliers(): void
    {
        $this->command->info('Seeding suppliers...');

        $suppliers = [
            ['name' => 'Distribuidora Norte S.A.', 'contact_name' => 'Roberto Sánchez', 'email' => 'ventas@norte.com', 'phone' => '011-4523-1234', 'address' => 'Av. Independencia 2345, CABA'],
            ['name' => 'Importadora del Sur', 'contact_name' => 'Ana Martínez', 'email' => 'compras@sur.com', 'phone' => '011-3345-5678', 'address' => 'Av. Rivadavia 8900, CABA'],
            ['name' => 'Mayorista Central', 'contact_name' => 'Diego López', 'email' => 'info@mayorista.com', 'phone' => '011-4678-9012', 'address' => 'Juan B. Justo 456, CABA'],
            ['name' => 'Proveedor Tech S.R.L.', 'contact_name' => 'Valeria Castro', 'email' => 'contacto@protech.com', 'phone' => '011-2234-3456', 'address' => 'Maipú 123, CABA'],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::firstOrCreate(['email' => $supplier['email']], array_merge($supplier, ['is_active' => true]));
        }
    }

    private function seedClients(): void
    {
        $this->command->info('Seeding clients...');

        $clients = [
            ['first_name' => 'Luciana', 'last_name' => 'Fernández', 'email' => 'luciana.fernandez@gmail.com', 'phone' => '11-5523-4567', 'address' => 'Corrientes 1500, CABA'],
            ['first_name' => 'Martín', 'last_name' => 'Herrera', 'email' => 'martin.herrera@hotmail.com', 'phone' => '11-4432-8901', 'address' => 'Av. Belgrano 890, CABA'],
            ['first_name' => 'Sofía', 'last_name' => 'Ramírez', 'email' => 'sofia.ramirez@yahoo.com', 'phone' => '11-6678-2345', 'address' => 'Palermo 234, CABA'],
            ['first_name' => 'Tomás', 'last_name' => 'Vargas', 'email' => 'tomas.vargas@gmail.com', 'phone' => '11-3345-6789', 'address' => 'Villa Urquiza 567, CABA'],
            ['first_name' => 'Camila', 'last_name' => 'Torres', 'email' => 'camila.torres@gmail.com', 'phone' => '11-4456-0123', 'address' => 'Floresta 890, CABA'],
            ['first_name' => 'Facundo', 'last_name' => 'Acosta', 'email' => 'facundo.acosta@outlook.com', 'phone' => '11-5567-4567', 'address' => 'San Telmo 123, CABA'],
            ['first_name' => 'Valentina', 'last_name' => 'Molina', 'email' => 'vale.molina@gmail.com', 'phone' => '11-6789-8901', 'address' => 'Recoleta 456, CABA'],
            ['first_name' => 'Nicolás', 'last_name' => 'Suárez', 'email' => 'nicolas.suarez@gmail.com', 'phone' => '11-2234-2345', 'address' => 'Flores 789, CABA'],
            ['first_name' => 'Empresa', 'last_name' => 'MegaStore S.A.', 'email' => 'compras@megastore.com', 'phone' => '011-4000-1111', 'address' => 'Microcentro 1000, CABA'],
            ['first_name' => 'Empresa', 'last_name' => 'Retail Plus S.R.L.', 'email' => 'admin@retailplus.com', 'phone' => '011-5000-2222', 'address' => 'Puerto Madero 200, CABA'],
        ];

        foreach ($clients as $client) {
            Client::firstOrCreate(['email' => $client['email']], array_merge($client, ['is_active' => true]));
        }
    }

    private function seedProducts(): void
    {
        $this->command->info('Seeding products...');

        $unitPresentation = Presentation::whereHas('presentationType', fn ($q) => $q->where('abbreviation', 'u'))
            ->where('quantity', 1)->first();

        $weightPresentation = Presentation::whereHas('presentationType', fn ($q) => $q->where('abbreviation', 'kg'))
            ->where('quantity', 1)->first();

        $electronicosType = ProductType::firstOrCreate(['name' => 'Electrónicos'], ['is_active' => true]);
        $computacionType = ProductType::firstOrCreate(['name' => 'Computación'], ['parent_id' => $electronicosType->id, 'is_active' => true]);
        $celularesType = ProductType::firstOrCreate(['name' => 'Celulares y Tablets'], ['parent_id' => $electronicosType->id, 'is_active' => true]);

        $ropaType = ProductType::firstOrCreate(['name' => 'Indumentaria'], ['is_active' => true]);
        $camiseriaType = ProductType::firstOrCreate(['name' => 'Camisería'], ['parent_id' => $ropaType->id, 'is_active' => true]);
        $calzadoType = ProductType::firstOrCreate(['name' => 'Calzado'], ['parent_id' => $ropaType->id, 'is_active' => true]);

        $alimentosType = ProductType::firstOrCreate(['name' => 'Alimentos'], ['is_active' => true]);
        $lacteoType = ProductType::firstOrCreate(['name' => 'Lácteos'], ['parent_id' => $alimentosType->id, 'is_active' => true]);
        $bebidasType = ProductType::firstOrCreate(['name' => 'Bebidas'], ['parent_id' => $alimentosType->id, 'is_active' => true]);

        $products = [
            ['type' => $computacionType, 'name' => 'Notebook Lenovo IdeaPad 15', 'price' => 450000, 'stock' => 12, 'presentation' => $unitPresentation],
            ['type' => $computacionType, 'name' => 'Monitor Samsung 27" Full HD', 'price' => 185000, 'stock' => 8, 'presentation' => $unitPresentation],
            ['type' => $computacionType, 'name' => 'Teclado Mecánico Redragon', 'price' => 32000, 'stock' => 25, 'presentation' => $unitPresentation],
            ['type' => $computacionType, 'name' => 'Mouse Inalámbrico Logitech M185', 'price' => 12500, 'stock' => 40, 'presentation' => $unitPresentation],
            ['type' => $celularesType, 'name' => 'Smartphone Samsung Galaxy A54', 'price' => 320000, 'stock' => 15, 'presentation' => $unitPresentation],
            ['type' => $celularesType, 'name' => 'iPhone 14 128GB', 'price' => 850000, 'stock' => 5, 'presentation' => $unitPresentation],
            ['type' => $celularesType, 'name' => 'Tablet Xiaomi Pad 6', 'price' => 210000, 'stock' => 9, 'presentation' => $unitPresentation],
            ['type' => $camiseriaType, 'name' => 'Camisa Oxford Manga Larga', 'price' => 18500, 'stock' => 30, 'presentation' => $unitPresentation],
            ['type' => $camiseriaType, 'name' => 'Remera Algodón Premium', 'price' => 8900, 'stock' => 60, 'presentation' => $unitPresentation],
            ['type' => $calzadoType, 'name' => 'Zapatillas Nike Air Max', 'price' => 95000, 'stock' => 18, 'presentation' => $unitPresentation],
            ['type' => $calzadoType, 'name' => 'Mocasines de Cuero', 'price' => 42000, 'stock' => 12, 'presentation' => $unitPresentation],
            ['type' => $lacteoType, 'name' => 'Queso Cremoso por kg', 'price' => 4800, 'stock' => 50, 'presentation' => $weightPresentation],
            ['type' => $lacteoType, 'name' => 'Manteca 200g', 'price' => 1200, 'stock' => 80, 'presentation' => $unitPresentation],
            ['type' => $bebidasType, 'name' => 'Agua Mineral 2L', 'price' => 650, 'stock' => 120, 'presentation' => $unitPresentation],
            ['type' => $bebidasType, 'name' => 'Jugo Cepita 1L', 'price' => 1100, 'stock' => 95, 'presentation' => $unitPresentation],
        ];

        foreach ($products as $data) {
            $product = Product::firstOrCreate(
                ['name' => $data['name']],
                ['product_type_id' => $data['type']->id, 'description' => null, 'is_active' => true]
            );

            if ($data['presentation']) {
                ProductPresentation::firstOrCreate(
                    ['product_id' => $product->id, 'presentation_id' => $data['presentation']->id],
                    ['price' => $data['price'], 'stock' => $data['stock'], 'min_stock' => max(2, intval($data['stock'] * 0.1)), 'is_active' => true]
                );
            }
        }
    }

    private function seedSales(): void
    {
        $this->command->info('Seeding sales...');

        $pendingState = SaleState::where('name', 'Pendiente')->first();
        $confirmedState = SaleState::where('name', 'Confirmada')->first();
        $deliveredState = SaleState::where('name', 'Entregada')->first();
        $cancelledState = SaleState::where('name', 'Cancelada')->first();
        $pos = PointOfSale::where('number', 1)->first();
        $user = User::where('email', 'maria@demo.com')->first() ?? User::first();
        $user2 = User::where('email', 'juan@demo.com')->first() ?? User::first();
        $efectivo = PaymentMethod::where('name', 'Efectivo')->first();
        $transferencia = PaymentMethod::where('name', 'Transferencia Bancaria')->first();
        $tarjetaDebito = PaymentMethod::where('name', 'Tarjeta de Débito')->first();

        $clients = Client::limit(8)->get();
        $presentations = ProductPresentation::with('product')->limit(10)->get();

        if ($presentations->isEmpty() || ! $pos) {
            return;
        }

        $salesData = [
            ['state' => $deliveredState, 'user' => $user, 'client_idx' => 0, 'days_ago' => 30, 'payment' => $efectivo],
            ['state' => $deliveredState, 'user' => $user, 'client_idx' => 1, 'days_ago' => 28, 'payment' => $transferencia],
            ['state' => $deliveredState, 'user' => $user2, 'client_idx' => 2, 'days_ago' => 25, 'payment' => $tarjetaDebito],
            ['state' => $deliveredState, 'user' => $user, 'client_idx' => 3, 'days_ago' => 22, 'payment' => $efectivo],
            ['state' => $deliveredState, 'user' => $user2, 'client_idx' => 4, 'days_ago' => 20, 'payment' => $transferencia],
            ['state' => $deliveredState, 'user' => $user, 'client_idx' => 0, 'days_ago' => 18, 'payment' => $efectivo],
            ['state' => $confirmedState, 'user' => $user2, 'client_idx' => 5, 'days_ago' => 15, 'payment' => $transferencia],
            ['state' => $confirmedState, 'user' => $user, 'client_idx' => 6, 'days_ago' => 12, 'payment' => $tarjetaDebito],
            ['state' => $confirmedState, 'user' => $user2, 'client_idx' => 1, 'days_ago' => 10, 'payment' => $efectivo],
            ['state' => $confirmedState, 'user' => $user, 'client_idx' => 7, 'days_ago' => 8, 'payment' => $transferencia],
            ['state' => $pendingState, 'user' => $user, 'client_idx' => 2, 'days_ago' => 5, 'payment' => $efectivo],
            ['state' => $pendingState, 'user' => $user2, 'client_idx' => 3, 'days_ago' => 4, 'payment' => $tarjetaDebito],
            ['state' => $pendingState, 'user' => $user, 'client_idx' => null, 'days_ago' => 3, 'payment' => $efectivo],
            ['state' => $pendingState, 'user' => $user2, 'client_idx' => 4, 'days_ago' => 2, 'payment' => $transferencia],
            ['state' => $cancelledState, 'user' => $user, 'client_idx' => 5, 'days_ago' => 14, 'payment' => null],
        ];

        foreach ($salesData as $data) {
            $presentation1 = $presentations->random();
            $presentation2 = $presentations->random();

            $qty1 = rand(1, 5);
            $qty2 = rand(1, 3);
            $price1 = $presentation1->price;
            $price2 = $presentation2->price;
            $subtotal = ($qty1 * $price1) + ($qty2 * $price2);

            $sale = Sale::create([
                'client_id' => $data['client_idx'] !== null && $clients->count() > $data['client_idx']
                    ? $clients[$data['client_idx']]->id
                    : null,
                'point_of_sale_id' => $pos->id,
                'sale_state_id' => $data['state']->id,
                'currency_id' => null,
                'user_id' => $data['user']->id,
                'subtotal' => $subtotal,
                'discount_type' => null,
                'discount_value' => 0,
                'discount_amount' => 0,
                'total' => $subtotal,
                'notes' => null,
                'created_at' => now()->subDays($data['days_ago']),
                'updated_at' => now()->subDays($data['days_ago']),
            ]);

            SaleItem::create([
                'sale_id' => $sale->id,
                'product_presentation_id' => $presentation1->id,
                'saleable_type' => null,
                'saleable_id' => null,
                'description' => $presentation1->product->name ?? 'Producto',
                'quantity' => $qty1,
                'unit_price' => $price1,
                'discount_type' => null,
                'discount_value' => 0,
                'discount_amount' => 0,
                'total' => $qty1 * $price1,
            ]);

            SaleItem::create([
                'sale_id' => $sale->id,
                'product_presentation_id' => $presentation2->id,
                'saleable_type' => null,
                'saleable_id' => null,
                'description' => $presentation2->product->name ?? 'Producto',
                'quantity' => $qty2,
                'unit_price' => $price2,
                'discount_type' => null,
                'discount_value' => 0,
                'discount_amount' => 0,
                'total' => $qty2 * $price2,
            ]);

            if ($data['payment'] && $data['state']->name !== 'Cancelada') {
                Payment::create([
                    'payable_type' => Sale::class,
                    'payable_id' => $sale->id,
                    'payment_method_id' => $data['payment']->id,
                    'currency_id' => null,
                    'daily_cash_id' => null,
                    'amount' => $subtotal,
                    'exchange_rate' => null,
                    'notes' => null,
                ]);
            }
        }
    }

    private function seedOrders(): void
    {
        $this->command->info('Seeding orders...');

        $newState = OrderState::where('name', 'Nuevo')->first();
        $prepState = OrderState::where('name', 'En preparación')->first();
        $onWayState = OrderState::where('name', 'En camino')->first();
        $deliveredState = OrderState::where('name', 'Entregado')->first();
        $pos = PointOfSale::where('number', 1)->first();
        $user = User::where('email', 'maria@demo.com')->first() ?? User::first();

        $clients = Client::limit(6)->get();
        $presentations = ProductPresentation::with('product')->limit(8)->get();

        if ($presentations->isEmpty() || ! $pos || $clients->isEmpty()) {
            return;
        }

        $couriers = Courier::all();
        if ($couriers->isEmpty()) {
            Courier::create(['name' => 'Federico Ramos', 'phone' => '11-3456-7890', 'is_active' => true]);
            Courier::create(['name' => 'Claudia Vega', 'phone' => '11-4567-8901', 'is_active' => true]);
            $couriers = Courier::all();
        }

        $ordersData = [
            ['state' => $deliveredState, 'client_idx' => 0, 'days_ago' => 25, 'courier_idx' => 0],
            ['state' => $deliveredState, 'client_idx' => 1, 'days_ago' => 20, 'courier_idx' => 1],
            ['state' => $onWayState, 'client_idx' => 2, 'days_ago' => 5, 'courier_idx' => 0],
            ['state' => $onWayState, 'client_idx' => 3, 'days_ago' => 3, 'courier_idx' => 1],
            ['state' => $prepState, 'client_idx' => 4, 'days_ago' => 2, 'courier_idx' => 0],
            ['state' => $prepState, 'client_idx' => 5, 'days_ago' => 1, 'courier_idx' => null],
            ['state' => $newState, 'client_idx' => 0, 'days_ago' => 1, 'courier_idx' => null],
            ['state' => $newState, 'client_idx' => 1, 'days_ago' => 0, 'courier_idx' => null],
        ];

        foreach ($ordersData as $data) {
            $presentation = $presentations->random();
            $qty = rand(1, 4);
            $price = $presentation->price;
            $total = $qty * $price;

            $courier = $data['courier_idx'] !== null && $couriers->count() > $data['courier_idx']
                ? $couriers[$data['courier_idx']]
                : null;

            $order = Order::create([
                'client_id' => $clients[$data['client_idx']]->id,
                'courier_id' => $courier?->id,
                'order_state_id' => $data['state']->id,
                'point_of_sale_id' => $pos->id,
                'sale_id' => null,
                'currency_id' => null,
                'user_id' => $user->id,
                'address' => $clients[$data['client_idx']]->address,
                'notes' => null,
                'requires_delivery' => true,
                'delivery_date' => now()->subDays($data['days_ago'])->addDays(rand(1, 5))->format('Y-m-d'),
                'scheduled_at' => null,
                'subtotal' => $total,
                'discount_type' => null,
                'discount_value' => 0,
                'discount_amount' => 0,
                'total' => $total,
                'created_at' => now()->subDays($data['days_ago']),
                'updated_at' => now()->subDays($data['days_ago']),
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'product_presentation_id' => $presentation->id,
                'saleable_type' => null,
                'saleable_id' => null,
                'description' => $presentation->product->name ?? 'Producto',
                'quantity' => $qty,
                'unit_price' => $price,
                'discount_type' => null,
                'discount_value' => 0,
                'discount_amount' => 0,
                'total' => $total,
            ]);
        }
    }

    private function seedDailyCash(): void
    {
        $this->command->info('Seeding daily cash...');

        $pos = PointOfSale::where('number', 1)->first();
        $user = User::where('email', 'admin@demo.com')->first() ?? User::first();

        if (! $pos || ! $user) {
            return;
        }

        for ($i = 7; $i >= 1; $i--) {
            DailyCash::create([
                'point_of_sale_id' => $pos->id,
                'user_id' => $user->id,
                'opening_balance' => rand(5000, 20000),
                'closing_balance' => rand(50000, 200000),
                'opened_at' => now()->subDays($i)->setTime(8, 0),
                'closed_at' => now()->subDays($i)->setTime(20, 0),
                'is_closed' => true,
                'notes' => null,
            ]);
        }

        DailyCash::create([
            'point_of_sale_id' => $pos->id,
            'user_id' => $user->id,
            'opening_balance' => 10000,
            'closing_balance' => null,
            'opened_at' => now()->setTime(8, 0),
            'closed_at' => null,
            'is_closed' => false,
            'notes' => null,
        ]);
    }
}

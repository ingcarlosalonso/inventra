<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Users & Roles
            'list_users',
            'create_edit_delete_users',
            'list_roles',
            'create_edit_delete_roles',

            // Clients & Suppliers
            'list_clients',
            'create_edit_delete_clients',
            'list_suppliers',
            'create_edit_delete_suppliers',

            // Products
            'list_products',
            'create_edit_delete_products',
            'bulk_update_product_price',
            'create_edit_delete_product_types',
            'create_edit_delete_presentation_types',
            'create_edit_delete_presentations',
            'create_edit_delete_product_movement_types',

            // Receptions
            'list_receptions',
            'create_edit_delete_receptions',

            // Sales
            'list_sales',
            'create_edit_delete_sales',
            'create_edit_delete_sale_states',
            'create_edit_delete_payment_methods',
            'create_edit_delete_points_of_sale',

            // Quotes
            'list_quotes',
            'create_edit_delete_quotes',

            // Orders
            'list_orders',
            'create_edit_delete_orders',
            'create_edit_delete_order_states',
            'create_edit_delete_couriers',

            // Daily Cash
            'list_daily_cashes',
            'enable_close_daily_cash',
            'create_edit_delete_cash_movement_types',

            // Reports
            'list_reports',
            'list_report_sales',
            'list_report_products',
            'list_report_payments',
            'list_report_inventory',
            'list_report_daily_cashes',
            'list_report_orders',
            'list_report_clients',
            'list_report_purchases',

            // Settings
            'create_edit_delete_currencies',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }
}

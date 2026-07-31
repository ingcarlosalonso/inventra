<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = [
            [
                'key' => 'orders_quotes',
                'name' => 'Pedidos y Presupuestos',
                'description' => 'Gestión de pedidos de entrega, couriers y presupuestos (cotizaciones) previos a la venta.',
                'sort_order' => 1,
            ],
            [
                'key' => 'ai_assistant',
                'name' => 'Asistente IA',
                'description' => 'Asistente conversacional para consultar datos del negocio y crear ventas, pedidos y presupuestos por chat.',
                'sort_order' => 2,
            ],
        ];

        foreach ($modules as $module) {
            Module::updateOrCreate(['key' => $module['key']], $module);
        }
    }
}

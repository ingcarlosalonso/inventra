<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class MigrateLegacyDataCommand extends Command
{
    protected $signature = 'tenants:migrate-legacy';

    protected $description = 'Migrate legacy StockAdministrator data into In-ventra tenants';

    // ── ID maps (legacy_id => new_id) ─────────────────────────────────────────
    private array $userMap = [];

    private array $productTypeMap = [];

    private array $productMap = [];

    private array $productPresentationMap = [];

    private array $adicionalPpMap = [];

    private array $compositeMap = [];

    private array $promotionMap = [];

    private array $pointOfSaleMap = [];

    private array $saleStateMap = [];

    private array $orderStateMap = [];

    private array $paymentMethodMap = [];

    private array $clientMap = [];

    private array $supplierMap = [];

    private array $courierMap = [];

    private array $dailyCashMap = [];

    private array $cashMovTypeMap = [];

    private array $productMovTypeMap = [];

    private array $saleMap = [];

    private array $receptionMap = [];

    private array $quoteMap = [];

    // ── Fixed IDs created during each tenant migration ─────────────────────────
    private int $arsId;

    private int $unitPresentationId;

    private int $additionalProductTypeId;

    private int $canceledSaleStateId;

    private int $firstUserId;

    public function handle(): void
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            warning('No se encontraron tenants.');

            return;
        }

        foreach ($tenants as $tenant) {
            $this->migrateTenant($tenant);
        }

        info('Proceso de migración finalizado.');
    }

    private function migrateTenant(Tenant $tenant): void
    {
        $domain = $tenant->domains()->first()?->domain ?? '';
        $subdomain = explode('.', $domain)[0] ?: Str::slug($tenant->name ?? 'tenant');
        $suggestedDb = "stockadministrator_{$subdomain}";

        $this->newLine();
        note("Tenant: [{$tenant->id}] ".($tenant->name ?? $domain));

        if (! confirm('¿Migrar este tenant?', default: false)) {
            info('Saltado.');

            return;
        }

        $legacyDb = text(
            label: 'Base de datos legacy',
            default: $suggestedDb,
            required: true,
        );

        config(['database.connections.legacy' => array_merge(
            config('database.connections.mysql'),
            ['database' => $legacyDb],
        )]);
        DB::purge('legacy');

        $tenant->makeCurrent();
        $this->resetMaps();

        try {
            DB::connection('tenant')->transaction(function () use ($legacyDb) {
                $this->line("  Migrando desde {$legacyDb}...");

                $this->migrateUsers();
                $this->createCurrencies();
                $this->createPresentation();
                $this->migrateProductTypes();
                $this->migrateProducts();
                $this->migrateAdicionals();
                $this->migrateBarcodes();
                $this->migrateCompositeProducts();
                $this->migratePromotions();
                $this->migratePointsOfSale();
                $this->migrateSaleStates();
                $this->migrateOrderStates();
                $this->migratePaymentMethods();
                $this->migrateClients();
                $this->migrateSuppliers();
                $this->migrateCouriers();
                $this->migrateDailyCashes();
                $this->migrateCashMovementTypes();
                $this->migrateProductMovementTypes();
                $this->migrateSales();
                $this->migrateSaleItems();
                $this->migrateQuotes();
                $this->migrateQuoteItems();
                $this->migrateReceptions();
                $this->migrateReceptionItems();
                $this->migratePayments();
                $this->migrateOrders();
                $this->migrateCashMovements();
                $this->migrateProductMovements();
            });

            info('  ✓ Tenant migrado correctamente.');
        } catch (\Throwable $e) {
            error('  ✗ Error: '.$e->getMessage());
            $this->line($e->getTraceAsString());
        } finally {
            Tenant::forgetCurrent();
            DB::purge('legacy');
        }
    }

    private function resetMaps(): void
    {
        $this->userMap = [];
        $this->productTypeMap = [];
        $this->productMap = [];
        $this->productPresentationMap = [];
        $this->adicionalPpMap = [];
        $this->compositeMap = [];
        $this->promotionMap = [];
        $this->pointOfSaleMap = [];
        $this->saleStateMap = [];
        $this->orderStateMap = [];
        $this->paymentMethodMap = [];
        $this->clientMap = [];
        $this->supplierMap = [];
        $this->courierMap = [];
        $this->dailyCashMap = [];
        $this->cashMovTypeMap = [];
        $this->productMovTypeMap = [];
        $this->saleMap = [];
        $this->receptionMap = [];
        $this->quoteMap = [];
    }

    // ── Migration steps ───────────────────────────────────────────────────────

    private function migrateUsers(): void
    {
        $rows = DB::connection('legacy')->table('users')->get();

        foreach ($rows as $row) {
            $existing = DB::connection('tenant')->table('users')
                ->where('email', $row->email)
                ->first();

            if ($existing) {
                $this->userMap[$row->id] = $existing->id;

                continue;
            }

            $newId = DB::connection('tenant')->table('users')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'name' => $row->name,
                'email' => $row->email,
                'password' => $row->password,
                'email_verified_at' => $row->email_verified_at,
                'remember_token' => $row->remember_token,
                'is_active' => true,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
                'deleted_at' => $row->deleted_at,
            ]);

            $this->userMap[$row->id] = $newId;
        }

        $this->firstUserId = DB::connection('tenant')->table('users')->value('id');
    }

    private function createCurrencies(): void
    {
        $this->arsId = DB::connection('tenant')->table('currencies')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'Peso Argentino',
            'symbol' => '$',
            'iso_code' => 'ARS',
            'is_default' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::connection('tenant')->table('currencies')->insert([
            'uuid' => (string) Str::uuid(),
            'name' => 'Dólar',
            'symbol' => 'US$',
            'iso_code' => 'USD',
            'is_default' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createPresentation(): void
    {
        $typeId = DB::connection('tenant')->table('presentation_types')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'Unidades',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->unitPresentationId = DB::connection('tenant')->table('presentations')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'presentation_type_id' => $typeId,
            'name' => 'Unidad',
            'abbreviation' => 'u.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function migrateProductTypes(): void
    {
        // Order: parents before children
        $rows = DB::connection('legacy')->table('tipo_productos')
            ->orderByRaw('ISNULL(tipo_producto_padre_id), id')
            ->get();

        foreach ($rows as $row) {
            $newId = DB::connection('tenant')->table('product_types')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'name' => $row->nombre,
                'parent_id' => $row->tipo_producto_padre_id
                    ? ($this->productTypeMap[$row->tipo_producto_padre_id] ?? null)
                    : null,
                'is_active' => true,
                'created_by' => $this->mapUser($row->created_by),
                'updated_by' => $this->mapUser($row->updated_by),
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
                'deleted_at' => $row->deleted_at,
            ]);

            $this->productTypeMap[$row->id] = $newId;
        }

        $this->additionalProductTypeId = DB::connection('tenant')->table('product_types')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'Adicionales',
            'parent_id' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function migrateProducts(): void
    {
        $rows = DB::connection('legacy')->table('productos')->get();

        foreach ($rows as $row) {
            $productId = DB::connection('tenant')->table('products')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'product_type_id' => $this->productTypeMap[$row->tipo_producto_id] ?? null,
                'currency_id' => $this->arsId,
                'name' => $row->nombre,
                'description' => null,
                'cost' => null,
                'is_active' => true,
                'created_by' => $this->mapUser($row->created_by),
                'updated_by' => $this->mapUser($row->updated_by),
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
                'deleted_at' => $row->deleted_at,
            ]);

            $ppId = DB::connection('tenant')->table('product_presentations')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'product_id' => $productId,
                'presentation_id' => $this->unitPresentationId,
                'price' => $row->precio_venta ?? 0,
                'stock' => $row->cantidad_disponible ?? 0,
                'min_stock' => $row->cantidad_minima_stock ?? 0,
                'is_active' => true,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);

            $this->productMap[$row->id] = $productId;
            $this->productPresentationMap[$row->id] = $ppId;
        }
    }

    private function migrateAdicionals(): void
    {
        $rows = DB::connection('legacy')->table('adicionals')->get();

        foreach ($rows as $row) {
            $productId = DB::connection('tenant')->table('products')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'product_type_id' => $this->additionalProductTypeId,
                'currency_id' => $this->arsId,
                'name' => $row->nombre,
                'description' => null,
                'cost' => null,
                'is_active' => true,
                'created_by' => $this->mapUser($row->created_by),
                'updated_by' => $this->mapUser($row->updated_by),
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
                'deleted_at' => $row->deleted_at,
            ]);

            $ppId = DB::connection('tenant')->table('product_presentations')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'product_id' => $productId,
                'presentation_id' => $this->unitPresentationId,
                'price' => $row->precio_venta ?? 0,
                'stock' => 0,
                'min_stock' => 0,
                'is_active' => true,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);

            $this->adicionalPpMap[$row->id] = $ppId;
        }
    }

    private function migrateBarcodes(): void
    {
        $rows = DB::connection('legacy')->table('codigo_barras')->whereNull('deleted_at')->get();

        foreach ($rows as $row) {
            if (! isset($this->productMap[$row->producto_id])) {
                continue;
            }

            DB::connection('tenant')->table('barcodes')->insert([
                'product_id' => $this->productMap[$row->producto_id],
                'barcode' => $row->codigo,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }
    }

    private function migrateCompositeProducts(): void
    {
        $rows = DB::connection('legacy')->table('producto_compuestos')->get();

        foreach ($rows as $row) {
            $newId = DB::connection('tenant')->table('composite_products')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'name' => $row->nombre,
                'code' => $row->codigo,
                'is_active' => true,
                'created_by' => $this->mapUser($row->created_by),
                'updated_by' => $this->mapUser($row->updated_by),
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
                'deleted_at' => $row->deleted_at,
            ]);

            $this->compositeMap[$row->id] = $newId;
        }

        $items = DB::connection('legacy')->table('cantidad_producto_compuestos')
            ->whereNull('deleted_at')
            ->get();

        foreach ($items as $item) {
            if (! isset($this->compositeMap[$item->producto_compuesto_id], $this->productMap[$item->producto_id])) {
                continue;
            }

            DB::connection('tenant')->table('composite_product_items')->insert([
                'composite_product_id' => $this->compositeMap[$item->producto_compuesto_id],
                'product_id' => $this->productMap[$item->producto_id],
                'quantity' => $item->cantidad,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ]);
        }
    }

    private function migratePromotions(): void
    {
        $rows = DB::connection('legacy')->table('promocions')->get();

        foreach ($rows as $row) {
            $newId = DB::connection('tenant')->table('promotions')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'name' => $row->nombre,
                'code' => $row->codigo,
                'sale_price' => $row->precio_venta,
                'is_active' => true,
                'created_by' => $this->mapUser($row->created_by),
                'updated_by' => $this->mapUser($row->updated_by),
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
                'deleted_at' => $row->deleted_at,
            ]);

            $this->promotionMap[$row->id] = $newId;
        }

        $items = DB::connection('legacy')->table('cantidad_promocions')
            ->whereNull('deleted_at')
            ->get();

        foreach ($items as $item) {
            if (! isset($this->promotionMap[$item->promocion_id], $this->productMap[$item->producto_id])) {
                continue;
            }

            DB::connection('tenant')->table('promotion_items')->insert([
                'promotion_id' => $this->promotionMap[$item->promocion_id],
                'product_id' => $this->productMap[$item->producto_id],
                'quantity' => $item->cantidad,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ]);
        }
    }

    private function migratePointsOfSale(): void
    {
        $rows = DB::connection('legacy')->table('punto_de_ventas')->get();

        foreach ($rows as $row) {
            $newId = DB::connection('tenant')->table('points_of_sale')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'name' => $row->nombre,
                'number' => (int) $row->numero,
                'is_active' => true,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
                'deleted_at' => $row->deleted_at,
            ]);

            $this->pointOfSaleMap[$row->id] = $newId;
        }
    }

    private function migrateSaleStates(): void
    {
        $rows = DB::connection('legacy')->table('estado_ventas')->get();

        foreach ($rows as $row) {
            $newId = DB::connection('tenant')->table('sale_states')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'name' => $row->nombre,
                'color' => null,
                'is_default' => (bool) $row->defecto,
                'is_final_state' => (bool) $row->estado_final,
                'is_active' => true,
                'sort_order' => 0,
                'created_by' => $this->mapUser($row->created_by),
                'updated_by' => $this->mapUser($row->updated_by),
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
                'deleted_at' => $row->deleted_at,
            ]);

            $this->saleStateMap[$row->id] = $newId;
        }

        $this->canceledSaleStateId = DB::connection('tenant')->table('sale_states')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'Cancelada',
            'color' => null,
            'is_default' => false,
            'is_final_state' => true,
            'is_active' => true,
            'sort_order' => 99,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function migrateOrderStates(): void
    {
        $rows = DB::connection('legacy')->table('estado_pedidos')->get();

        foreach ($rows as $row) {
            $newId = DB::connection('tenant')->table('order_states')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'name' => $row->nombre,
                'color' => null,
                'is_default' => (bool) $row->defecto,
                'is_final_state' => (bool) $row->estado_final,
                'is_active' => true,
                'sort_order' => 0,
                'created_by' => $this->mapUser($row->created_by),
                'updated_by' => $this->mapUser($row->updated_by),
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
                'deleted_at' => $row->deleted_at,
            ]);

            $this->orderStateMap[$row->id] = $newId;
        }
    }

    private function migratePaymentMethods(): void
    {
        $rows = DB::connection('legacy')->table('medio_pago')->get();

        foreach ($rows as $row) {
            $newId = DB::connection('tenant')->table('payment_methods')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'name' => $row->nombre,
                'is_active' => true,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
                'deleted_at' => $row->deleted_at,
            ]);

            $this->paymentMethodMap[$row->id] = $newId;
        }
    }

    private function migrateClients(): void
    {
        $rows = DB::connection('legacy')->table('clientes')->get();

        foreach ($rows as $row) {
            $newId = DB::connection('tenant')->table('clients')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'first_name' => $row->nombre,
                'last_name' => $row->apellido,
                'phone' => $row->telefono_celular ?? $row->telefono_fijo,
                'address' => $row->direccion,
                'notes' => $row->observaciones,
                'is_active' => true,
                'created_by' => $this->mapUser($row->created_by),
                'updated_by' => $this->mapUser($row->updated_by),
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
                'deleted_at' => $row->deleted_at,
            ]);

            $this->clientMap[$row->id] = $newId;
        }
    }

    private function migrateSuppliers(): void
    {
        $rows = DB::connection('legacy')->table('proveedors')->get();

        foreach ($rows as $row) {
            $newId = DB::connection('tenant')->table('suppliers')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'name' => $row->nombre,
                'contact_name' => null,
                'phone' => $row->telefono_celular ?? $row->telefono_fijo,
                'address' => $row->direccion,
                'is_active' => true,
                'created_by' => $this->mapUser($row->created_by),
                'updated_by' => $this->mapUser($row->updated_by),
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
                'deleted_at' => $row->deleted_at,
            ]);

            $this->supplierMap[$row->id] = $newId;
        }
    }

    private function migrateCouriers(): void
    {
        $rows = DB::connection('legacy')->table('cadetes')->get();

        foreach ($rows as $row) {
            $newId = DB::connection('tenant')->table('couriers')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'name' => $row->nombre,
                'phone' => $row->celular,
                'is_active' => true,
                'created_by' => $this->mapUser($row->created_by),
                'updated_by' => $this->mapUser($row->updated_by),
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
                'deleted_at' => $row->deleted_at,
            ]);

            $this->courierMap[$row->id] = $newId;
        }
    }

    private function migrateDailyCashes(): void
    {
        $rows = DB::connection('legacy')->table('caja_diarias')->get();

        foreach ($rows as $row) {
            $isClosed = $row->fecha_cierre !== null;

            $newId = DB::connection('tenant')->table('daily_cashes')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'point_of_sale_id' => isset($row->punto_venta_id) ? ($this->pointOfSaleMap[$row->punto_venta_id] ?? null) : null,
                'user_id' => $this->firstUserId,
                'opening_balance' => $row->saldo_inicial ?? 0,
                'closing_balance' => $isClosed ? ($row->saldo_actual ?? 0) : null,
                'opened_at' => $row->fecha_apertura,
                'closed_at' => $row->fecha_cierre,
                'is_closed' => $isClosed,
                'notes' => $row->observaciones,
                'created_by' => $this->mapUser($row->created_by),
                'updated_by' => $this->mapUser($row->updated_by),
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
                'deleted_at' => $row->deleted_at,
            ]);

            $this->dailyCashMap[$row->id] = $newId;
        }
    }

    private function migrateCashMovementTypes(): void
    {
        $rows = DB::connection('legacy')->table('tipo_movimiento_extra_cajas')->get();

        foreach ($rows as $row) {
            $newId = DB::connection('tenant')->table('cash_movement_types')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'name' => $row->nombre,
                'is_income' => $row->operacion === '+',
                'is_active' => true,
                'created_by' => $this->mapUser($row->created_by),
                'updated_by' => $this->mapUser($row->updated_by),
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
                'deleted_at' => $row->deleted_at,
            ]);

            $this->cashMovTypeMap[$row->id] = $newId;
        }
    }

    private function migrateProductMovementTypes(): void
    {
        $rows = DB::connection('legacy')->table('tipo_movimiento_extra_productos')->get();

        foreach ($rows as $row) {
            $newId = DB::connection('tenant')->table('product_movement_types')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'name' => $row->nombre,
                'is_income' => $row->operacion === '+',
                'is_active' => true,
                'created_by' => $this->mapUser($row->created_by),
                'updated_by' => $this->mapUser($row->updated_by),
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
                'deleted_at' => $row->deleted_at,
            ]);

            $this->productMovTypeMap[$row->id] = $newId;
        }
    }

    private function migrateSales(): void
    {
        $rows = DB::connection('legacy')->table('ventas')->get();

        $subtotals = DB::connection('legacy')->table('detalle_ventas')
            ->selectRaw('venta_id, SUM(cantidad * precio_venta) as subtotal')
            ->whereNull('deleted_at')
            ->groupBy('venta_id')
            ->pluck('subtotal', 'venta_id');

        foreach ($rows as $row) {
            $saleStateId = $row->cancelado
                ? $this->canceledSaleStateId
                : ($this->saleStateMap[$row->estado_id] ?? null);

            $subtotal = (float) ($subtotals[$row->id] ?? 0);
            [$discountType, $discountValue, $discountAmount] = $this->resolveDiscount(
                (float) ($row->porcentaje_descuento ?? 0),
                (float) ($row->monto_descuento ?? 0),
                $subtotal,
            );

            $notes = $row->numero_factura ? "Factura: {$row->numero_factura}" : null;

            $newId = DB::connection('tenant')->table('sales')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'client_id' => isset($row->cliente_id) ? ($this->clientMap[$row->cliente_id] ?? null) : null,
                'point_of_sale_id' => $this->pointOfSaleMap[$row->punto_de_venta_id] ?? null,
                'sale_state_id' => $saleStateId,
                'currency_id' => $this->arsId,
                'user_id' => $this->mapUser($row->created_by),
                'subtotal' => $subtotal,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'discount_amount' => $discountAmount,
                'total' => round($subtotal - $discountAmount, 2),
                'notes' => $notes,
                'created_by' => $this->mapUser($row->created_by),
                'updated_by' => $this->mapUser($row->updated_by),
                'created_at' => $row->fecha_venta,
                'updated_at' => $row->updated_at,
                'deleted_at' => $row->deleted_at,
            ]);

            $this->saleMap[$row->id] = $newId;
        }
    }

    private function migrateSaleItems(): void
    {
        $rows = DB::connection('legacy')->table('detalle_ventas')->get();

        foreach ($rows as $row) {
            if (! isset($this->saleMap[$row->venta_id])) {
                continue;
            }

            [$saleableType, $saleableId, $ppId, $description] = $this->resolveSaleable($row);

            if ($saleableType === null) {
                continue;
            }

            $total = round((float) $row->cantidad * (float) $row->precio_venta, 2);

            DB::connection('tenant')->table('sale_items')->insert([
                'uuid' => (string) Str::uuid(),
                'sale_id' => $this->saleMap[$row->venta_id],
                'product_presentation_id' => $ppId,
                'saleable_type' => $saleableType,
                'saleable_id' => $saleableId,
                'description' => $description,
                'quantity' => $row->cantidad,
                'unit_price' => $row->precio_venta,
                'discount_type' => null,
                'discount_value' => 0,
                'discount_amount' => 0,
                'total' => $total,
                'created_by' => $this->mapUser($row->created_by),
                'updated_by' => $this->mapUser($row->updated_by),
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
                'deleted_at' => $row->deleted_at,
            ]);
        }
    }

    private function migrateQuotes(): void
    {
        $rows = DB::connection('legacy')->table('presupuestos')->get();

        $subtotals = DB::connection('legacy')->table('detalle_presupuestos')
            ->selectRaw('presupuesto_id, SUM(cantidad * precio_presupuesto) as subtotal')
            ->whereNull('deleted_at')
            ->groupBy('presupuesto_id')
            ->pluck('subtotal', 'presupuesto_id');

        foreach ($rows as $row) {
            $subtotal = (float) ($subtotals[$row->id] ?? 0);
            [$discountType, $discountValue, $discountAmount] = $this->resolveDiscount(
                (float) ($row->porcentaje_descuento ?? 0),
                (float) ($row->monto_descuento ?? 0),
                $subtotal,
            );

            $newId = DB::connection('tenant')->table('quotes')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'client_id' => isset($row->cliente_id) ? ($this->clientMap[$row->cliente_id] ?? null) : null,
                'user_id' => $this->firstUserId,
                'currency_id' => $this->arsId,
                'sale_id' => isset($row->venta_id) ? ($this->saleMap[$row->venta_id] ?? null) : null,
                'subtotal' => $subtotal,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'discount_amount' => $discountAmount,
                'total' => round($subtotal - $discountAmount, 2),
                'notes' => $row->observaciones,
                'starts_at' => $row->fecha,
                'expires_at' => null,
                'created_by' => $this->mapUser($row->created_by),
                'updated_by' => $this->mapUser($row->updated_by),
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
                'deleted_at' => $row->deleted_at,
            ]);

            $this->quoteMap[$row->id] = $newId;
        }
    }

    private function migrateQuoteItems(): void
    {
        $rows = DB::connection('legacy')->table('detalle_presupuestos')->get();

        foreach ($rows as $row) {
            if (! isset($this->quoteMap[$row->presupuesto_id])) {
                continue;
            }

            [$saleableType, $saleableId, $ppId, $description] = $this->resolveSaleable((object) [
                'producto_id' => $row->producto_id,
                'adicional_id' => $row->adicional_id,
                'producto_compuesto_id' => $row->producto_compuesto_id,
                'promocion_id' => $row->promocion_id,
            ]);

            if ($saleableType === null) {
                continue;
            }

            $total = round((float) $row->cantidad * (float) $row->precio_presupuesto, 2);

            DB::connection('tenant')->table('quote_items')->insert([
                'uuid' => (string) Str::uuid(),
                'quote_id' => $this->quoteMap[$row->presupuesto_id],
                'product_presentation_id' => $ppId,
                'saleable_type' => $saleableType,
                'saleable_id' => $saleableId,
                'description' => $description,
                'quantity' => $row->cantidad,
                'unit_price' => $row->precio_presupuesto,
                'discount_type' => null,
                'discount_value' => 0,
                'discount_amount' => 0,
                'total' => $total,
                'created_by' => $this->mapUser($row->created_by),
                'updated_by' => $this->mapUser($row->updated_by),
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
                'deleted_at' => $row->deleted_at,
            ]);
        }
    }

    private function migrateReceptions(): void
    {
        $rows = DB::connection('legacy')->table('recepcions')->get();

        foreach ($rows as $row) {
            $newId = DB::connection('tenant')->table('receptions')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'supplier_id' => $this->supplierMap[$row->proveedor_id] ?? null,
                'daily_cash_id' => isset($row->caja_diaria_id) ? ($this->dailyCashMap[$row->caja_diaria_id] ?? null) : null,
                'user_id' => $this->firstUserId,
                'supplier_invoice' => $row->numero_factura_proveedor,
                'total' => $row->monto_caja_utilizado ?? 0,
                'notes' => $row->observaciones,
                'received_at' => $row->fecha,
                'created_by' => $this->mapUser($row->created_by),
                'updated_by' => $this->mapUser($row->updated_by),
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
                'deleted_at' => $row->deleted_at,
            ]);

            $this->receptionMap[$row->id] = $newId;
        }
    }

    private function migrateReceptionItems(): void
    {
        $rows = DB::connection('legacy')->table('ingreso_productos')->whereNull('deleted_at')->get();

        foreach ($rows as $row) {
            if (! isset($this->receptionMap[$row->recepcion_id], $this->productPresentationMap[$row->producto_id])) {
                continue;
            }

            DB::connection('tenant')->table('reception_items')->insert([
                'uuid' => (string) Str::uuid(),
                'reception_id' => $this->receptionMap[$row->recepcion_id],
                'product_presentation_id' => $this->productPresentationMap[$row->producto_id],
                'quantity' => $row->cantidad,
                'unit_cost' => $row->precio_compra_unidad,
                'total' => round((float) $row->cantidad * (float) $row->precio_compra_unidad, 2),
                'created_by' => $this->mapUser($row->created_by),
                'updated_by' => $this->mapUser($row->updated_by),
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }
    }

    private function migratePayments(): void
    {
        $rows = DB::connection('legacy')->table('pagos')->get();

        foreach ($rows as $row) {
            if (! isset($this->saleMap[$row->venta_id])) {
                continue;
            }

            DB::connection('tenant')->table('payments')->insert([
                'uuid' => (string) Str::uuid(),
                'payable_type' => 'sale',
                'payable_id' => $this->saleMap[$row->venta_id],
                'payment_method_id' => isset($row->medio_pago_id) ? ($this->paymentMethodMap[$row->medio_pago_id] ?? null) : null,
                'currency_id' => $this->arsId,
                'daily_cash_id' => $this->dailyCashMap[$row->caja_id] ?? null,
                'amount' => $row->monto,
                'exchange_rate' => null,
                'notes' => null,
                'created_by' => $this->mapUser($row->created_by),
                'updated_by' => $this->mapUser($row->updated_by),
                'created_at' => $row->fecha_pago,
                'updated_at' => $row->updated_at,
                'deleted_at' => $row->deleted_at,
            ]);
        }
    }

    private function migrateOrders(): void
    {
        $rows = DB::connection('legacy')->table('pedidos')->get();

        $legacySales = DB::connection('legacy')->table('ventas')
            ->whereIn('id', $rows->pluck('venta_id')->unique()->filter()->values())
            ->get()
            ->keyBy('id');

        foreach ($rows as $row) {
            if (! isset($this->saleMap[$row->venta_id])) {
                continue;
            }

            $legacySale = $legacySales[$row->venta_id] ?? null;
            $pointOfSaleId = $legacySale ? ($this->pointOfSaleMap[$legacySale->punto_de_venta_id] ?? null) : null;
            $clientId = ($legacySale && $legacySale->cliente_id) ? ($this->clientMap[$legacySale->cliente_id] ?? null) : null;

            $newSaleId = $this->saleMap[$row->venta_id];
            $subtotal = DB::connection('tenant')->table('sale_items')
                ->where('sale_id', $newSaleId)
                ->whereNull('deleted_at')
                ->sum('total');

            $scheduledAt = ($row->fecha_entrega && $row->hora_entrega)
                ? $row->fecha_entrega.' '.$row->hora_entrega
                : null;

            $newId = DB::connection('tenant')->table('orders')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'sale_id' => $newSaleId,
                'client_id' => $clientId,
                'courier_id' => isset($row->cadete_id) ? ($this->courierMap[$row->cadete_id] ?? null) : null,
                'order_state_id' => $this->orderStateMap[$row->estado_id] ?? null,
                'user_id' => $this->mapUser($row->created_by),
                'point_of_sale_id' => $pointOfSaleId,
                'currency_id' => $this->arsId,
                'address' => $row->direccion,
                'notes' => $row->observaciones,
                'requires_delivery' => true,
                'delivery_date' => $row->fecha_entrega,
                'scheduled_at' => $scheduledAt,
                'subtotal' => $subtotal,
                'discount_type' => null,
                'discount_value' => 0,
                'discount_amount' => 0,
                'total' => $subtotal,
                'created_by' => $this->mapUser($row->created_by),
                'updated_by' => $this->mapUser($row->updated_by),
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
                'deleted_at' => $row->deleted_at,
            ]);

            // Copy sale_items into order_items
            $saleItems = DB::connection('tenant')->table('sale_items')
                ->where('sale_id', $newSaleId)
                ->whereNull('deleted_at')
                ->get();

            foreach ($saleItems as $si) {
                DB::connection('tenant')->table('order_items')->insert([
                    'uuid' => (string) Str::uuid(),
                    'order_id' => $newId,
                    'product_presentation_id' => $si->product_presentation_id,
                    'saleable_type' => $si->saleable_type,
                    'saleable_id' => $si->saleable_id,
                    'description' => $si->description,
                    'quantity' => $si->quantity,
                    'unit_price' => $si->unit_price,
                    'discount_type' => null,
                    'discount_value' => 0,
                    'discount_amount' => 0,
                    'total' => $si->total,
                    'created_at' => $si->created_at,
                    'updated_at' => $si->updated_at,
                ]);
            }
        }
    }

    private function migrateCashMovements(): void
    {
        $rows = DB::connection('legacy')->table('movimiento_extra_cajas')
            ->whereNull('deleted_at')
            ->get();

        foreach ($rows as $row) {
            if (! isset($this->dailyCashMap[$row->caja_diaria_id])) {
                continue;
            }

            DB::connection('tenant')->table('cash_movements')->insert([
                'daily_cash_id' => $this->dailyCashMap[$row->caja_diaria_id],
                'cash_movement_type_id' => $this->cashMovTypeMap[$row->tipo_movimiento_extra_caja_id] ?? null,
                'user_id' => $this->firstUserId,
                'reception_id' => null,
                'amount' => $row->monto,
                'notes' => $row->descripcion,
                'created_at' => $row->fecha,
                'updated_at' => $row->updated_at,
            ]);
        }
    }

    private function migrateProductMovements(): void
    {
        $rows = DB::connection('legacy')->table('movimiento_extra_productos')
            ->whereNull('deleted_at')
            ->get();

        foreach ($rows as $row) {
            if (! isset($this->productMap[$row->producto_id])) {
                continue;
            }

            DB::connection('tenant')->table('product_movements')->insert([
                'product_id' => $this->productMap[$row->producto_id],
                'product_movement_type_id' => $this->productMovTypeMap[$row->tipo_movimiento_extra_prod_id] ?? null,
                'product_presentation_id' => $this->productPresentationMap[$row->producto_id] ?? null,
                'user_id' => $this->firstUserId,
                'quantity' => $row->cantidad,
                'notes' => $row->descripcion,
                'created_at' => $row->fecha,
                'updated_at' => $row->updated_at,
            ]);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function mapUser(?int $oldId): ?int
    {
        if ($oldId === null) {
            return null;
        }

        return $this->userMap[$oldId] ?? $this->firstUserId;
    }

    /**
     * @return array{string|null, int|null, float, string}
     */
    private function resolveDiscount(float $pct, float $fixed, float $subtotal): array
    {
        if ($pct > 0) {
            return ['percentage', $pct, round($subtotal * $pct / 100, 2)];
        }

        if ($fixed > 0) {
            return ['fixed', $fixed, $fixed];
        }

        return [null, 0, 0];
    }

    /**
     * @return array{string|null, int|null, int|null, string}
     */
    private function resolveSaleable(object $row): array
    {
        if (! empty($row->producto_id) && isset($this->productPresentationMap[$row->producto_id])) {
            $ppId = $this->productPresentationMap[$row->producto_id];
            $name = DB::connection('legacy')->table('productos')
                ->where('id', $row->producto_id)->value('nombre') ?? 'Producto';

            return ['product_presentation', $ppId, $ppId, $name];
        }

        if (! empty($row->adicional_id) && isset($this->adicionalPpMap[$row->adicional_id])) {
            $ppId = $this->adicionalPpMap[$row->adicional_id];
            $name = DB::connection('legacy')->table('adicionals')
                ->where('id', $row->adicional_id)->value('nombre') ?? 'Adicional';

            return ['product_presentation', $ppId, $ppId, $name];
        }

        if (! empty($row->producto_compuesto_id) && isset($this->compositeMap[$row->producto_compuesto_id])) {
            $newId = $this->compositeMap[$row->producto_compuesto_id];
            $name = DB::connection('legacy')->table('producto_compuestos')
                ->where('id', $row->producto_compuesto_id)->value('nombre') ?? 'Compuesto';

            return ['composite_product', $newId, null, $name];
        }

        if (! empty($row->promocion_id) && isset($this->promotionMap[$row->promocion_id])) {
            $newId = $this->promotionMap[$row->promocion_id];
            $name = DB::connection('legacy')->table('promocions')
                ->where('id', $row->promocion_id)->value('nombre') ?? 'Promoción';

            return ['promotion', $newId, null, $name];
        }

        return [null, null, null, ''];
    }
}

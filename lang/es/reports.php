<?php

return [
    'title' => 'Reportes',
    'subtitle' => 'Analizá el rendimiento de tu negocio',

    // Report names
    'sales_title' => 'Ventas',
    'sales_desc' => 'Evolución de facturación, tickets y descuentos por período.',
    'products_title' => 'Productos',
    'products_desc' => 'Los productos más vendidos por cantidad e ingresos.',
    'payments_title' => 'Cobros',
    'payments_desc' => 'Pagos recibidos agrupados por método de pago.',
    'inventory_title' => 'Inventario',
    'inventory_desc' => 'Estado actual del stock con alertas de mínimos.',
    'daily_cashes_title' => 'Cajas',
    'daily_cashes_desc' => 'Resumen de aperturas, cobros y movimientos de caja.',
    'orders_title' => 'Pedidos',
    'orders_desc' => 'Pedidos por estado, repartidor y período.',
    'clients_title' => 'Clientes',
    'clients_desc' => 'Clientes ordenados por volumen de compras.',
    'purchases_title' => 'Compras',
    'purchases_desc' => 'Recepciones de mercadería por proveedor y período.',

    // Actions
    'export_excel' => 'Exportar Excel',
    'view_report' => 'Ver reporte',
    'apply' => 'Aplicar',
    'clear' => 'Limpiar',
    'refresh' => 'Actualizar',

    // Filters
    'filters' => 'Filtros',
    'date_from' => 'Desde',
    'date_to' => 'Hasta',
    'all_clients' => 'Todos los clientes',
    'all_pos' => 'Todos los puntos de venta',
    'all_states' => 'Todos los estados',
    'all_methods' => 'Todos los métodos',
    'all_categories' => 'Todas las categorías',
    'all_couriers' => 'Todos los repartidores',
    'all_suppliers' => 'Todos los proveedores',
    'all_stock' => 'Todo el stock',
    'stock_low' => 'Stock bajo',
    'stock_out' => 'Sin stock',
    'stock_ok' => 'Stock OK',
    'stock_status' => 'Estado de stock',
    'only_closed' => 'Solo cerradas',

    // KPIs - generic
    'no_data' => 'Sin datos para el período seleccionado.',
    'total_records' => 'registros',

    // KPIs - sales
    'total_revenue' => 'Facturación total',
    'total_collected' => 'Total cobrado',
    'total_discounts' => 'Descuentos',
    'avg_ticket' => 'Ticket promedio',
    'sales_count' => 'Ventas',
    'revenue_chart' => 'Evolución de facturación',

    // KPIs - products
    'total_units' => 'Unidades vendidas',
    'unique_products' => 'Productos distintos',
    'top_product' => 'Producto estrella',
    'top_products_chart' => 'Top 10 productos por ingresos',
    'product_name' => 'Producto',
    'product_type' => 'Categoría',
    'units_sold' => 'Unidades',
    'avg_price' => 'Precio prom.',
    'sale_count' => 'Ventas',

    // KPIs - payments
    'total_amount' => 'Total cobrado',
    'payments_count' => 'Pagos',
    'top_method' => 'Método principal',
    'avg_amount' => 'Pago promedio',
    'by_method' => 'Por método de pago',
    'payment_method' => 'Método',
    'amount' => 'Monto',
    'payments_chart' => 'Cobros diarios',

    // KPIs - inventory
    'total_items' => 'Presentaciones activas',
    'low_stock_count' => 'Stock bajo',
    'out_of_stock_count' => 'Sin stock',
    'ok_count' => 'Stock OK',
    'stock_ok_label' => 'OK',
    'stock_low_label' => 'Bajo',
    'stock_out_label' => 'Sin stock',

    // KPIs - daily cashes
    'cashes_count' => 'Cajas',
    'closed_count' => 'Cerradas',
    'total_income_extra' => 'Ingresos extras',
    'total_expense_extra' => 'Egresos extras',
    'opening_balance' => 'Saldo inicial',
    'collected' => 'Cobros',
    'closing_balance' => 'Saldo cierre',
    'opened_at' => 'Apertura',
    'closed_at' => 'Cierre',
    'open' => 'Abierta',
    'closed' => 'Cerrada',

    // KPIs - orders
    'orders_count' => 'Pedidos',
    'by_state' => 'Por estado',
    'courier' => 'Repartidor',
    'delivery_date' => 'Fecha entrega',
    'states_count' => 'Estados distintos',

    // KPIs - clients
    'active_clients' => 'Clientes activos',
    'total_clients' => 'Clientes totales',
    'total_sales' => 'Ventas en período',
    'last_sale' => 'Última compra',
    'top_clients_chart' => 'Top 10 clientes',

    // KPIs - purchases
    'total_cost' => 'Costo total',
    'receptions_count' => 'Recepciones',
    'unique_suppliers' => 'Proveedores',
    'avg_reception' => 'Compra promedio',
    'by_supplier' => 'Por proveedor',
    'supplier' => 'Proveedor',
    'received_at' => 'Fecha recepción',

    // Table headers
    'date' => 'Fecha',
    'client' => 'Cliente',
    'point_of_sale' => 'Punto de venta',
    'state' => 'Estado',
    'subtotal' => 'Subtotal',
    'discount' => 'Descuento',
    'total' => 'Total',
    'user' => 'Usuario',
    'notes' => 'Notas',
    'presentation' => 'Presentación',
    'stock' => 'Stock',
    'min_stock' => 'Mín.',
];

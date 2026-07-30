<?php

return [
    // Users & Roles
    'list_users' => [
        'label' => 'Ver usuarios',
        'description' => 'Acceder al listado de usuarios del sistema',
    ],
    'create_edit_delete_users' => [
        'label' => 'Gestionar usuarios',
        'description' => 'Crear, editar, activar/desactivar y eliminar usuarios',
    ],
    'list_roles' => [
        'label' => 'Ver roles',
        'description' => 'Acceder al listado de roles y sus permisos asignados',
    ],
    'create_edit_delete_roles' => [
        'label' => 'Gestionar roles',
        'description' => 'Crear, editar y eliminar roles, y asignarles permisos',
    ],

    // Clients & Suppliers
    'list_clients' => [
        'label' => 'Ver clientes',
        'description' => 'Acceder al listado de clientes',
    ],
    'create_edit_delete_clients' => [
        'label' => 'Gestionar clientes',
        'description' => 'Crear, editar y eliminar clientes',
    ],
    'list_suppliers' => [
        'label' => 'Ver proveedores',
        'description' => 'Acceder al listado de proveedores',
    ],
    'create_edit_delete_suppliers' => [
        'label' => 'Gestionar proveedores',
        'description' => 'Crear, editar y eliminar proveedores',
    ],

    // Products
    'list_products' => [
        'label' => 'Ver productos',
        'description' => 'Acceder al catálogo de productos, compuestos, promociones y movimientos',
    ],
    'create_edit_delete_products' => [
        'label' => 'Gestionar productos',
        'description' => 'Crear, editar, eliminar e importar productos, compuestos, promociones y movimientos de stock',
    ],
    'bulk_update_product_price' => [
        'label' => 'Actualización masiva de precios',
        'description' => 'Actualizar el precio de múltiples productos a la vez',
    ],
    'create_edit_delete_product_types' => [
        'label' => 'Gestionar tipos de producto',
        'description' => 'Crear, editar y eliminar las categorías del catálogo',
    ],
    'create_edit_delete_presentation_types' => [
        'label' => 'Gestionar tipos de presentación',
        'description' => 'Crear, editar y eliminar las unidades de medida base (kg, litro, unidad, etc.)',
    ],
    'create_edit_delete_presentations' => [
        'label' => 'Gestionar presentaciones',
        'description' => 'Crear, editar y eliminar las presentaciones asignables a productos',
    ],
    'create_edit_delete_product_movement_types' => [
        'label' => 'Gestionar tipos de movimiento de producto',
        'description' => 'Crear, editar y eliminar los tipos de ajuste manual de stock',
    ],

    // Receptions
    'list_receptions' => [
        'label' => 'Ver ingresos de stock',
        'description' => 'Acceder al historial de recepciones de mercadería',
    ],
    'create_edit_delete_receptions' => [
        'label' => 'Gestionar ingresos de stock',
        'description' => 'Registrar y eliminar ingresos de mercadería de proveedores',
    ],

    // Sales
    'list_sales' => [
        'label' => 'Ver ventas',
        'description' => 'Acceder al listado de ventas y pagos pendientes',
    ],
    'create_edit_delete_sales' => [
        'label' => 'Gestionar ventas',
        'description' => 'Crear, editar y eliminar ventas, y registrar pagos',
    ],
    'create_edit_delete_sale_states' => [
        'label' => 'Gestionar estados de venta',
        'description' => 'Crear, editar y eliminar los estados del ciclo de vida de las ventas',
    ],
    'create_edit_delete_payment_methods' => [
        'label' => 'Gestionar métodos de pago',
        'description' => 'Crear, editar y eliminar los medios de pago disponibles',
    ],
    'create_edit_delete_points_of_sale' => [
        'label' => 'Gestionar puntos de venta',
        'description' => 'Crear, editar y eliminar sucursales y puntos de venta',
    ],

    // Quotes
    'list_quotes' => [
        'label' => 'Ver presupuestos',
        'description' => 'Acceder al listado de presupuestos',
    ],
    'create_edit_delete_quotes' => [
        'label' => 'Gestionar presupuestos',
        'description' => 'Crear, eliminar y convertir presupuestos en ventas o pedidos',
    ],

    // Orders
    'list_orders' => [
        'label' => 'Ver pedidos',
        'description' => 'Acceder al listado de pedidos y su seguimiento',
    ],
    'create_edit_delete_orders' => [
        'label' => 'Gestionar pedidos',
        'description' => 'Crear, eliminar y cambiar el estado de los pedidos',
    ],
    'create_edit_delete_order_states' => [
        'label' => 'Gestionar estados de pedido',
        'description' => 'Crear, editar y eliminar los estados del ciclo de vida de los pedidos',
    ],
    'create_edit_delete_couriers' => [
        'label' => 'Gestionar repartidores',
        'description' => 'Crear, editar y eliminar los repartidores / couriers',
    ],

    // Daily Cash
    'list_daily_cashes' => [
        'label' => 'Ver cajas diarias',
        'description' => 'Acceder al historial de cajas diarias y sus movimientos',
    ],
    'enable_close_daily_cash' => [
        'label' => 'Abrir y cerrar caja',
        'description' => 'Abrir, cerrar y registrar movimientos en la caja diaria',
    ],
    'create_edit_delete_cash_movement_types' => [
        'label' => 'Gestionar tipos de movimiento de caja',
        'description' => 'Crear, editar y eliminar las categorías de movimientos de caja (ingresos y egresos)',
    ],

    // Reports
    'list_report_sales' => [
        'label' => 'Reporte de ventas',
        'description' => 'Ver el reporte de ventas por período, cliente y estado',
    ],
    'list_report_products' => [
        'label' => 'Reporte de productos',
        'description' => 'Ver el reporte de movimientos y rotación de productos',
    ],
    'list_report_payments' => [
        'label' => 'Reporte de pagos',
        'description' => 'Ver el reporte de pagos recibidos por método y período',
    ],
    'list_report_inventory' => [
        'label' => 'Reporte de inventario',
        'description' => 'Ver el reporte de stock actual y productos bajo mínimo',
    ],
    'list_report_daily_cashes' => [
        'label' => 'Reporte de cajas',
        'description' => 'Ver el reporte de cajas diarias y sus balances',
    ],
    'list_report_orders' => [
        'label' => 'Reporte de pedidos',
        'description' => 'Ver el reporte de pedidos por estado, courier y período',
    ],
    'list_report_clients' => [
        'label' => 'Reporte de clientes',
        'description' => 'Ver el reporte de actividad y compras por cliente',
    ],
    'list_report_purchases' => [
        'label' => 'Reporte de compras',
        'description' => 'Ver el reporte de recepciones y compras a proveedores',
    ],

    // Settings
    'create_edit_delete_currencies' => [
        'label' => 'Gestionar monedas',
        'description' => 'Crear, editar y eliminar las monedas disponibles en el sistema',
    ],
    'manage_customization' => [
        'label' => 'Personalización del sistema',
        'description' => 'Cambiar el logo, colores y fuente de la empresa',
    ],
];

<?php

return [
    // Users & Roles
    'list_users' => [
        'label' => 'View users',
        'description' => 'Access the list of system users',
    ],
    'create_edit_delete_users' => [
        'label' => 'Manage users',
        'description' => 'Create, edit, activate/deactivate and delete users',
    ],
    'list_roles' => [
        'label' => 'View roles',
        'description' => 'Access the list of roles and their assigned permissions',
    ],
    'create_edit_delete_roles' => [
        'label' => 'Manage roles',
        'description' => 'Create, edit and delete roles, and assign permissions to them',
    ],

    // Clients & Suppliers
    'list_clients' => [
        'label' => 'View clients',
        'description' => 'Access the client list',
    ],
    'create_edit_delete_clients' => [
        'label' => 'Manage clients',
        'description' => 'Create, edit and delete clients',
    ],
    'list_suppliers' => [
        'label' => 'View suppliers',
        'description' => 'Access the supplier list',
    ],
    'create_edit_delete_suppliers' => [
        'label' => 'Manage suppliers',
        'description' => 'Create, edit and delete suppliers',
    ],

    // Products
    'list_products' => [
        'label' => 'View products',
        'description' => 'Access the product catalogue, composite products, promotions and movements',
    ],
    'create_edit_delete_products' => [
        'label' => 'Manage products',
        'description' => 'Create, edit, delete and import products, composite products, promotions and stock movements',
    ],
    'bulk_update_product_price' => [
        'label' => 'Bulk price update',
        'description' => 'Update the price of multiple products at once',
    ],
    'create_edit_delete_product_types' => [
        'label' => 'Manage product types',
        'description' => 'Create, edit and delete catalogue categories',
    ],
    'create_edit_delete_presentation_types' => [
        'label' => 'Manage presentation types',
        'description' => 'Create, edit and delete base units of measure (kg, litre, unit, etc.)',
    ],
    'create_edit_delete_presentations' => [
        'label' => 'Manage presentations',
        'description' => 'Create, edit and delete presentations assignable to products',
    ],
    'create_edit_delete_product_movement_types' => [
        'label' => 'Manage product movement types',
        'description' => 'Create, edit and delete manual stock adjustment types',
    ],

    // Receptions
    'list_receptions' => [
        'label' => 'View stock entries',
        'description' => 'Access the merchandise reception history',
    ],
    'create_edit_delete_receptions' => [
        'label' => 'Manage stock entries',
        'description' => 'Record and delete merchandise entries from suppliers',
    ],

    // Sales
    'list_sales' => [
        'label' => 'View sales',
        'description' => 'Access the sales list and pending payments',
    ],
    'create_edit_delete_sales' => [
        'label' => 'Manage sales',
        'description' => 'Create, edit and delete sales, and register payments',
    ],
    'create_edit_delete_sale_states' => [
        'label' => 'Manage sale states',
        'description' => 'Create, edit and delete the states of the sales lifecycle',
    ],
    'create_edit_delete_payment_methods' => [
        'label' => 'Manage payment methods',
        'description' => 'Create, edit and delete available payment methods',
    ],
    'create_edit_delete_points_of_sale' => [
        'label' => 'Manage points of sale',
        'description' => 'Create, edit and delete branches and points of sale',
    ],

    // Quotes
    'list_quotes' => [
        'label' => 'View quotes',
        'description' => 'Access the quotes list',
    ],
    'create_edit_delete_quotes' => [
        'label' => 'Manage quotes',
        'description' => 'Create, delete and convert quotes into sales or orders',
    ],

    // Orders
    'list_orders' => [
        'label' => 'View orders',
        'description' => 'Access the orders list and their tracking',
    ],
    'create_edit_delete_orders' => [
        'label' => 'Manage orders',
        'description' => 'Create, delete and change the state of orders',
    ],
    'create_edit_delete_order_states' => [
        'label' => 'Manage order states',
        'description' => 'Create, edit and delete the states of the orders lifecycle',
    ],
    'create_edit_delete_couriers' => [
        'label' => 'Manage couriers',
        'description' => 'Create, edit and delete delivery couriers',
    ],

    // Daily Cash
    'list_daily_cashes' => [
        'label' => 'View daily cash',
        'description' => 'Access the daily cash history and its movements',
    ],
    'enable_close_daily_cash' => [
        'label' => 'Open and close cash register',
        'description' => 'Open, close and register movements in the daily cash register',
    ],
    'create_edit_delete_cash_movement_types' => [
        'label' => 'Manage cash movement types',
        'description' => 'Create, edit and delete cash movement categories (income and expenses)',
    ],

    // Reports
    'list_report_sales' => [
        'label' => 'Sales report',
        'description' => 'View the sales report by period, client and status',
    ],
    'list_report_products' => [
        'label' => 'Products report',
        'description' => 'View the product movements and turnover report',
    ],
    'list_report_payments' => [
        'label' => 'Payments report',
        'description' => 'View the payments received by method and period',
    ],
    'list_report_inventory' => [
        'label' => 'Inventory report',
        'description' => 'View the current stock and below-minimum products report',
    ],
    'list_report_daily_cashes' => [
        'label' => 'Cash registers report',
        'description' => 'View the daily cash registers and their balances report',
    ],
    'list_report_orders' => [
        'label' => 'Orders report',
        'description' => 'View the orders report by status, courier and period',
    ],
    'list_report_clients' => [
        'label' => 'Clients report',
        'description' => 'View the client activity and purchases report',
    ],
    'list_report_purchases' => [
        'label' => 'Purchases report',
        'description' => 'View the receptions and supplier purchases report',
    ],

    // Settings
    'create_edit_delete_currencies' => [
        'label' => 'Manage currencies',
        'description' => 'Create, edit and delete the currencies available in the system',
    ],
    'manage_customization' => [
        'label' => 'System customization',
        'description' => "Change the company's logo, colours and font",
    ],
];

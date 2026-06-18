# Roles & Permissions

The roles and permissions system lets you control exactly what each user can do in In-ventra. A **role** is a set of permissions, and each user can have one or more roles assigned.

Path: **Settings → Roles & Permissions**

---

## What are roles?

A role is an access profile that groups permissions. Instead of configuring permissions one by one per user, you create roles with the required access and assign them to the appropriate users.

Typical role examples:
- **Administrator**: full access to the system.
- **Salesperson**: can create and view sales, clients, and quotes, but cannot access settings or financial reports.
- **Warehouse**: manages stock, receptions, and product movements, without access to sales or cash.

---

## Create a role

1. Go to **Settings → Roles & Permissions**.
2. Click **New role**.
3. Enter the role name (e.g. "Salesperson", "Supervisor").
4. Select the permissions this role will have (see list below).
5. Save.

---

## Edit a role

1. In the roles list, click the edit icon (pencil) for the corresponding role.
2. Modify the name or selected permissions.
3. Save changes.

> **Important**: modifying a role applies changes immediately to all users who have it assigned.

---

## Delete a role

1. Click the delete icon (trash) for the role.
2. Confirm the action in the dialog that appears.

> **Warning**: deleting a role removes all associated permissions from users who only had that role. Make sure those users have another role assigned.

---

## Available permissions

Permissions are grouped by module:

### Users & Roles
| Permission | What it allows |
|---|---|
| `list_users` | View the user list |
| `create_edit_delete_users` | Create, edit, and delete users |
| `list_roles` | View the roles list |
| `create_edit_delete_roles` | Create, edit, and delete roles and their permissions |

### Clients & Suppliers
| Permission | What it allows |
|---|---|
| `list_clients` | View the client list |
| `create_edit_delete_clients` | Create, edit, and delete clients |
| `list_suppliers` | View the supplier list |
| `create_edit_delete_suppliers` | Create, edit, and delete suppliers |

### Products
| Permission | What it allows |
|---|---|
| `list_products` | View the product catalogue |
| `create_edit_delete_products` | Create, edit, and delete products |
| `bulk_update_product_price` | Bulk price updates |
| `create_edit_delete_product_types` | Manage product types |
| `create_edit_delete_presentation_types` | Manage presentation types |
| `create_edit_delete_presentations` | Manage presentations |
| `create_edit_delete_product_movement_types` | Manage product movement types |

### Stock Entry
| Permission | What it allows |
|---|---|
| `list_receptions` | View stock entry history |
| `create_edit_delete_receptions` | Record and edit stock entries |

### Sales
| Permission | What it allows |
|---|---|
| `list_sales` | View the sales list |
| `create_edit_delete_sales` | Create, edit, and delete sales |
| `create_edit_delete_sale_states` | Manage sale states |
| `create_edit_delete_payment_methods` | Manage payment methods |
| `create_edit_delete_points_of_sale` | Manage points of sale |

### Quotes
| Permission | What it allows |
|---|---|
| `list_quotes` | View the quotes list |
| `create_edit_delete_quotes` | Create, edit, and delete quotes |

### Orders
| Permission | What it allows |
|---|---|
| `list_orders` | View the orders list |
| `create_edit_delete_orders` | Create, edit, and delete orders |
| `create_edit_delete_order_states` | Manage order states |
| `create_edit_delete_couriers` | Manage couriers / delivery persons |

### Daily Cash
| Permission | What it allows |
|---|---|
| `list_daily_cashes` | View daily cash history |
| `enable_close_daily_cash` | Open and close the daily cash |
| `create_edit_delete_cash_movement_types` | Manage cash movement types |

### Reports
| Permission | What it allows |
|---|---|
| `list_report_sales` | View sales report |
| `list_report_products` | View products report |
| `list_report_payments` | View payments report |
| `list_report_inventory` | View inventory report |
| `list_report_daily_cashes` | View daily cash report |
| `list_report_orders` | View orders report |
| `list_report_clients` | View clients report |
| `list_report_purchases` | View purchases report |

### Settings
| Permission | What it allows |
|---|---|
| `create_edit_delete_currencies` | Manage system currencies |
| `manage_customization` | Change the company logo, colours, and font |

---

## Assign roles to a user

Roles are assigned from the **Users** section:

1. Go to **Settings → Users**.
2. Edit the user you want to assign a role to.
3. In the **Roles** field, select one or more roles.
4. Save.

> The user will see the changes applied on their next login or after refreshing the page.

---

## Best practices

- **Create roles by function**, not by person. A "Salesperson" role is better than a "John" role.
- **Use the principle of least privilege**: assign only the permissions the user actually needs to work.
- **The `create_edit_delete_roles` permission** is very sensitive: only give it to trusted administrators, as whoever has it can modify any role and its access.
- **If a user can't see something**, first check what roles they have assigned and what permissions those roles include.

> **Tip**: If you need to create a full administrator, assign all available permissions to the role. This way they can manage any part of the system.

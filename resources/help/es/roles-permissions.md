# Roles y Permisos

El sistema de roles y permisos te permite controlar exactamente qué puede hacer cada usuario dentro de In-ventra. Un **rol** es un conjunto de permisos, y cada usuario puede tener uno o más roles asignados.

Ruta: **Configuración → Roles y Permisos**

---

## ¿Qué son los roles?

Un rol es un perfil de acceso que agrupa permisos. En lugar de configurar permisos uno a uno por usuario, creás roles con los accesos necesarios y los asignás a los usuarios correspondientes.

Ejemplos de roles típicos:
- **Administrador**: acceso total al sistema.
- **Vendedor**: puede crear y ver ventas, clientes y presupuestos, pero no acceder a configuración ni reportes financieros.
- **Depositero**: gestiona el stock, receptions y movimientos de productos, sin acceso a ventas ni caja.

---

## Crear un rol

1. Ir a **Configuración → Roles y Permisos**.
2. Hacer clic en **Nuevo rol**.
3. Escribir el nombre del rol (ej: "Vendedor", "Supervisor").
4. Seleccionar los permisos que tendrá ese rol (ver listado más abajo).
5. Guardar.

---

## Editar un rol

1. En la lista de roles, hacer clic en el ícono de edición (lápiz) del rol correspondiente.
2. Modificar el nombre o los permisos seleccionados.
3. Guardar los cambios.

> **Importante**: al modificar un rol, los cambios aplican de inmediato a todos los usuarios que lo tengan asignado.

---

## Eliminar un rol

1. Hacer clic en el ícono de eliminar (papelera) del rol.
2. Confirmar la acción en el diálogo que aparece.

> **Atención**: al eliminar un rol, los usuarios que solo tenían ese rol perderán todos los permisos asociados. Verificá que esos usuarios tengan otro rol asignado.

---

## Permisos disponibles

Los permisos están agrupados por módulo:

### Usuarios y Roles
| Permiso | Qué permite |
|---|---|
| `list_users` | Ver el listado de usuarios |
| `create_edit_delete_users` | Crear, editar y eliminar usuarios |
| `list_roles` | Ver el listado de roles |
| `create_edit_delete_roles` | Crear, editar y eliminar roles y sus permisos |

### Clientes y Proveedores
| Permiso | Qué permite |
|---|---|
| `list_clients` | Ver el listado de clientes |
| `create_edit_delete_clients` | Crear, editar y eliminar clientes |
| `list_suppliers` | Ver el listado de proveedores |
| `create_edit_delete_suppliers` | Crear, editar y eliminar proveedores |

### Productos
| Permiso | Qué permite |
|---|---|
| `list_products` | Ver el catálogo de productos |
| `create_edit_delete_products` | Crear, editar y eliminar productos |
| `bulk_update_product_price` | Actualizar precios de forma masiva |
| `create_edit_delete_product_types` | Gestionar tipos de producto |
| `create_edit_delete_presentation_types` | Gestionar tipos de presentación |
| `create_edit_delete_presentations` | Gestionar presentaciones |
| `create_edit_delete_product_movement_types` | Gestionar tipos de movimiento de producto |

### Ingreso de Stock
| Permiso | Qué permite |
|---|---|
| `list_receptions` | Ver el historial de ingresos de stock |
| `create_edit_delete_receptions` | Registrar y editar ingresos de stock |

### Ventas
| Permiso | Qué permite |
|---|---|
| `list_sales` | Ver el listado de ventas |
| `create_edit_delete_sales` | Crear, editar y eliminar ventas |
| `create_edit_delete_sale_states` | Gestionar estados de venta |
| `create_edit_delete_payment_methods` | Gestionar métodos de pago |
| `create_edit_delete_points_of_sale` | Gestionar puntos de venta |

### Presupuestos
| Permiso | Qué permite |
|---|---|
| `list_quotes` | Ver el listado de presupuestos |
| `create_edit_delete_quotes` | Crear, editar y eliminar presupuestos |

### Pedidos
| Permiso | Qué permite |
|---|---|
| `list_orders` | Ver el listado de pedidos |
| `create_edit_delete_orders` | Crear, editar y eliminar pedidos |
| `create_edit_delete_order_states` | Gestionar estados de pedido |
| `create_edit_delete_couriers` | Gestionar couriers / repartidores |

### Caja Diaria
| Permiso | Qué permite |
|---|---|
| `list_daily_cashes` | Ver el historial de cajas diarias |
| `enable_close_daily_cash` | Abrir y cerrar la caja diaria |
| `create_edit_delete_cash_movement_types` | Gestionar tipos de movimiento de caja |

### Reportes
| Permiso | Qué permite |
|---|---|
| `list_report_sales` | Ver reporte de ventas |
| `list_report_products` | Ver reporte de productos |
| `list_report_payments` | Ver reporte de pagos |
| `list_report_inventory` | Ver reporte de inventario |
| `list_report_daily_cashes` | Ver reporte de cajas diarias |
| `list_report_orders` | Ver reporte de pedidos |
| `list_report_clients` | Ver reporte de clientes |
| `list_report_purchases` | Ver reporte de compras |

### Configuración
| Permiso | Qué permite |
|---|---|
| `create_edit_delete_currencies` | Gestionar monedas del sistema |
| `manage_customization` | Cambiar logo, colores y fuente de la empresa |

---

## Asignar roles a un usuario

Los roles se asignan desde la sección de **Usuarios**:

1. Ir a **Configuración → Usuarios**.
2. Editar el usuario al que querés asignar el rol.
3. En el campo **Roles**, seleccionar uno o más roles.
4. Guardar.

> El usuario verá los cambios aplicados en su próximo inicio de sesión o al refrescar la página.

---

## Buenas prácticas

- **Creá roles por función**, no por persona. Un rol "Vendedor" es mejor que un rol "Juan".
- **Usá el principio de mínimo privilegio**: asigná solo los permisos que el usuario realmente necesita para trabajar.
- **El permiso `create_edit_delete_roles`** es muy sensible: solo dáselo a administradores de confianza, ya que quien lo tiene puede modificar cualquier rol y sus accesos.
- **Si un usuario no puede ver algo**, verificá primero qué roles tiene asignados y qué permisos incluyen esos roles.

> **Tip**: Si necesitás crear un administrador completo, asigná todos los permisos disponibles al rol. Así podrá gestionar cualquier parte del sistema.

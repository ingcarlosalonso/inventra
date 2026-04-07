# Inventra — Architecture & Database Decisions

## Stack
- PHP 8.5 / Laravel 12 (latest stable)
- MySQL, Eloquent ORM
- Spatie Multitenancy (one DB per tenant)
- Spatie Permission (RBAC)
- Inertia.js + Vue 3 + Vite
- Sanctum (API auth)
- DOMPDF, Maatwebsite Excel
- PHPUnit + Mockery

---

## Multi-tenancy
- Central DB: `inventra`
- Per-tenant DBs: created automatically on tenant registration
- Tenant finder: `DomainTenantFinder` (by hostname)
- Task: `SwitchTenantDatabaseTask`
- All business/tenant migrations go in: `database/migrations/tenant/`

---

## Environment
```
EXT_SUBDOMAIN_URL=".com"   # Global product, no .ar suffix
CENTRAL_DOMAIN="development.central.inventra.com"
DB_DATABASE=inventra
TENANCY_DATABASE=inventra
```

---

## UUID Rules
- UUID only on models exposed to the frontend.
- **Parametrización:** uuid only on tables edited from the front:
  - `sale_states` ✅
  - `order_states` ✅
  - `payment_methods` ✅
  - `cash_movement_types` ✅
  - `product_movement_types` ✅
  - `product_types` ✅
  - `presentation_types` ✅
  - `presentations` ✅
  - `currencies` ✅
- **Business entities:** all carry uuid:
  - `clients` ✅
  - `suppliers` ✅
  - `couriers` ✅
  - `products` ✅
  - `composite_products` ✅
  - `promotions` ✅
  - `points_of_sale` ✅
- **Transactions:** all carry uuid:
  - `sales` ✅
  - `orders` ✅
  - `quotes` ✅
  - `receptions` ✅
  - `daily_cashes` ✅
- **Detail/pivot tables:** no uuid (never exposed directly):
  - `sale_items`, `quote_items`, `reception_items`
  - `payments` (no uuid, but has softDeletes)
  - `product_movements`, `cash_movements`
  - `composite_product_product`, `product_promotion`
  - `barcodes`

---

## Table Map (old → new)

### Parametrización
| Old (ES) | New (EN) | Notes |
|---|---|---|
| `punto_de_ventas` | `points_of_sale` | + uuid, + address, keeps `number` field |
| `estado_ventas` | `sale_states` | + uuid |
| `estado_pedidos` | `order_states` | + uuid |
| `medio_pago` | `payment_methods` | + uuid, + created_by/updated_by |
| `tipo_movimiento_extra_cajas` | `cash_movement_types` | + uuid |
| `tipo_movimiento_extra_productos` | `product_movement_types` | + uuid |
| `tipo_productos` | `product_types` | + uuid, self-referential |
| `configuracion` | `settings` | rewritten with typed columns (see below) |
| — | `currencies` | **new** |
| — | `presentation_types` | **new** |
| — | `presentations` | **new** |

### Business Entities
| Old (ES) | New (EN) | Notes |
|---|---|---|
| `clientes` | `clients` | + uuid |
| `proveedors` | `suppliers` | + uuid |
| `cadetes` | `couriers` | + uuid |
| `productos` | `products` | + uuid, + presentation_id |
| `producto_compuestos` | `composite_products` | + uuid |
| `adicionals` | ❌ removed | Replaced by product_type = "addon". Create a product type called "Addon" and use regular products |
| `promocions` | `promotions` | + uuid |
| `codigo_barras` | `barcodes` | no uuid |

### Transactions
| Old (ES) | New (EN) | Notes |
|---|---|---|
| `ventas` | `sales` | + uuid |
| `detalle_ventas` | `sale_items` | removed `compuesto_por` JSON field |
| `pedidos` | `orders` | + uuid |
| `presupuestos` | `quotes` | + uuid |
| `detalle_presupuestos` | `quote_items` | no uuid |
| `pagos` | `payments` | + softDeletes |
| `caja_diarias` | `daily_cashes` | + uuid |
| `recepcions` | `receptions` | + uuid |
| `ingreso_productos` | `reception_items` | no uuid |
| `movimiento_extra_productos` | `product_movements` | no uuid |
| `movimiento_extra_cajas` | `cash_movements` | no uuid |

### Pivot Tables
| Old (ES) | New (EN) |
|---|---|
| `cantidad_producto_compuestos` | `composite_product_product` |
| `cantidad_promocions` | `product_promotion` |
| `adicional_producto_compuestos` | ❌ removed (addons eliminated) |
| `adicional_promocions` | ❌ removed (addons eliminated) |

---

## Settings Table (typed columns)
Replaces the old `configuracion` table that stored everything in a single JSON field.
Two scopes: **global per tenant** and **per point of sale**.

```
settings
  id
  point_of_sale_id (nullable FK → points_of_sale — null = global tenant setting)
  business_name
  logo (nullable, path)
  tax_id (nullable — CUIT or equivalent)
  legal_name (nullable)
  address (nullable)
  phone (nullable)
  email (nullable)
  website (nullable)
  timestamps
  soft_deletes
```

---

## Key Design Decisions

1. **No `addons` entity.** Extras like "delivery" or "preparation" are modeled as regular products with a dedicated `product_type` (e.g. type = "Addon"). This simplifies the sale item model.

2. **No JSON in `sale_items`.** The old `compuesto_por` longtext/JSON column is removed. Composite product composition is handled via the `composite_product_product` pivot table.

3. **`settings` uses typed columns**, not a single JSON blob. Supports both global (tenant-wide) and per-point-of-sale configuration via nullable `point_of_sale_id`.

4. **`payments` gets softDeletes** (was missing in the old system).

5. **`points_of_sale` keeps the `number` field** from the old system.

6. **Multi-currency support** via new `currencies` table with `is_default` flag.

7. **Presentations system** (new): `presentation_types` (weight, volume, unit) → `presentations` (grams, liters, units) → linked to `products`.

8. **API-first architecture.** All routes in `api.php`. Inertia consumes the same endpoints today; mobile app can consume them tomorrow with no backend changes.

---

## Soft Deletes
Applied to all users and business entities:
`users`, `clients`, `suppliers`, `couriers`, `products`, `composite_products`, `promotions`, `sales`, `orders`, `quotes`, `receptions`, `daily_cashes`, `payments`, `points_of_sale`, all parametrization tables.

## created_by / updated_by
All business tables track who created/updated the record via `created_by` and `updated_by` FK → `users.id`.

---

## Feature Development Order (per module)
Action → FormRequest → Controller → Resource → Events/Listeners → Unit Tests → Feature Tests

## Branch Naming
- `feature/title-in-kebab-case`
- `bug/title-in-kebab-case`

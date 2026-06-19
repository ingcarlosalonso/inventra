# Changelog

All notable changes to In-ventra are documented here.

---

## [1.3.0] - xxxx-xx-xx

### Added
- Automatic user creation on tenant provisioning: when a new tenant is created from the central admin panel, an Administrator role is created with all permissions and two users are provisioned — one derived from the contact name (`nombre.apellido@in-ventra.com`, password equals the email by default) and one internal system user for platform access
- System user (`is_system` flag): the internal admin user is excluded at the query level from all tenant user listings and API responses via a global Eloquent scope
- `expires_at` enforcement: tenants whose expiration date has passed are now blocked immediately via `Tenant::isActive()`, regardless of their `status` field
- `tenants:suspend-expired` command: scheduled daily at 00:05, automatically sets expired tenants to `suspended` so the central admin panel reflects the correct status

---

## [1.2.0] - xxxx-xx-xx

### Added
- Brands (marcas): full CRUD for product brands with active/inactive toggle; products can be assigned an optional brand; brand label shown in product list and in the product search dropdown on sales, orders, and quotes
- Barcodes per product presentation: each presentation can have one or more EAN/QR codes directly assigned
- Barcode reader for sales, orders, quotes, and receipts: a scanning field that adds the corresponding item when Enter is pressed, compatible with USB/Bluetooth readers

---

## [1.1.0] - 2026-06-18

### Added
- Release notes system: central admin can draft, edit, and publish release notes parsed from CHANGELOG.md; users see a "What's New" popup on first login after each new release
- Profile page: authenticated users can change their password from a dedicated profile section accessible via the top bar user menu
- Permission middleware on all API endpoints: every route now enforces the corresponding `list_*` or `create_edit_delete_*` permission; previously only reports, roles/users, and bulk price were protected
- Roles & Permissions help section: full user guide covering role management, permission catalogue, and best practices
- `RoleControllerTest` and `PermissionControllerTest` with happy path, validation, 401, and 403 coverage
- `RoleFactory` for use in tests
- Human-readable permission labels and descriptions in the role editing slide-over: each checkbox now shows a friendly name and a short explanation instead of the raw database key
- `manage_customization` permission for the system customization endpoint (logo, colours, font)
- Translation files `lang/es/permissions.php` and `lang/en/permissions.php` with label and description for every permission

### Fixed
- List rows across all modules (Sales, Orders, Quotes, Receptions, Daily Cashes, Products, Clients, Suppliers, and Settings catalogs) redesigned with a 2-row card layout for mobile; edit/delete buttons are always visible on touch devices instead of hidden behind hover
- AI Assistant panel redesigned as a bottom sheet on mobile with backdrop, drag handle, and top-aligned welcome state with suggestion buttons
- Sales and Orders creation forms: payment method selector and remove-payment button now usable on small screens
- Sales and Orders detail pages: item and payment tables now scroll horizontally instead of overflowing on mobile
- Logout broken in tenant app: now calls the Sanctum API endpoint, clears the local token, and redirects to login
- Logout broken in central admin: was posting to wrong route `/central-admin/logout`, corrected to `/logout`
- Reports section failing to load: frontend was calling `/api/reports/*` instead of the versioned `/api/v1/reports/*` endpoints
- 500 error on every `permission:*` protected route (reports, bulk price update, roles/users management): the `permission` middleware alias was never registered in `bootstrap/app.php`
- Orders report 500 error: `OrdersReport` queried a non-existent `name` column on `clients` instead of `first_name`/`last_name`
- GET endpoints for reference/config tables (payment methods, points of sale, sale/order states, couriers, cash movement types, product types, presentations, currencies) now require only `auth:sanctum` instead of a write permission, allowing all authenticated users to populate form dropdowns
- `list_reports` orphaned permission removed from `PermissionSeeder` (it had no corresponding route)

## [1.0.0] - 2026-06-11

First production release. Full multi-tenant platform ready to operate.

### Added
- Multi-tenant SaaS with isolated database per company (spatie/laravel-multitenancy)
- Granular RBAC permissions system (spatie/laravel-permission)
- Central admin panel for tenant management (create, suspend, activate)
- Products module: types, presentations, composite products, promotions, barcodes, bulk price update, Excel import, extra movements
- Sales module: multiple payment methods, percentage and fixed-amount discounts, configurable states, conversion to order
- Orders module: courier assignment, state machine, stock deduction on delivery
- Quotes module: PDF generation (DOMPDF), conversion to sale or order
- Daily Cash module: opening/closing per point of sale, extra movements, reconciliation and balance
- Receptions module: incoming merchandise from suppliers with purchase price
- Clients and Suppliers management
- Reports module: sales, orders, payments, inventory, daily cashes, clients, purchases (with Excel export)
- AI Assistant (Groq / Llama 4 Scout) with inventory and sales tools
- Dashboard with KPIs, charts, low stock alerts, and open cash registers summary
- Notification system for low stock alerts
- User, role, and permission management per tenant
- Full multilingual support (Spanish default, English fallback)
- Tenant customization: logo, colors, business name
- Legacy data migration command (`MigrateLegacyDataCommand`)
- Unit and feature test suite covering models, scopes, actions, services, and controllers

### Security
- Rate limiting on login endpoint (5 attempts per minute)
- Permission middleware on sensitive routes (users, roles, bulk price update)
- Automatic stock reversion on sale, order, or reception deletion
- Soft deletes on CashMovement and ProductMovement for audit trail integrity
- Composite index on payments (payable_type, payable_id)
- Performance indexes on sales.created_at, products.is_active, orders.scheduled_at

---

## Versioning

This project follows [Semantic Versioning](https://semver.org/):
- **MAJOR** (X.0.0): breaking changes or core module redesign
- **MINOR** (1.X.0): new backwards-compatible functionality
- **PATCH** (1.0.X): bug fixes and minor improvements without new features

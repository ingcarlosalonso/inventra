# Changelog

All notable changes to In-ventra are documented here.

---

## [1.4.0] - xxxx-xx-xx

### Added
- Sellable module system: features can now be contracted per tenant independently of the base package. Two modules launch with this release — "Pedidos y Presupuestos" (Orders, Couriers, Order States, Quotes) and "Asistente IA" — toggled per client from the central admin panel. Disabled modules are hidden from the sidebar/AI assistant icon and their API routes return 403
- AI Assistant can now create sales, orders and quotes from a chat description (client, products, discount, payment method/credit). It always resolves client/products/payment method server-side and shows a priced draft first — it only creates the record once the user explicitly confirms, and only if the user has the same permission the manual screen requires

### Fixed
- The `module:` gate for "Pedidos y Presupuestos" was only wired into the API routes (`routes/v1.php`), not the Inertia page routes (`routes/web.php`) — a tenant without that module contracted could still open `/orders`, `/quotes`, `/settings/order-states`, `/settings/couriers` and `/reports/orders` directly by URL (the API calls behind the page would 403, but the page itself rendered). Added `module:orders_quotes` to those page routes too, and a route-assertion test so a module missing from either route file fails the suite
- `Presentation` has no `name` column (only quantity + unit type), so every place reading `$presentation->name` (AI Assistant stock/low-stock tools, the new create_sale/order/quote item matching) silently got `null` and showed a blank size. Added `Presentation::display` (e.g. "2 L") as the single source of truth and switched the assistant to it. **Note**: `SaleItemResource`, `OrderItemResource`, `QuoteItemResource`, `ProductMovementResource`, `LowStockNotification`, `InventoryReport` and `ProcessSaleService`/`ProcessOrderService` (stock-error messages) have the same `->presentation->name` bug and were not touched in this pass — flagged for a separate fix
- AI Assistant `create_sale`/`create_order`/`create_quote` item matching required every word of a natural-language description to match the product name verbatim, so "agua mineral de 2 litros" wouldn't resolve to a product literally named "Agua Mineral 2L" (filler words and unit-notation differences broke it). Matching is now score-based — it fetches every product matching *any* significant word, then picks whichever matches the *most*, so natural phrasing works the way a human reading it would expect
- AI Assistant `create_sale`/`create_order`/`create_quote` rejected any call where the model sent an explicit `null` for an inapplicable optional field (`point_of_sale_search`, `notes`, `address`, `courier_search`, `scheduled_at`, `expires_at`, `client_search`, `confirm`, `requires_delivery`) — every one of these was a non-nullable schema parameter, so a perfectly reasonable `null` from the model got the whole tool call rejected before our code ran. All now accept `null` explicitly
- AI Assistant's "create a sale" suggestion chip was a fully pre-written example with an invented client/product/payment ("2 Coca-Cola for Juan Pérez"), which doesn't make sense for every business type. It's now a short, generic prompt ("Quiero crear una venta") that lets the assistant ask for the actual details conversationally, for any kind of business
- AI Assistant fallback model (`llama-3.1-8b-instant`) frequently sent malformed tool-call arguments (wrong types, missing required fields) in testing; swapped for `openai/gpt-oss-20b`, which handled the same scenarios correctly
- AI Assistant `create_sale`/`create_order`/`create_quote` could be chained by the model into calling `confirm=true` right after `confirm=false` within the same reply, before the human ever saw the draft — defeating the point of the confirmation step. `confirm=true` now only succeeds if the assistant's immediately preceding reply actually was a shown draft (tracked via a stable marker the backend appends itself, not left to the model to reproduce)
- AI Assistant model was hardcoded (`meta-llama/llama-4-scout-17b-16e-instruct`), which Groq has since retired, breaking the assistant entirely with a 500 error. Model selection now comes from config/env (`GROQ_MODEL`, `GROQ_FALLBACK_MODELS`), `AssistantService` automatically falls back through the configured list on failure, and returns a friendly translated message instead of a 500 if every model fails. Added `assistant:check-model`, scheduled daily, to warn when the configured model stops working
- AI Assistant `create_sale`/`create_order`/`create_quote` tools rejected any request with an explicit "no discount" because their `discount_type` parameter was a strict enum and providers reject empty/null values that aren't in the enum list — every "sin descuento" request failed at the tool-call-validation level before our code even ran
- Client search (`ClientBySearch`, used by the AI Assistant's client tools and the manual Clients screen) required the search text to match as one exact substring in a single column, so a full name like "Luciana Fernández" never matched since first and last name live in separate columns. Now matches each word independently, in any order — same fix already applied to product search
- AI Assistant (and product/composite product search in general) required the search text to match as one exact substring, so a query like "coca cola 500" would fail to find "Coca-Cola 500ml". Product and composite product search now match each word independently, in any order
- AI Assistant "stock and price" tool queried `stock`/`min_stock`/`price` directly on `Product`, which doesn't have those columns (they live on `ProductPresentation`) — every answer came back with blank values. It now queries `ProductPresentation` correctly, including inactive-presentation filtering
- AI Assistant "low stock" tool ran a raw `stock <= min_stock` query against the `products` table (same missing-columns issue as above), which errored on every call. Fixed the same way, via `ProductPresentation`
- AI Assistant "composite products" and "promotions" tools referenced a `products` relation that no longer exists since the kits/promotions restructure — both errored on every call. Now use the current `items.product` relation and `sale_price`/`code` fields
- AI Assistant "daily cash status" tool referenced a `movements` relation that doesn't exist on `DailyCash` (the real relation is `cashMovements`) — errored on every call
- AI Assistant "recent receptions" tool referenced a `product` relation that doesn't exist on reception items (items relate to `productPresentation.product`) — errored on every call
- AI Assistant "clients" tool ordered by a non-existent `name` column (clients are stored as `first_name`/`last_name`) — errored on every call

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

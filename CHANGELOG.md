# Changelog

All notable changes to In-ventra are documented here.

---

## [2.1.0] - xxxx-xx-xx

### Added
- Mercado Pago module: tenants can connect their own Mercado Pago account (OAuth) and charge sales/orders by card through a linked Point (posnet) terminal per point of sale. Charges are created as a priced order pushed to the terminal and confirmed asynchronously via a signed Mercado Pago webhook, which registers the resulting payment automatically — no card data ever passes through In-ventra. Gated behind the new "Mercado Pago" sellable module and a dedicated `manage_mercadopago` permission

### Fixed
- Mercado Pago OAuth connect used a per-tenant callback URL (each tenant's own subdomain), but Mercado Pago OAuth applications only accept a single static `redirect_uri` — the "Conectar" flow could never have worked for more than one tenant. The callback now lives on the central domain; the initiating tenant travels through the cached OAuth `state` and the browser is redirected back to that tenant's own settings page once the token exchange succeeds
- Mercado Pago webhook dispatched processing for both `order` and `payment` notification topics, but a `payment` notification's `data_id` is a payment id, not an order id — `ProcessMercadoPagoWebhookAction` had no way to resolve it, so the card was charged on Mercado Pago's side but silently never reflected in In-ventra. Only `order` notifications are dispatched now, matching what the Point/Orders API actually sends for this integration
- Charging a sale/order through the Mercado Pago terminal (`CreatePosnetChargeAction`) and confirming a payment from the webhook (`ProcessMercadoPagoWebhookAction`) both read-then-wrote without a lock, so a double-click/retry or a retried webhook delivery (Mercado Pago retries notifications) could create two terminal charges or register the same payment twice. Both now run inside a `tenant` DB transaction with `lockForUpdate()`, and the pending-balance check now also subtracts charges still awaiting terminal confirmation, not just confirmed payments
- An order status Mercado Pago returns outside the 8 statuses this module models (e.g. a new status added on their side) crashed the webhook job and the charge endpoint with an uncaught `ValueError` instead of failing gracefully — both now log a warning and skip the update / fall back to `created` instead of crashing
- The async Mercado Pago webhook job registered a payment even for a tenant whose "Mercado Pago" module had been disabled mid-flight, bypassing the `module:mercado_pago` gate every synchronous entry point already enforces. The job now checks `Tenant::hasModule('mercado_pago')` before processing
- Mercado Pago webhook responded with `200`/`401` instead of the `204 No Content` this project's webhooks are expected to return immediately
- Posnet charge failures (account not connected, terminal not configured, no local payment method configured, amount exceeds pending balance) were silently swallowed in `Payments/Create.vue` — the "Cobrar con Mercado Pago" button just did nothing visible. The charge error is now shown inline
- `MercadoPagoException` messages were hardcoded in English and never routed through `__()`, breaking this project's i18n rule — and they're exactly what the fix above now surfaces to the user. All messages now come from `lang/{es,en}/mercadopago.php`
- Disconnecting a Mercado Pago account (`MercadoPagoConnectionController::destroy`) only deleted the local credential row — the access/refresh token stayed authorized on Mercado Pago's side. It now revokes the authorization via Mercado Pago's API first (best-effort: a failed/unreachable revoke still lets the local disconnect succeed)
- "Registrar Venta"/"Registrar Pedido"/"Registrar Presupuesto" silently did nothing: `Sales/Create.vue`, `Orders/Create.vue` and `Quotes/Create.vue` sent each item as `product_presentation_id`, but `StoreSaleRequest`/`StoreOrderRequest`/`StoreQuoteRequest` require the polymorphic `item_type` + `saleable_id` pair — every submission failed validation, and the resulting field-level errors had no matching input to attach to, so nothing appeared on screen. Items now send `item_type: 'product'` and `saleable_id`, and a generic error message is always shown as a fallback even when field-level errors can't be displayed inline
- `database/seeders/DatabaseSeeder.php` used `WithoutModelEvents` and created a leftover "Test User" (scaffolding never meant for real tenants). Suppressing model events broke `User`'s `HasUuid` boot event (`uuid` has no DB default), so `tenant:migrate --seed` crashed with a `QueryException` on every tenant instead of just seeding the module catalog. Removed the Test User creation and the trait; `DatabaseSeeder` now only calls `ModuleSeeder`
- `daily-cash:auto-manage` and the new `tenants:sync-permissions` command called `$tenant->run(...)`, a method that doesn't exist on `spatie/laravel-multitenancy` v4 (`Tenant` only exposes `execute()`) — every scheduled run of the auto open/close command was throwing a `BadMethodCallException` instead of doing anything. Fixed both to use `execute()`

### Added
- `Sales/Create.vue` and `Orders/Create.vue` now flag stock problems up front instead of letting the user discover them at the payment step: the product search shows out-of-stock presentations struck through, grayed out and labeled "Sin stock" (not selectable), and each item row turns red with an inline "stock insuficiente" message if its quantity exceeds available stock — which also disables "Registrar Venta"/"Registrar Pedido" until resolved. `Quotes/Create.vue` gets the same visual cues (in a non-blocking amber tone) since a quote doesn't commit stock and is allowed to reference an out-of-stock item
- `tenants:sync-permissions` artisan command: runs `PermissionSeeder` against existing tenants (optionally scoped via `--tenant=`), so a permission added after a tenant was already provisioned (like `manage_mercadopago`) can be backfilled without touching `DatabaseSeeder`

## [2.0.1] - 2026-08-24

### Fixed
- Product listing never showed current stock per presentation, even though the backend already returned it (`ProductPresentationResource.stock`) — the list row only showed brand/type/presentation count. Added a per-product stock badge (color-coded for out-of-stock/low-stock) to the product list
- `UserController::toggle` had no self-guard, unlike `destroy` — a user could deactivate their own account and lock themselves out. Added the same self-check `destroy` already had
- Deleting a `ProductType` or `Presentation` still assigned to products threw an uncaught `QueryException` (500) instead of a friendly error, since the FK is `restrictOnDelete()` but the controllers never checked for existing products first. Both now return a 422 with a translated message
- Receptions linked to an open daily cash had zero effect on that cash's balance — `StoreReceptionAction` never recorded anything against the cash and `CalculateDailyCashBalanceAction` never looked at `Reception` at all, despite the Help docs and app documentation describing the reception total as reflected in the day's balance. The balance calculation (and the equivalent `DailyCash` index/show queries) now subtracts linked receptions' totals
- `CompositeProductController::destroy` deleted unconditionally with no check for prior usage. Composite products aren't addable through Sales/Orders/Quotes today, but the polymorphic `saleable_type`/`saleable_id` columns those items already use to reference sellable items have no DB-level FK (morph columns can't be indexed/constrained), so once that flow ships this would silently orphan historical sale/order/quote items instead of failing loudly. Blocked deletion (422) when a composite product is still referenced by any `SaleItem`, `OrderItem`, or `QuoteItem`
- Audited every Help section (`resources/help/{es,en}/*.md`) against actual app behavior and corrected numerous claims describing filters, actions, and flows that don't exist (e.g. non-existent list filters across Sales/Orders/Quotes/Daily Cashes/Reports, PDF generation for Quotes, editing Sales/Orders/Quotes after creation, adding composite products/promotions to a sale or order, client CUIT/credit-limit/history, `list_users`/`list_roles` as functional view-only permissions, and dashboard quick-action buttons that aren't in the top bar)

## [2.0.0] - 2026-08-06

### Added
- Sellable module system: features can now be contracted per tenant independently of the base package. Two modules launch with this release — "Pedidos y Presupuestos" (Orders, Couriers, Order States, Quotes) and "Asistente IA" — toggled per client from the central admin panel. Disabled modules are hidden from the sidebar/AI assistant icon and their API routes return 403

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

# In-ventra — Junie Guidelines

> **Auto-generado** el 2026-06-16 15:20 desde `CLAUDE.md` y `AGENTS.md`.
> No editar manualmente — los cambios se perderán en la próxima sincronización.
> Para actualizar: modificar `CLAUDE.md` o `AGENTS.md` y hacer commit (el hook lo regenera automáticamente).

---

# In-ventra — Claude Guide

## Working Mode

- Never use Plan Mode or Explore agents unless the user explicitly says "explore" or "plan first".
- For new modules: read a maximum of 5 reference files and code directly.
- Limit bash outputs to the last lines (| tail -10).
- CLAUDE.md already documents the entire architecture and patterns — trust it without re-exploring the codebase.
- Mandatory help section: Every time any user-facing functionality is added, modified, or removed (routes, screens, permissions, flows), update the corresponding help section in resources/js/Pages/Help/ and the HelpController. No exceptions.

## Project Description

**In-ventra** is a multi-tenant SaaS system for inventory, sales, and order management aimed at small and medium businesses. Each client company operates on its own subdomain with an isolated database.

## Tech Stack

| Layer | Technology                                      |
|-------|-------------------------------------------------|
| Backend | PHP 8.2 / Laravel 12                            |
| ORM | Eloquent                                        |
| Database | MySQL (SQLite for testing)                      |
| Multi-tenancy | spatie/laravel-multitenancy (one DB per tenant) |
| Permissions | spatie/laravel-permission (latest, RBAC)        |
| Auth | laravel/sanctum (API tokens, custom `PersonalAccessToken` model) |
| AI | prism-php/prism (Groq provider, `AssistantService`) |
| PDF | barryvdh/laravel-dompdf (latest)                |
| Excel | maatwebsite/excel (latest)                      |
| Frontend | Inertia.js v3 + Vue 3 + Vite                    |
| Styling | Tailwind CSS v4                                 |
| Tests | PHPUnit (latest) + Mockery                      |

## Internationalisation (i18n)

- The application is **fully multilingual**. Every user-facing string MUST use Laravel's translation helpers (`__()`, `trans()`, `@lang`).
- **Never hardcode** Spanish or English text in PHP or Vue files.
- Translation files live in `lang/{locale}/` (e.g. `lang/es/`, `lang/en/`).
- Group keys by domain: `lang/es/products.php`, `lang/es/clients.php`, `lang/es/common.php`, `lang/es/notifications.php`, `lang/es/sales.php`, etc.
- Vue components use the `$t()` helper from the Inertia shared translations (passed via `HandleInertiaRequests`).
- Default locale: `es` (Spanish). Fallback: `en`.
- `SetLocale` middleware resolves the active locale from `session('locale')`, validated against `['es', 'en']`, falling back to `config('app.locale')`.
- `HandleInertiaRequests::loadTranslations()` reads every file in `lang/{locale}/` and shares it as `translations` on every Inertia response. **This is currently uncached** — if it becomes a perf issue, cache per-locale and bust on deploy.

## Application Navigation Structure

```
Sidebar:
├── Dashboard
├── Stock Entry (Ingreso de Stock)
├── Products
│   ├── [Config] Product Types
│   ├── [Config] Extra Movement Types (product)
│   ├── [Config] Presentations
│   ├── Products
│   ├── Composite Products
│   ├── Bulk Price Update
│   ├── Import XLSX
│   └── Extra Movements
├── Suppliers
├── Daily Cash
│   ├── Daily Cashes
│   ├── Extra Movements
│   └── [Config] Extra Movement Types (cash)
├── Clients
├── Orders
│   ├── Orders
│   ├── Order States
│   └── Couriers
├── Sales
│   ├── Sales
│   ├── [Config] Points of Sale
│   ├── [Config] Sale States
│   └── [Config] Payment Methods
└── Settings
    ├── General Parameters
    └── Currencies

Quick Actions (top bar): Nueva Venta · Nuevo Pedido · Ingresar Pago · Presupuestos · Reportes
Other top-bar elements: Notifications bell (unread count + list), AI Assistant chat, locale switcher.
```

## UI / Frontend Guidelines

- Design must feel **modern, clean, and intuitive**. Avoid dated table-heavy layouts.
- Use a **fixed sidebar** with grouped navigation and icons.
- **Top bar** with tenant name, quick-action buttons, notifications, AI assistant entry point, and user menu.
- List pages: searchable, with status badges, row actions (edit/delete), and an "Add" button.
- Forms: slide-over panels or dedicated pages — never modal dialogs for complex forms.
- Use **consistent color tokens** via Tailwind CSS v4 variables. Tenant-level theming (`logo`, `primary_color`, `secondary_color`, `accent_color`, `font_family`) comes from the `Customization` model via `HandleInertiaRequests` → shared prop `customization`.
- Empty states, loading skeletons, and inline validation feedback are required.
- All UI text goes through `$t()`.

## Central Admin Domain

The central domain (`CENTRAL_DOMAIN=development.central.in-ventra.com`) is the **landlord admin panel** — it is NOT a tenant. Its purpose is to create and manage tenants (client companies).

- Login at: `http://development.central.in-ventra.com/login` (auth guard: `central`, user model: `Admin`)
- After login → `/tenants` to list/create/suspend/activate tenants
- Routes live in `routes/central.php` with a domain constraint (`Route::domain(config('app.central_domain'))`)
- Controllers: `App\Http\Controllers\Central\{AuthController, TenantController}`
- Vue pages: `resources/js/Pages/Central/{Login.vue, Tenants/}`
- `routes/central.php` is registered **before** `routes/web.php` in `bootstrap/app.php` (via the `then:` callback) so the domain constraint takes precedence over the tenant `/login` route
- The `central_domain` key is defined in `config/app.php` sourced from `CENTRAL_DOMAIN` env var
- Tenant provisioning (`TenantController::store` → `ProvisionTenantAction`) creates the per-tenant database, runs tenant migrations, and seeds permissions (`PermissionSeeder`).
    - **Security note**: the generated database name is built from the user-supplied `subdomain`. The `TenantController` validation (`regex:/^[a-z0-9\-]+$/`, `unique:tenants,domain`) is the only thing protecting `ProvisionTenantAction`'s raw `DB::statement("CREATE DATABASE ...")`. If `ProvisionTenantAction::execute()` is ever called from another entry point (CLI, job, tinker), re-validate the subdomain format inside the Action itself — never trust the caller.
- `central:create-admin` artisan command (`CreateCentralAdmin`) creates/updates an `Admin` for the central panel via `Admin::updateOrCreate(['email' => ...], ['password' => bcrypt(...)])`.

## Multi-tenant Architecture (Spatie)

- **Central database** (`inventra`): `tenants` and `domains` tables from spatie/laravel-multitenancy, plus company data, `admins` table.
- **Per-tenant databases**: one per client, created automatically (`in_ventra_tenant_{subdomain}`). Contain all business tables.
- Tenant identification via hostname using Spatie's `DomainTenantFinder`.
- Models that belong to tenant DB use the tenant connection configured via `SwitchTenantDatabaseTask`.
- Middleware aliases (registered in `bootstrap/app.php`):
    - `tenant` → `Spatie\Multitenancy\Http\Middleware\NeedsTenant`
    - `tenant.session` → `Spatie\Multitenancy\Http\Middleware\EnsureValidTenantSession`
    - `tenant.active` → `App\Http\Middleware\EnsureActiveTenant` (custom: blocks access if `Tenant::current()` is suspended, returns 403 JSON or `errors.tenant-suspended` view)
- Every tenant-scoped route group must include `['tenant', 'tenant.active']` middleware (see `routes/v1.php`, `routes/web.php`).
- Key env vars: `CENTRAL_DOMAIN`, `SUBDOMAIN_URL`, `EXT_SUBDOMAIN_URL`, `WEBDOMAIN`, `TENANCY_DATABASE`.
- Tenant-related artisan commands:
    - `tenant:migrate [--fresh] [--seed] [--tenant=*]` (`TenantMigrateCommand`) — runs migrations on tenant DBs via `MigrateTenantAction`.
    - `app/Actions/MigrateTenantAction.php` extends Spatie's base action, forcing `--path=database/migrations/tenant` and the tenant DB connection.
    - `tenants:migrate-legacy` (`MigrateLegacyDataCommand`) — one-off command for migrating data from the legacy "StockAdministrator" system. Interactive (Laravel Prompts), per-tenant confirmation. Treat as a maintenance tool, not part of the normal feature workflow.
    - `daily-cash:auto-manage` (`AutoManageDailyCashCommand`) — iterates all tenants (`Tenant::all()->each(fn ($t) => $t->run(...))`) and opens/closes daily cashes per point-of-sale schedule (`auto_open_time` / `auto_close_time`). Scheduled via `routes/console.php`.

## Authentication & Authorization

- Laravel standard auth (API via Sanctum), separate login for central domain vs. tenants.
    - Central: `auth:central` guard, `Admin` model, session-based (Inertia).
    - Tenant: `auth:sanctum`, `User` model, token-based (`createToken('api')->plainTextToken`).
    - Custom `PersonalAccessToken` model registered via `Sanctum::usePersonalAccessTokenModel()` in `AppServiceProvider`.
    - `SANCTUM_TOKEN_EXPIRATION` / `SANCTUM_TOKEN_PREFIX` env vars configure token lifetime and prefix (`inventra_*`).
- Login throttling: `Route::post('auth/login', ...)->middleware('throttle:5,1')`.
- RBAC with spatie/laravel-permission (latest). `Role` and `Permission` models scoped per tenant (custom models in `App\Models\{Role,Permission}`, configured in `config/permission.php`).
- Routes protected with `middleware('permission:permission_name')`.
- Permission conventions: `list_*`, `create_edit_delete_*`, specific permissions for critical operations (e.g. `enable_close_daily_cash`, `bulk_update_product_price`).
- **`PermissionController::index`** returns the full permission catalogue (`Permission::orderBy('name')->get()`) for the role-editing UI. This endpoint must stay behind `auth:sanctum` **and** a permission gate restricted to users who can manage roles (e.g. `list_roles` or equivalent) — do not leave it open to any authenticated user.
- User self-management guardrails (when touching `UserController`):
    - `destroy` and `toggle` must prevent a user from deactivating/deleting their own account (`$request->user()->id !== $user->id`).
    - Password updates must go through the model's `hashed` cast (`casts()` → `'password' => 'hashed'`) — never assign `$request->input('password')` to a plain attribute without confirming the cast is in place.

## Main Modules

### Sales (`Sale`)
- Support for simple items, composite products, and promotions (`SaleItemType` enum: `Product`, `Composite`, `Promotion`, each with a `morphType()` mapped via `Relation::morphMap()` in `AppServiceProvider`).
- Percentage and fixed-amount discounts (`DiscountType` enum: `Percentage`, `Fixed`), at both sale and item level.
- Multiple payment methods per sale (`Payment` model, polymorphic `payable`).
- Stock validation before confirming — throws `InsufficientStockException` (rendered as 422 JSON via `bootstrap/app.php` → `withExceptions`).
- Configurable states (with `default` and `final_state` flags via `SaleState`).
- Conversion to delivery order.
- Created/processed through `ProcessSaleService` (multi-step: resolve UUIDs → build items via `BuildSaleItemsData` → validate stock → create sale/items/payments inside a `DB::connection('tenant')->transaction()`).
- **Deletion**: `SaleController::destroy` must revert stock for all items (and any linked order/daily-cash effects) before/while soft-deleting — do not call `$sale->delete()` directly without a `returnStock()`-style step. Also guard deletion based on sale state (e.g. disallow deleting sales in a `final_state`).

### Orders (`Order`)
- Delivery orders linked to a sale.
- Courier (delivery person) assignment.
- Configurable state machine (`OrderState`, with `IsDefault` scope).
- Stock deduction on delivery confirmation.
- State transitions via `OrderController::updateState` (`PATCH /orders/{order}/state`).

### Quotes (`Quote`)
- Pre-sale quotations.
- PDF generation (DOMPDF).
- Conversion to sale or order (`ConvertQuoteToSaleAction`).
- `NotConverted` scope filters quotes not yet converted.

### Inventory (`Product`)
- Hierarchical categories (`ProductType`, self-referential).
- Available stock + minimum threshold (`min_stock`), per `ProductPresentation`.
- Multiple barcodes per product (`Barcode` model).
- Composite products (kits) and promotions (`CompositeProduct`, `Promotion` + `PromotionItem`).
- Bulk import via Excel (`ProductImportController` → `ProductImport` import class, `ToCollection` + `WithHeadingRow` + `WithValidation`, matches by barcode then by name, supports both Spanish and English column headers — `nombre`/`name`, `costo`/`cost`, `tipo`/`type`, `codigo_barras`/`barcode`).
- Bulk price update (`BulkPriceController` → `BulkPriceUpdateAction`).
- Extra movements (adjustments, losses, corrections) via `ProductMovement` + `ProductMovementType`.
- **Presentation** (`Presentation`): defines how a product is measured/sold (e.g. grams, liters, units).
- **Presentation Type** (`PresentationType`): the unit of measure category (weight, volume, unit, etc.).
- Low-stock detection: `CheckLowStockAction` (triggered after stock changes) checks `stock < min_stock` and notifies all active users with an email via `LowStockNotification` (`database` + `mail` channels, `ShouldQueue`). When iterating notifiable users, prefer `Notification::send($users, new LowStockNotification(...))` over `User::query()->each(...)` to avoid per-user dispatch overhead.

### Daily Cash (`DailyCash`)
- Opening and closing of cash register per point of sale.
- Automatic registration of received payments (`withSum('payments', 'amount')`).
- Extra movements (deposits, withdrawals) with configurable types (`CashMovementType`, `is_income` flag distinguishes income vs. expense movements).
- Reconciliation and balance verification via `CalculateDailyCashBalanceAction`.
- Linked to supplier receptions.
- Auto open/close based on `PointOfSale.auto_open_time` / `auto_close_time`, run by the `daily-cash:auto-manage` scheduled command (see Multi-tenant Architecture above).

### Receptions (`Reception`)
- Incoming merchandise from suppliers.
- Purchase price per unit registration.
- Affects the daily cash balance (`daily_cash_id` on `Reception`).
- Created via `StoreReceptionAction`: resolves supplier/daily-cash/product-presentation UUIDs, creates `Reception` + `ReceptionItem`s inside a `DB::connection('tenant')->transaction()`, increments stock per item, and computes the running `total`.

### Reports (`app/Reports/`)
- One class per report domain: `SalesReport`, `OrdersReport`, `ClientsReport`, `ProductsReport`, `PurchasesReport`, `PaymentsReport`, `DailyCashesReport`, `InventoryReport`.
- Plain PHP classes (not Actions/Services) — they assemble query data + chart series for `ReportController`.
- Common pattern: `dateRange($filters)` (defaults to last 30 days), `resolveFilterIds($filters)` (UUID → internal id via `ByUuid` scope), `fillDailyChart()` (zero-fills missing days in a date range).
- Date range filters; granular permissions per report type (`list_report_*`).
- Excel export via `ReportExport` (`FromArray` + `WithHeadings` + `WithStyles` + `ShouldAutoSize`), styled with a bold white-on-indigo (`#4F46E5`) header row — keep this style consistent for new report exports.

### AI Assistant (`AssistantService`)
- Chat endpoint: `POST /api/v1/assistant/chat`, throttled `throttle:20,1`, behind `auth:sanctum`.
- Backed by `prism-php/prism`, provider `Groq`, model `meta-llama/llama-4-scout-17b-16e-instruct` (constant `AssistantService::MODEL`).
- System prompt is built per-request with the **current tenant name** and enforces tenant isolation: the assistant must refuse to discuss other tenants and must always call a tool before answering data questions (no hallucinated numbers).
- Tools are tenant-scoped read-only queries (stock, low stock, composite products, promotions, sales summary, recent sales/orders/quotes/receptions, daily cash status, product movements, top-selling products, clients, suppliers, users).
- `ChatAssistantRequest` validates `messages` (array, 1–50 items, each `role` in `user|assistant`, `content` max 2000 chars).
- **When adding new assistant tools**: keep them read-only, scope every query to the current tenant (rely on the tenant DB connection — never accept a tenant identifier from the LLM), and avoid exposing PII (emails, full user lists) unless the requesting user already has permission to see that data through the normal UI. Review `usersTool`/`clientsTool`/`suppliersTool` outputs whenever fields are added — only return what's needed for the assistant's stated purpose.
- `GROQ_API_KEY` env var. Config in `config/prism.php`.

### Administration
- User, role, and permission management.
- General system configuration (`Customization` model: logo, colors, font — surfaced via `HandleInertiaRequests`).
- Client, supplier, courier, and point-of-sale management.
- Definition of sale/order states, payment methods, movement types.
- Notifications: `NotificationController` (`index`, `unread-count`, `read-all`, `{id}/read`, `destroy`) backed by Laravel's database notifications.

## Directory Structure

```
app/
  Actions/                    # Complex business operations only (not simple CRUD)
  Adapters/                   # External API integrations
  Constants/                  # App-wide constants (e.g. FontFamilies)
  Console/Commands/           # Artisan commands (tenant migration, daily cash automation, legacy import)
  DTOs/{Model}/               # Data Transfer Objects (only when truly needed)
  Enums/                      # Backed enums for fixed value sets (DiscountType, SaleItemType, ...)
  Exceptions/                 # Domain exceptions (e.g. InsufficientStockException)
  Exports/                    # Excel export classes (Maatwebsite\Excel)
  Http/
    Controllers/              # Controllers: FormRequest → Eloquent+Scopes → Resource
      Central/                # Landlord admin controllers (AuthController, TenantController)
    Middleware/                # SetLocale, HandleInertiaRequests, EnsureActiveTenant, ...
    Requests/                 # FormRequests for validation
    Resources/{Model}/        # API Resources (never raw models)
  Imports/                    # Excel importers (Maatwebsite)
  Jobs/                       # Queued jobs (always ShouldQueue)
  Models/                     # Eloquent models (relationships, scopes, helpers only)
    Scopes/                   # Cross-model scopes (ByUuid, ByUuids, Active, CreatedAfter, CreatedOn)
    {Model}/
      Scopes/                 # Model-specific scope classes (never raw where() in models)
  Notifications/              # Laravel notifications (LowStockNotification, ...)
  Providers/                  # AppServiceProvider (morph map, Sanctum PAT model, withScopes macro)
  Reports/                     # Report query/aggregation classes (one per domain)
  Services/                   # Multi-step workflows (e.g. ProcessSaleService, AssistantService)
database/
  factories/                  # Model factories (one per model)
  migrations/                 # Central DB migrations
  migrations/tenant/          # Per-tenant migrations
  seeders/                    # PermissionSeeder, etc.
lang/
  es/, en/                    # Translation files, grouped by domain
resources/
  help/                       # Markdown/content backing the in-app Help section
  js/                         # Vue 3 components (Inertia pages)
    Pages/                    # Inertia page components
      Central/                # Landlord admin pages (Login, Tenants)
      Help/                   # In-app help pages (kept in sync with HelpController)
    Components/               # Reusable Vue components
    Layouts/                  # AppLayout and variants
    composables/              # Vue composables (useApi, etc.)
routes/
  api.php                     # Entry point for API routing — requires v1.php (and future versions)
  v1.php                       # All v1 tenant API routes, prefixed /v1, under ['api','tenant','tenant.active']
  central.php                  # Landlord admin routes, domain-constrained, registered first
  web.php                       # Tenant Inertia SPA page renders + tenant web auth
  console.php                  # Scheduled commands (daily-cash:auto-manage, etc.)
tests/
  Unit/
    Models/
    Scopes/
    Actions/
    Services/
    Requests/
    Resources/
  Feature/
    Controllers/
    Jobs/
```

## Architecture Rules

### When to use each layer

| Layer | Use when | Skip when |
|---|---|---|
| **Action** | Logic called from 2+ places (Controller + Job + Command), or complex with side effects (events, stock changes) | Simple CRUD with no reuse |
| **Service** | Multi-step workflow coordinating multiple Actions or external calls | Single-step operations |
| **Repository** | Complex queries: multiple joins, subqueries, aggregations, reused across features | Simple `where` + `orderBy` — use Eloquent + Scopes directly |
| **DTO** | Data built from multiple sources (Request + CSV + API), or passed through multiple layers | Simple `$request->validated()` passed directly to `Model::create()` |
| **Report** | Read-only aggregation/query logic feeding a report endpoint + Excel export | Anything that mutates data — use Action/Service instead |

### Simple CRUD (parametrization tables, basic entities)
Controller queries Eloquent directly using Scopes. No Actions, no DTOs, no Repositories.

```php
public function index(Request $request): AnonymousResourceCollection
{
    $query = ProductType::query();
    if ($request->filled('search')) {
        $query->withScopes(new BySearch($request->string('search')));
    }
    return ProductTypeResource::collection($query->orderBy('name')->get());
}

public function store(StoreProductTypeRequest $request): ProductTypeResource
{
    return ProductTypeResource::make(ProductType::create($request->validated()));
}
```

### Complex business operations (Sales, Orders, DailyCash, Receptions)
Controller → Action/Service → Eloquent + Scopes → Resource.

- Wrap multi-step writes in `DB::connection('tenant')->transaction(function () { ... })`.
- Resolve any UUIDs passed from the frontend to internal IDs **inside** the Action/Service via `Model::query()->withScopes(new ByUuid($uuid))->value('id')` (or `firstOrFail()` when the record must exist).

5. **Adapters** = all external API calls (`app/Adapters/`). Never call external APIs directly. Errors → domain exceptions. Config → `config/services.php`.
6. **Events** decouple side effects. Flow: `Action → Event::dispatch() → Listener → Job (optional)`.
7. **Jobs** handle heavy/background processing. Always implement `ShouldQueue`.
8. **Console Commands** orchestrate cross-tenant operations (`Tenant::all()->each(fn ($t) => $t->run(fn () => ...))`) but delegate the actual work to Actions/Services — keep command classes thin.

## API Responses

1. Always return **Resources**, never raw models. Use `ResourceName::make($model)` or `ResourceName::collection($collection)`.
2. Never use `compact()` for API responses — only for Blade/Inertia shared data if needed.
3. Domain exceptions are mapped to HTTP responses centrally in `bootstrap/app.php` via `$exceptions->render(...)` (e.g. `InsufficientStockException` → 422 JSON `{message: ...}`). Add new domain exceptions there rather than catching them ad-hoc in controllers.

## Data Layer

### Responsibilities

| Concept | Responsibility | Direction |
|---|---|---|
| **FormRequest** | Validate and sanitize HTTP input | Front → Back |
| **DTO** | Transport typed data between internal layers | Internal |
| **Mapper** | Transform between structures (DTO ↔ Model, external ↔ internal) | Internal |
| **Resource** | Transform Model to JSON output | Back → Front |

### Flow — Simple CRUD
```
Request → FormRequest → Controller → Model (via Scopes)
                                          ↓
Front  ← Resource ←─────────────────── Model
```

### Flow — Complex operations
```
Request → FormRequest → Controller → Action/Service → Model (via Scopes)
                                                            ↓
Front  ← Resource ←──────────────────────────────────── Model
```

### DTOs

Only create a DTO when:
- The same data structure is built from multiple sources (HTTP request + CSV import + external API).
- Data must travel through multiple layers (Controller → Service → Job).
- A `readonly` typed structure meaningfully improves clarity over `$request->validated()`.

For simple CRUD, use `$request->validated()` directly. Do not create DTOs just to have them.

### Resources

1. Use Resources exclusively for HTTP output. Never use them inside Services, Actions, or Repositories.
2. Different endpoints with different data → separate Resources. No inheritance between Resources.
3. Use `whenLoaded()` for relationships to avoid N+1. Use `when()` for conditional fields.

## Models

1. Models may contain relationships, scopes, and small helpers. No business logic.
2. Every new model needs a **Factory** + **Model Test** with these three cases:
    - `it_has_expected_columns()` → use `assertHasExpectedColumns(Model::tableName(), [...])`
    - `it_extends_from_custom_model()` → assert `instanceof Model`
    - Relations → separate file `tests/Unit/Models/{Model}/RelationsTest.php`, one test per relation.
3. **No raw Eloquent queries** (`where`, `orWhere`, etc.). Always use a Scope class and apply via `::withScopes(new ScopeName(...))` (custom macro registered in `AppServiceProvider::boot()`).

    ```php
    class ByName implements Scope
    {
        public function __construct(private string $name) {}
        public function apply(Builder $builder, Model $model): void
        {
            $builder->where('name', $this->name);
        }
    }
    ```

    - **Shared/cross-model scopes** live in `App\Models\Scopes\` (e.g. `ByUuid`, `ByUuids`, `Active`, `CreatedAfter`, `CreatedOn`) — reuse these instead of redefining per model.
    - **Model-specific scopes** live in `App\Models\{Model}\Scopes\` (e.g. `App\Models\Sale\Scopes\ByClient`).
4. **Morph relationships**: `morphTo` on the morph model and `morphOne`/`morphMany` on parents. Register morph aliases centrally in `AppServiceProvider::boot()` via `Relation::morphMap([...])` — never rely on FQCN morph types in the database. Current map: `sale`, `order`, `payment`, `product_presentation`, `composite_product`, `promotion`.
5. **Enums for fixed value sets** live in `app/Enums/` as backed enums (`string` backing). When an enum value also has to map onto a morph type or another representation, add explicit methods (`morphType()`, `fromMorphType()`) rather than scattering `match()` calls across the codebase — see `SaleItemType` as the reference pattern.

## Code Conventions

- All code, table names, model names, and routes **in English**.
- The application is multilingual (i18n via Laravel's `lang/` files). No hardcoded user-facing strings.
- RESTful API routes: `GET /resource`, `POST /resource`, `GET /resource/{id}`, `PUT /resource/{id}`, `DELETE /resource/{id}`. Prefer `Route::apiResource(...)->except([...])`/`->only([...])` over listing routes manually.
- All tenant API routes live under `routes/v1.php`, mounted at `/v1` with `['api', 'tenant', 'tenant.active']` middleware, and protected per-route by `auth:sanctum`. When introducing breaking changes, add `routes/v2.php` rather than mutating v1.
- Soft deletes on users and business entities.
- Cascade delete on parent-child relationships.
- Use computed attributes via Eloquent accessors (modern syntax, no `getXxxAttribute`).
- Models return stock on deletion (`returnStock()`). Any controller that deletes a model with stock implications (sales, orders, receptions, movements) must call this before/within the delete — never a bare `$model->delete()`.
- No magic strings. Constants go in `app/Constants/` (e.g. `FontFamilies`). Fixed value sets go in `app/Enums/` instead of constants when they represent a closed set of states/types.
- Use Enums for fixed value sets.
- Always use **constructor injection**. Never `new ServiceName()` manually.
- Domain-specific exceptions only (e.g. `InsufficientStockException`). Use structured logging. Map new domain exceptions to HTTP responses in `bootstrap/app.php`.
- Classes: `PascalCase` · Methods/variables: `camelCase` · Constants: `UPPER_CASE`
- Prefer `config(...)` over `env(...)` outside of config files — `env()` calls in application code break once config is cached (`php artisan config:cache`). If a value needs to be read at runtime, add it to a `config/*.php` file and read it via `config()`.
- **After modifying any PHP file, always run `vendor/bin/pint --dirty --format agent` to apply code style before finalizing.** Never skip this step — failing to run Pint after edits breaks the project's formatting.

## Security Checklist (apply on every PR touching auth, controllers, or Actions)

- [ ] Every new controller action that returns data has an explicit `middleware('permission:...')` or is intentionally public (documented why).
- [ ] User-supplied strings that end up in raw SQL identifiers (table/database names, column names) are validated against a strict allow-list/regex **at the point of use**, not only upstream in a FormRequest.
- [ ] Destructive endpoints (`destroy`, `toggle`, password changes) on `User`/`Admin` check that the acting user isn't operating on their own account when that would lock them out or break tenant admin access.
- [ ] Password fields are written through the model's `hashed` cast — never assigned as plain strings to `update()`/`create()`.
- [ ] Deletions of stock-affecting models (`Sale`, `Order`, `Reception`, `ProductMovement`) revert stock and check the record's current state before allowing deletion.
- [ ] No `env()` calls outside `config/*.php`.
- [ ] No debug/scratch files committed under `public/` (e.g. `phpinfo()` dumps, test text files) — `public/` is served directly and anything there is world-readable.
- [ ] New AI Assistant tools are read-only, tenant-scoped via the tenant DB connection (never via an LLM-supplied identifier), and don't leak more PII than the requesting user already has access to via the UI.

## Migrations

- Table names are **plural**, except pivot tables (singular, underscore-joined, alphabetical order).
- Morph migrations: **do not create indexes** on morph columns.
- Ask before creating a migration: does this table need `uuid`? (only if exposed to frontend).

## Webhooks

- Webhooks are **incoming**: respond immediately with `204 No Content`.
- Always verify the signature before any processing.
- Dispatch a **Job** for all actual processing. Never process synchronously inside the controller.

## Testing

**Every piece of code gets tests. No exceptions.**

### What to test and where

| Subject | Type | Location |
|---|---|---|
| Model columns, casts, traits | Unit | `tests/Unit/Models/{Model}/ModelTest.php` |
| Model relationships | Unit | `tests/Unit/Models/{Model}/RelationsTest.php` |
| Scope classes | Unit | `tests/Unit/Scopes/{Model}/...Test.php` (shared scopes under `tests/Unit/Scopes/...Test.php`) |
| Actions / Services | Unit | `tests/Unit/Actions/...` / `tests/Unit/Services/...` |
| FormRequests (validation rules) | Unit | `tests/Unit/Requests/...` |
| API Resources (output shape) | Unit | `tests/Unit/Resources/...` |
| API endpoints (full HTTP cycle) | Feature | `tests/Feature/Controllers/...` |
| Jobs | Feature | `tests/Feature/Jobs/...` |
| Event + Listener wiring | Feature | `tests/Feature/Events/...` |
| Console Commands (cross-tenant) | Feature | `tests/Feature/Commands/...` |

### Rules
- Use `php artisan make:test --phpunit {name}` for feature tests, `--unit` for unit tests.
- Feature tests cover: happy path, validation errors, auth errors (401/403), not-found (404).
- Use model factories, never manual `DB::insert()`.
- Tenant model/feature tests extend `ModelTestCase` / `TenantFeatureTestCase`, which migrate the tenant DB once per suite.
- API endpoint tests must hit the **versioned** path (`/api/v1/...`) — double-check the URL prefix matches `routes/v1.php` exactly (no duplicated `v1/v1` segments, no missing version).
- Run the affected test file after every change: `php artisan test --compact tests/Feature/Controllers/ProductTypeControllerTest.php`
- After finishing a feature, run the full suite: `php artisan test --compact`

## Feature Development Workflow

Order when building a new feature:
1. Migration (if needed)
2. Model + Factory + Model Test
3. Enum(s), if the feature introduces a new fixed value set
4. Scopes + Scope Tests
5. FormRequest + Request Test
6. Resource + Resource Test
7. Controller (simple CRUD: Eloquent direct; complex: Action/Service first)
8. Routes (add to `routes/v1.php`, keep grouped by module with comment headers)
9. Feature Test (Controller endpoints)
10. Frontend (Vue page)
11. Help section update (`resources/js/Pages/Help/` + `HelpController`) if user-facing behaviour changed
12. Translations (`lang/es/*.php`, `lang/en/*.php`) for any new user-facing strings

## Git & Branching

- Branch format:
    - `feature/title-in-kebab-case`
    - `bug/title-in-kebab-case`

## Pull Requests

Describe what was done with an example if necessary.

## Changelog

Add entry to `CHANGELOG.md` under the current version section:
- `### Added` for features · `### Fixed` for bugs
- Format: `Task title`

## Key Environment Variables

```
APP_URL=http://localhost
SUBDOMAIN_URL="development."
EXT_SUBDOMAIN_URL=".com"
CENTRAL_DOMAIN="development.central.in-ventra.com"
WEBDOMAIN="127.0.0.1"
DB_DATABASE=inventra
TENANCY_DATABASE=inventra

# Sanctum
SANCTUM_TOKEN_EXPIRATION=10080
SANCTUM_TOKEN_PREFIX=inventra_

# AI Assistant (Groq, via Prism)
GROQ_API_KEY=
```

> `.env.testing` mirrors these with SQLite connections for central and tenant DBs (`DB_CONNECTION=sqlite`, `DB_TENANT_DRIVER=sqlite`). Its `APP_KEY` is a fixed test-only value — never reuse it outside the `testing` environment.

## Common Commands

```bash
php artisan migrate                           # Central DB migrations
php artisan tenant:migrate                    # Run migrations on all tenant DBs (use --tenant=ID for one)
php artisan tenant:migrate --fresh --seed     # Fresh + seed all tenant DBs
php artisan central:create-admin --email=... --password=...  # Create/update a central admin
php artisan daily-cash:auto-manage            # Run the auto open/close cycle once (also scheduled)
php artisan serve
npm run dev
npm run build
php artisan tinker
php artisan route:list
php artisan queue:work
```

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.2
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v11
- @inertiajs/vue3 (INERTIA_VUE) - v3
- vue (VUE) - v3
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `laravel-best-practices` — Apply this skill whenever writing, reviewing, or refactoring Laravel PHP code. This includes creating or modifying controllers, models, migrations, form requests, policies, jobs, scheduled commands, service classes, and Eloquent queries. Triggers for N+1 and query performance issues, caching strategies, authorization and security patterns, validation, error handling, queue and job configuration, route definitions, and architectural decisions. Also use for Laravel code reviews and refactoring existing Laravel code to follow best practices. Covers any task involving Laravel backend PHP code patterns.
- `inertia-vue-development` — Develops Inertia.js v3 Vue client-side applications. Activates when creating Vue pages, forms, or navigation; using <Link>, <Form>, useForm, useHttp, setLayoutProps, or router; working with deferred props, prefetching, optimistic updates, instant visits, or polling; or when user mentions Vue with Inertia, Vue pages, Vue forms, or Vue navigation.
- `tailwindcss-development` — Always invoke when the user's message includes 'tailwind' in any form. Also invoke for: building responsive grid layouts (multi-column card grids, product grids), flex/grid page structures (dashboards with sidebars, fixed topbars, mobile-toggle navs), styling UI components (cards, tables, navbars, pricing sections, forms, inputs, badges), adding dark mode variants, fixing spacing or typography, and Tailwind v3/v4 work. The core use case: writing or fixing Tailwind utility classes in HTML templates (Blade, JSX, Vue). Skip for backend PHP logic, database queries, API routes, JavaScript with no HTML/CSS component, CSS file audits, build tool configuration, and vanilla CSS.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.
- To check environment variables, read the `.env` file directly.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
    - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app/Console/Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

=== inertia-vue/core rules ===

# Inertia + Vue

Vue components must have a single root element.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

</laravel-boost-guidelines>

## Workflow Orchestration

### Plan Mode Default
- Enter plan mode for ANY non-trivial task (3+ steps or architectural decisions)
- If something goes sideways, STOP and re-plan immediately – don't keep pushing
- Use plan mode for verification steps, not just building
- Write detailed specs upfront to reduce ambiguity

### Subagent Strategy
- Use subagents liberally to keep main context window clean
- Offload research, exploration, and parallel analysis to subagents
- For complex problems, throw more compute at it via subagents
- One task per subagent for focused execution

### Self-Improvement Loop
- After ANY correction from the user: update `tasks/lessons.md` with the pattern
- Write rules for yourself that prevent the same mistake
- Ruthlessly iterate on these lessons until mistake rate drops
- Review lessons at session start for relevant project

### Verification Before Done
- Never mark a task complete without proving it works
- Diff behavior between main and your changes when relevant
- Ask yourself: "Would a staff engineer approve this?"
- Run tests, check logs, demonstrate correctness

### Demand Elegance (Balanced)
- For non-trivial changes: pause and ask "is there a more elegant way?"
- If a fix feels hacky: "Knowing everything I know now, implement the elegant solution"
- Skip this for simple, obvious fixes – don't over-engineer
- Challenge your own work before presenting it

### Autonomous Bug Fixing
- When given a bug report: just fix it. Don't ask for hand-holding
- Point at logs, errors, failing tests – then resolve them
- Zero context switching required from the user
- Go fix failing CI tests without being told how

Task Management

1. **Plan First**: Write plan to `tasks/todo.md` with checkable items
2. **Verify Plan**: Check in before starting implementation
3. **Track Progress**: Mark items complete as you go
4. **Explain Changes**: High-level summary at each step
5. **Document Results**: Add review section to `tasks/todo.md`
6. **Capture Lessons**: Update `tasks/lessons.md` after corrections

## Core Principles

- **Simplicity First**: Make every change as simple as possible. Impact minimal code.
- **No Laziness**: Find root causes. No temporary fixes. Senior developer standards.
- **Minimal Impact**: Changes should only touch what's necessary. Avoid introducing bugs.


---

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.2
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v11
- @inertiajs/vue3 (INERTIA_VUE) - v3
- vue (VUE) - v3
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `laravel-best-practices` — Apply this skill whenever writing, reviewing, or refactoring Laravel PHP code. This includes creating or modifying controllers, models, migrations, form requests, policies, jobs, scheduled commands, service classes, and Eloquent queries. Triggers for N+1 and query performance issues, caching strategies, authorization and security patterns, validation, error handling, queue and job configuration, route definitions, and architectural decisions. Also use for Laravel code reviews and refactoring existing Laravel code to follow best practices. Covers any task involving Laravel backend PHP code patterns.
- `inertia-vue-development` — Develops Inertia.js v3 Vue client-side applications. Activates when creating Vue pages, forms, or navigation; using <Link>, <Form>, useForm, useHttp, setLayoutProps, or router; working with deferred props, prefetching, optimistic updates, instant visits, or polling; or when user mentions Vue with Inertia, Vue pages, Vue forms, or Vue navigation.
- `tailwindcss-development` — Always invoke when the user's message includes 'tailwind' in any form. Also invoke for: building responsive grid layouts (multi-column card grids, product grids), flex/grid page structures (dashboards with sidebars, fixed topbars, mobile-toggle navs), styling UI components (cards, tables, navbars, pricing sections, forms, inputs, badges), adding dark mode variants, fixing spacing or typography, and Tailwind v3/v4 work. The core use case: writing or fixing Tailwind utility classes in HTML templates (Blade, JSX, Vue). Skip for backend PHP logic, database queries, API routes, JavaScript with no HTML/CSS component, CSS file audits, build tool configuration, and vanilla CSS.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.
- To check environment variables, read the `.env` file directly.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app/Console/Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

=== inertia-vue/core rules ===

# Inertia + Vue

Vue components must have a single root element.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

</laravel-boost-guidelines>

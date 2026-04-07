# Inventra — Claude Guide

## Project Description

**Inventra** is a multi-tenant SaaS system for inventory, sales, and order management aimed at small and medium businesses. Each client company operates on its own subdomain with an isolated database.

## Tech Stack

| Layer | Technology                                      |
|-------|-------------------------------------------------|
| Backend | PHP 8.5 / Laravel 13                            |
| ORM | Eloquent                                        |
| Database | MySQL                                           |
| Multi-tenancy | spatie/laravel-multitenancy (one DB per tenant) |
| Permissions | spatie/laravel-permission (latest, RBAC)        |
| PDF | barryvdh/laravel-dompdf (latest)                |
| Excel | maatwebsite/excel (latest)                      |
| Frontend | Inertia.js + Vue 3 + Vite                       |
| Tests | PHPUnit (latest) + Mockery                      |

## Multi-tenant Architecture (Spatie)

- **Central database** (`inventra`): `tenants` and `domains` tables from spatie/laravel-multitenancy, plus company data.
- **Per-tenant databases**: one per client, created automatically. Contain all business tables.
- Tenant identification via hostname using Spatie's `DomainTenantFinder`.
- Models that belong to tenant DB use the tenant connection configured via `SwitchTenantDatabaseTask`.
- Key env vars: `CENTRAL_DOMAIN`, `SUBDOMAIN_URL`, `EXT_SUBDOMAIN_URL`, `TENANCY_DATABASE`.

## Authentication & Authorization

- Laravel standard auth (API via Sanctum), separate login for central domain vs. tenants.
- RBAC with spatie/laravel-permission (latest). `Role` and `Permission` models scoped per tenant.
- Routes protected with `middleware('permission:permission_name')`.
- Permission conventions: `list_*`, `create_edit_delete_*`, specific permissions for critical operations (e.g. `enable_close_daily_cash`, `bulk_update_product_price`).

## Main Modules

### Sales (`Sale`)
- Support for simple items, composite products, and promotions.
- Percentage and fixed-amount discounts.
- Multiple payment methods per sale.
- Stock validation before confirming.
- Configurable states (with `default` and `final_state` flags).
- Conversion to delivery order.

### Orders (`Order`)
- Delivery orders linked to a sale.
- Courier (delivery person) assignment.
- Configurable state machine.
- Stock deduction on delivery confirmation.

### Quotes (`Quote`)
- Pre-sale quotations.
- PDF generation (DOMPDF).
- Conversion to sale or order.

### Inventory (`Product`)
- Hierarchical categories (`ProductType`, self-referential).
- Available stock + minimum threshold.
- Multiple barcodes per product.
- Composite products (kits) and promotions.
- Bulk import via Excel.
- Extra movements (adjustments, losses, corrections).
- **Presentation** (`Presentation`): defines how a product is measured/sold (e.g. grams, liters, units).
- **Presentation Type** (`PresentationType`): the unit of measure category (weight, volume, unit, etc.).

### Daily Cash (`DailyCash`)
- Opening and closing of cash register per point of sale.
- Automatic registration of received payments.
- Extra movements (deposits, withdrawals) with configurable types.
- Reconciliation and balance verification.
- Linked to supplier receptions.

### Receptions (`Reception`)
- Incoming merchandise from suppliers.
- Purchase price per unit registration.
- Affects the daily cash balance.

### Reports
- Lists and charts for: sales, orders, payments, cash movements, products.
- Date range filters.
- Granular permissions per report type (`list_report_*`).

### Administration
- User, role, and permission management.
- General system configuration.
- Client, supplier, courier, and point-of-sale management.
- Definition of sale/order states, payment methods, movement types.

## Directory Structure

```
app/
  Actions/                    # Single business operations (stateless)
  Adapters/                   # External API integrations
  Constants/                  # App-wide constants
  DTOs/{Model}/               # Data Transfer Objects (readonly)
  Http/
    Controllers/              # Thin controllers: validate → Action → Resource
    Requests/                 # FormRequests for validation
    Resources/{Model}/        # API Resources (never raw models)
  Jobs/                       # Queued jobs (always ShouldQueue)
  Mappers/                    # DTO ↔ Model / external ↔ internal transformations
  Models/                     # Eloquent models (relationships, scopes, helpers only)
    {Model}/
      Scopes/                 # Scope classes (never raw where() in models)
  Repositories/               # All complex data access
  Services/                   # Multi-step workflows (e.g. NewSaleService)
  Imports/                    # Excel importers (Maatwebsite)
database/
  migrations/                 # Central DB migrations
  migrations/tenant/          # Per-tenant migrations
resources/
  js/                         # Vue 3 components (Inertia pages)
    Pages/                    # Inertia page components
    Components/               # Reusable Vue components
routes/
  api.php                     # All API routes (primary)
  web.php                     # Minimal: login redirects, Inertia SPA entry
tests/
  Unit/
    Actions/
    Services/
    Repositories/
    Models/
  Feature/
    Controllers/
    Jobs/
```

## Architecture Rules

1. **Controllers** are thin: validate via FormRequest → call Action/Service → return Resource. No business logic, no direct model queries, no manual validation.
2. **Actions** = single business operation. Stateless, no HTTP dependencies, constructor injection. Usable from Controllers, Commands, Jobs, Listeners.
3. **Services** = multi-step workflows. Use when multiple Actions or external services must be coordinated.
4. **Repositories** = all complex data access. Controllers and Actions never query models directly.
5. **Adapters** = all external API calls (`app/Adapters/`). Never call external APIs directly. Errors → domain exceptions. Config → `config/services.php`.
6. **Events** decouple side effects. Flow: `Action → Event::dispatch() → Listener → Job (optional)`.
7. **Jobs** handle heavy/background processing. Always implement `ShouldQueue`.

## API Responses

1. Always return **Resources**, never raw models. Use `ResourceName::make($model)` or `ResourceName::collection($collection)`.
2. Never use `compact()` for API responses — only for Blade/Inertia shared data if needed.

## Data Layer

### Responsibilities

| Concept | Responsibility | Direction |
|---|---|---|
| **FormRequest** | Validate and sanitize HTTP input | Front → Back |
| **DTO** | Transport typed data between internal layers | Internal |
| **Mapper** | Transform between structures (DTO ↔ Model, external ↔ internal) | Internal |
| **Resource** | Transform Model to JSON output | Back → Front |

### Flow

```
Request → FormRequest → Controller → DTO → Action/Service → [Mapper] → Repository → Model
                                                                                        ↓
Front ← Resource ←──────────────────────────────────────────────────────────────── Model
```

### DTOs

1. Use DTOs to decouple business logic from HTTP. Built in the Controller from a validated FormRequest.
2. Always `readonly`. No logic, no queries, no side effects.
3. One DTO per business intent: `CreateClientDTO` and `UpdateClientDTO` are separate even if they share fields. Optional fields use nullable defaults.
4. Never exposed to the front. DTOs are internal only.

### Mappers

1. Add a Mapper when: transformation involves logic (formatting, conversions), the same DTO is built from multiple sources (Request, CSV, external API), or a Model needs to be converted to an internal DTO.
2. Simple one-liner mappings stay in the DTO's `fromRequest()`. No Mapper needed.

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
3. **No raw Eloquent queries** (`where`, `orWhere`, etc.). Always use a Scope class in `App\Models\{Model}\Scopes\` and apply via `::scopes(new ScopeName(...))`.

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

4. **Morph relationships**: `morphTo` on the morph model and `morphOne`/`morphMany` on parents.

## Code Conventions

- All code, table names, model names, and routes **in English**.
- The application is multilingual (i18n via Laravel's `lang/` files). No hardcoded user-facing strings.
- RESTful API routes: `GET /resource`, `POST /resource`, `GET /resource/{id}`, `PUT /resource/{id}`, `DELETE /resource/{id}`.
- Soft deletes on users and business entities.
- Cascade delete on parent-child relationships.
- Use computed attributes via Eloquent accessors (modern syntax, no `getXxxAttribute`).
- Models return stock on deletion (`returnStock()`).
- No magic strings. Constants go in `app/Constants/`.
- Use Enums for fixed value sets.
- Always use **constructor injection**. Never `new ServiceName()` manually.
- Domain-specific exceptions only (e.g. `StockException`). Use structured logging.
- Classes: `PascalCase` · Methods/variables: `camelCase` · Constants: `UPPER_CASE`

## Migrations

- Table names are **plural**, except pivot tables (singular, underscore-joined, alphabetical order).
- Morph migrations: **do not create indexes** on morph columns.
- Ask before creating a migration: does this table need `uuid`? (only if exposed to frontend).

## Webhooks

- Webhooks are **incoming**: respond immediately with `204 No Content`.
- Always verify the signature before any processing.
- Dispatch a **Job** for all actual processing. Never process synchronously inside the controller.

## Testing

- Unit tests: Actions, Services, Repositories, Rules.
- Feature tests: Controllers, Jobs, Events, Webhooks.
- Every new feature must include both.

## Feature Development Workflow

Order when building a new feature: Action → FormRequest → Controller → Resource → Events/Listeners (if needed) → Unit Tests → Feature Tests.

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
CENTRAL_DOMAIN="development.central.inventra.com"
WEBDOMAIN="127.0.0.1"
DB_DATABASE=inventra
TENANCY_DATABASE=inventra
```

## Common Commands

```bash
php artisan migrate                           # Central DB migrations
php artisan tenants:artisan "migrate"         # Run migrations on all tenant DBs
php artisan serve
npm run dev
npm run build
php artisan tinker
php artisan route:list
php artisan queue:work
```

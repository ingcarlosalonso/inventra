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

## Internationalisation (i18n)

- The application is **fully multilingual**. Every user-facing string MUST use Laravel's translation helpers (`__()`, `trans()`, `@lang`).
- **Never hardcode** Spanish or English text in PHP or Vue files.
- Translation files live in `lang/{locale}/` (e.g. `lang/es/`, `lang/en/`).
- Group keys by domain: `lang/es/products.php`, `lang/es/clients.php`, `lang/es/common.php`, etc.
- Vue components use the `$t()` helper from the Inertia shared translations (passed via `HandleInertiaRequests`).
- Default locale: `es` (Spanish). Fallback: `en`.

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
```

## UI / Frontend Guidelines

- Design must feel **modern, clean, and intuitive**. Avoid dated table-heavy layouts.
- Use a **fixed sidebar** with grouped navigation and icons.
- **Top bar** with tenant name, quick-action buttons, and user menu.
- List pages: searchable, with status badges, row actions (edit/delete), and an "Add" button.
- Forms: slide-over panels or dedicated pages — never modal dialogs for complex forms.
- Use **consistent color tokens** via Tailwind CSS v4 variables.
- Empty states, loading skeletons, and inline validation feedback are required.
- All UI text goes through `$t()`.

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
  Actions/                    # Complex business operations only (not simple CRUD)
  Adapters/                   # External API integrations
  Constants/                  # App-wide constants
  DTOs/{Model}/               # Data Transfer Objects (only when truly needed)
  Http/
    Controllers/              # Controllers: FormRequest → Eloquent+Scopes → Resource
    Requests/                 # FormRequests for validation
    Resources/{Model}/        # API Resources (never raw models)
  Jobs/                       # Queued jobs (always ShouldQueue)
  Models/                     # Eloquent models (relationships, scopes, helpers only)
    {Model}/
      Scopes/                 # Scope classes (never raw where() in models)
  Services/                   # Multi-step workflows (e.g. ProcessSaleService)
  Imports/                    # Excel importers (Maatwebsite)
database/
  migrations/                 # Central DB migrations
  migrations/tenant/          # Per-tenant migrations
resources/
  js/                         # Vue 3 components (Inertia pages)
    Pages/                    # Inertia page components
    Components/               # Reusable Vue components
    Layouts/                  # AppLayout and variants
    composables/              # Vue composables (useApi, etc.)
routes/
  api.php                     # All API routes (primary)
  web.php                     # Minimal: Inertia SPA page renders
tests/
  Unit/
    Models/
    Scopes/
    Actions/
    Services/
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

### Complex business operations (Sales, Orders, DailyCash)
Controller → Action/Service → Eloquent + Scopes → Resource.

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

**Every piece of code gets tests. No exceptions.**

### What to test and where

| Subject | Type | Location |
|---|---|---|
| Model columns, casts, traits | Unit | `tests/Unit/Models/{Model}/ModelTest.php` |
| Model relationships | Unit | `tests/Unit/Models/{Model}/RelationsTest.php` |
| Scope classes | Unit | `tests/Unit/Scopes/{Model}/...Test.php` |
| Actions / Services | Unit | `tests/Unit/Actions/...` / `tests/Unit/Services/...` |
| FormRequests (validation rules) | Unit | `tests/Unit/Requests/...` |
| API Resources (output shape) | Unit | `tests/Unit/Resources/...` |
| API endpoints (full HTTP cycle) | Feature | `tests/Feature/Controllers/...` |
| Jobs | Feature | `tests/Feature/Jobs/...` |
| Event + Listener wiring | Feature | `tests/Feature/Events/...` |

### Rules
- Use `php artisan make:test --phpunit {name}` for feature tests, `--unit` for unit tests.
- Feature tests cover: happy path, validation errors, auth errors (401/403), not-found (404).
- Use model factories, never manual `DB::insert()`.
- Tenant model tests extend `ModelTestCase` which migrates the tenant DB once per suite.
- Run the affected test file after every change: `php artisan test --compact tests/Feature/Controllers/ProductTypeControllerTest.php`
- After finishing a feature, run the full suite: `php artisan test --compact`

## Feature Development Workflow

Order when building a new feature:
1. Migration (if needed)
2. Model + Factory + Model Test
3. Scopes + Scope Tests
4. FormRequest + Request Test
5. Resource + Resource Test
6. Controller (simple CRUD: Eloquent direct; complex: Action/Service first)
7. Routes
8. Feature Test (Controller endpoints)
9. Frontend (Vue page)

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
php artisan tenants:artisan "migrate --path=database/migrations/tenant --database=tenant --force"  # Run migrations on all tenant DBs
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

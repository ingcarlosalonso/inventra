# Changelog

All notable changes to In-ventra are documented here.

---

## [1.1.0] - xxxx-xx-xx

### Added
- Release notes system: central admin can draft, edit, and publish release notes parsed from CHANGELOG.md; users see a "What's New" popup on first login after each new release
- Per-release deploy task automation (`deploy/release_tasks.sh`): version-specific one-off commands (seeders, ad-hoc artisan commands) declared per release and run idempotently by `deploy_project.sh` after migrations
- Removed one-time server provisioning scripts from `deploy/` now that production is already set up and running; `DEPLOYMENT_GUIDE.md` trimmed to cover only ongoing operations

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

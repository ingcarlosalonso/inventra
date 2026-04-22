# Changelog

All notable changes to In-ventra are documented here.

## [1.0.0] - 2026-04-21

### Added
- Multi-tenant SaaS architecture with per-tenant isolated databases (spatie/laravel-multitenancy)
- RBAC permissions system (spatie/laravel-permission)
- Products module with types, presentations, composite products, promotions, barcodes, bulk price update, and Excel import
- Sales module with multiple payment methods, discounts, and configurable states
- Orders module with couriers, delivery management, and state machine
- Quotes module with PDF generation and conversion to sale/order
- Daily Cash module with opening/closing, extra movements, and reconciliation
- Receptions module for incoming merchandise from suppliers
- Clients and Suppliers management
- Reports module: sales, orders, payments, inventory, daily cashes, clients, purchases (with Excel export)
- AI Assistant powered by Groq (Llama 4 Scout) with inventory and sales tools
- Dashboard with KPIs, charts, low stock alerts, and open cash registers summary
- Full multilingual support (Spanish default, English fallback)
- Notification system for low stock alerts
- User, role, and permission management per tenant

### Security
- Rate limiting on login endpoint (5 attempts per minute)
- Permission middleware on sensitive routes (users, roles, bulk price)
- Authenticated locale switching
- Stock reversion on sale/order/reception deletion
- Soft deletes on CashMovement and ProductMovement for audit trail integrity
- Composite index on payments (payable_type, payable_id)
- Performance indexes on sales.created_at, products.is_active, orders.scheduled_at

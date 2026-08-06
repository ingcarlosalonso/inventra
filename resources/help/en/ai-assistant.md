# AI Assistant

> **Note**: The AI Assistant is a separately contracted module. If you don't see the icon in the top bar, contact your account administrator to enable it.

The AI Assistant is available from the icon in the top bar, on any screen. It answers questions about your business and, as of this version, can also **create sales, orders and quotes** for you.

## Asking questions

You can ask it in plain language about:

- Product stock and prices, low-stock products.
- Composite products (kits) and active promotions.
- Recent sales, orders, quotes and receptions.
- Daily cash register status and product movements.
- Clients, suppliers and system users.
- Best-selling products for a date range.

The assistant only answers with real data from your company — it never invents numbers. If it can't find something, it will say so explicitly.

## Creating sales, orders and quotes by chat

You can ask it directly, for example:

> "Create a sale for John Smith with 2 units of Product A and 1 of Product B, no discount, paid in cash."

> "I need an order for Mary Jones, 3 units of Product C, delivered to 123 Main St, leave it on credit."

> "Put together a quote for 5 units of Product D for client Southern Distributors, valid until the end of the month."

### How it works

1. The assistant looks up the client and products you mentioned and builds a **draft** with the details and the calculated total — nothing is created yet.
2. It shows you that draft to review.
3. Only once you explicitly confirm ("yes", "go ahead", "confirm"), the assistant actually creates the sale, order or quote.

If something is ambiguous (for example, two clients or two products match what you asked for), the assistant will ask you to clarify before continuing — it never picks on its own.

### What it can include

- **Client**: optional. If you don't mention one, the sale or order is created without a client.
- **Products**: plain products only for now (no kits or promotions) — the same limitation the manual creation screens have today.
- **Discount**: an overall discount for the whole sale/order/quote (percentage or fixed amount).
- **Payments** (sales and orders): you can specify one or more payment methods with an amount. If you don't mention any payment, the sale or order is left **on credit/debt** — that's a valid outcome, not an error.
- **Orders**, additionally: delivery address, courier and scheduled date.
- **Quotes**, additionally: expiration date.

### Permissions

For the assistant to create a sale, order or quote, your user needs the same permission used by the manual screen (**Create/Edit/Delete Sales**, **Orders** or **Quotes**, as applicable). If you don't have it, the assistant will tell you instead of attempting the action.

## Privacy and scope

The assistant only has access to your company's data. It cannot see or mention information belonging to other In-ventra tenants.

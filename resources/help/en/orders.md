# Orders

The Orders module manages delivery orders. An order can be created from a sale, from a quote, or independently. It allows assigning a courier (delivery person), defining the delivery address, and tracking the delivery status.

## What is an Order?

An order is a product delivery request to a client. Unlike a sale that happens immediately, an order represents a scheduled delivery. The order:

- Can be linked to a prior sale.
- Has an assigned courier for delivery.
- Follows a configurable state machine (e.g. Pending → In Preparation → On the Way → Delivered).
- Stock is deducted when the order reaches its final delivery state.

## Order Attributes

**Header:**
- **Client**: order recipient.
- **Courier**: delivery person assigned.
- **State**: current state in the workflow.
- **Point of sale**: order origin.
- **Currency**: order currency.
- **Delivery address**: where it will be delivered.
- **Requires delivery**: indicates if the order needs physical delivery.
- **Delivery date**: when delivery is planned.
- **Scheduled date**: date agreed with the client.
- **Notes**: internal and client observations.

**Items:** same structure as sales:
- Product + presentation, quantity, unit price, discount.

**Payments:** same as in sales, multiple payments can be registered.

## Creating an Order

1. Click **New Order** (from the top bar or from Orders).
2. Select the **client**.
3. Assign a **courier** if you already know who will deliver.
4. Select the order's **initial state**.
5. Fill in the delivery address (pre-fills with the client's address if loaded).
6. Define delivery and scheduled dates.
7. Enable **Requires delivery** if the order has home delivery.
8. Add the order **items**.
9. Add **payments** if the client pays when placing the order.
10. Click **Save**.

## Changing Order State

From the order detail, you can change the state using the state selector. Each change is recorded with date and time.

States are configurable in **Configuration → Order States**.

## Final State and Stock Deduction

When an order reaches a **final state** (e.g. "Delivered"), the system records stock deduction for all items. If the order was created from a sale, stock may have already been deducted when confirming the sale.

## Courier Assignment

You can assign or reassign the courier at any time before the order reaches final state. The Orders report shows metrics by courier.

## Tips

> **Tip**: Configure states with distinctive colors to quickly identify which orders need immediate attention.

> **Tip**: Assign the courier as early as possible for accurate delivery efficiency reporting.

> **Tip**: Use the "Scheduled date" field to sort orders by urgency and organize the day's logistics.

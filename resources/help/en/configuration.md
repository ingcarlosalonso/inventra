# Configuration

The Configuration section groups all system parameters that define how In-ventra works. It is divided into several subsections.

## Presentation Types

Base units of measure of the system (kg, g, liter, unit, meter, etc.).

Each type has a name and abbreviation. Used to create Presentations.

Path: **Products → Presentation Types**

---

## Presentations

Combinations of type and quantity available to assign to products (e.g. "1 kg", "500 g", "6 units").

Path: **Products → Presentations**

---

## Product Movement Types

Categories for manual stock movements (extras).

Each movement type has:
- **Name**: description of the movement (e.g. "Expiry loss", "Inventory correction").
- **Direction**: entry (increases stock) or exit (decreases stock).
- **Affects stock**: whether the movement modifies available stock.

Examples:
- "Positive adjustment" → entry → affects stock
- "Breakage / Loss" → exit → affects stock

Path: **Configuration → Product Movement Types**

---

## Cash Movement Types

Categories for manual movements in the daily cash register.

Each type has:
- **Name**: description (e.g. "Owner deposit", "Expense payment", "Withdrawal").
- **Direction**: income (adds to balance) or expense (subtracts from balance).

Path: **Configuration → Cash Movement Types**

---

## Points of Sale

Points of sale are the physical or virtual branches/registers of the business.

Each point of sale has:
- **Number**: numeric identifier.
- **Name**: descriptive name (e.g. "Downtown Branch", "Online Store").
- **Address**: physical location.
- **Auto-open time**: if configured, the cash register opens automatically at this time.
- **Auto-close time**: the cash register closes automatically at this time.

Each sale, order, and daily cash is associated with a point of sale.

Path: **Configuration → Points of Sale**

---

## Sale States

States define the lifecycle of a sale. They are configurable according to your business workflow.

Each state has:
- **Name**: state description (e.g. "Pending", "Confirmed", "Cancelled").
- **Color**: badge color for visual identification.
- **Is default state**: if enabled, new sales are automatically created in this state. Only one default state is allowed.
- **Is final state**: if enabled, sales in this state cannot be modified. Indicates the sale is complete or closed.
- **Display order**: controls the order states appear in selectors and filters.

Path: **Configuration → Sale States**

---

## Order States

Same logic as sale states, but applied to the order lifecycle.

Each state has:
- **Name**, **Color**, **Is default state**, **Is final state**, **Is active**, **Order**.

The "Is active" field allows deactivating a state without deleting it.

Path: **Configuration → Order States**

---

## Payment Methods

The means by which clients can pay (cash, debit card, credit card, bank transfer, etc.).

They are simple: only have a **name**. Used when registering payments in sales and orders.

Path: **Configuration → Payment Methods**

---

## Couriers

Delivery persons assigned to orders with home delivery.

Each courier has:
- **Name**: full name.
- **Email**: contact email.
- **Phone**: contact number.
- **Status**: active or inactive.

Inactive couriers don't appear in the selector when creating orders.

Path: **Configuration → Couriers**

---

## Currencies

The system supports multiple currencies for sales and products.

Each currency has:
- **Name**: full name (e.g. "Argentine Peso").
- **Symbol**: abbreviated symbol (e.g. "$", "US$", "€").
- **ISO code**: standard code (e.g. "ARS", "USD", "EUR").
- **Is default currency**: one currency must be the system default.

Path: **Configuration → Currencies**

---

## Configuration Best Practices

- Configure sale and order states before starting to operate.
- Always define a default state and at least one final state.
- Load all payment methods you regularly use.
- Define presentation types and common presentations before loading the catalog.
- Enable auto open/close schedule only if the point of sale operates at fixed hours.

## Tips

> **Tip**: Colors on sale and order states are key for fast visual operation. Use red for alert states, green for completed, and blue for in-progress.

> **Tip**: If you have multiple branches, create one point of sale for each. This enables separate reports per branch.

# Sales

The Sales module is the heart of In-ventra. It allows registering all sales transactions with their items, discounts, payments, and associated stock.

## Sale Workflow

1. **Create the sale**: select client, point of sale, and state.
2. **Add items**: search products, define quantities and discounts.
3. **Register payments**: select payment methods and amounts.
4. **Confirm**: the system validates stock, registers the sale, and updates balances.

## Creating a Sale

Click **New Sale** (from the top bar or from the Sales list).

### Step 1: General Data

- **Client**: search and select the client (optional). For sales without a registered client, you can leave it blank or select a generic "Counter" client.
- **Point of sale**: branch or register where the sale is made.
- **State**: the initial state is automatically assigned. You can change it manually.
- **Currency**: sale currency.
- **Notes**: internal observations.

### Step 2: Adding Items

1. In the items section, search for the product by name, code, or barcode.
2. Select the product **presentation**.
3. Adjust the **quantity** (default 1).
4. The **unit price** pre-fills automatically with the presentation price. You can modify it.
5. Apply a **discount** if applicable:
   - Percentage: e.g. 10% → system calculates the amount.
   - Fixed amount: e.g. $50 → deducted directly from the item subtotal.
6. Click **+ Add** to confirm the item.
7. Repeat for each product.

### Step 3: Payments

1. In the payments section, select the **payment method** (cash, card, bank transfer, etc.).
2. Enter the **amount** paid with that method.
3. The system shows:
   - **Sale total**
   - **Total paid**
   - **Remaining balance** (in red if unpaid)
   - **Change** (if overpaid in cash)
4. You can add multiple payments with different methods (e.g. part cash + part card).

### Step 4: Confirm

Click **Save Sale**. The system:
- Validates sufficient stock for each item.
- Deducts stock from each presentation.
- Registers payments in the point of sale's daily cash.
- Generates the sale number.

## Discounts

You can apply discounts at the item level:

- **Percentage** (e.g. 15%): system calculates amount = price x quantity x percentage.
- **Fixed amount** (e.g. $100): deducted directly from the item subtotal.

## Multiple Payment Methods

A sale can have several payment records. For example:

- $500 in cash
- $800 with debit card

This is useful when the client pays partially with different methods. The system automatically calculates the remaining balance.

## Creating Sale from Quote

From the **Quotes** module, you can convert an accepted quote into a sale. The system pre-loads all quote items into the sale form so you only need to add payments and confirm.

## Tips

> **Tip**: Configure a "Pending" sale state as default and "Confirmed" as final state. This lets you register credit sales without immediate payment and confirm them when collected.

> **Tip**: Use barcode search to speed up item loading.

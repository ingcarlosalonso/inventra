# Products

The Products module centralizes your company's catalog of items. Each product can have multiple presentations, prices, and barcodes.

## Key Concepts

### Product

A product is the base item in the catalog. It has the following attributes:

- **Name**: descriptive name of the product (e.g. "Sunflower Oil").
- **Description**: optional text with additional information.
- **Product type**: category to which it belongs (e.g. Oils, Beverages, Cleaning).
- **Currency**: currency in which the price is expressed.
- **Status**: active or inactive. Inactive products don't appear in searches when creating sales.
- **Barcodes**: one or multiple EAN/QR codes to scan the product.

### Presentation

A presentation defines **how the product is sold or measured**. Each product can have several presentations, for example:

- "1 kg" with price $500 and stock 100 units
- "500 g" with price $280 and stock 50 units
- "1 unit" with price $120 and stock 200 units

Each presentation has:

- **Presentation type**: the unit of measure (e.g. kg, g, liter, unit).
- **Quantity**: how many units of the type it contains (e.g. 1, 500, 2).
- **Sale price**: list price for this presentation.
- **Current stock**: currently available quantity.
- **Minimum stock**: low-stock alert threshold.

## Products List

When you enter **Products** you'll see a table with:

- Product name and code
- Product type
- Status (active/inactive) with color badge
- Total stock (sum of presentations)
- Actions: edit, view detail, activate/deactivate, delete

### Search

You can search products by **name**, **code**, or **barcode**. The search filters in real time.

### Filter by Status

Use the status selector to view only active, inactive, or all products.

## Creating a Product

1. Click **New Product**.
2. Fill in the name, description (optional), and select the product type.
3. Choose the product's currency.
4. Add at least one **presentation**: select type, enter quantity, price, and stock.
5. Optionally add more presentations with the **+ Add presentation** button.
6. Optionally enter barcodes.
7. Click **Save**.

## Editing a Product

Click the pencil icon in the product row or the Edit button in the detail view. You can modify all fields including existing presentations or add new ones.

> **Important**: Changing a presentation's price does not affect previously registered sales.

## Activating / Deactivating a Product

Use the status toggle to activate or deactivate a product without deleting it. Inactive products:

- Don't appear in searches when creating sales, orders, or quotes.
- Remain visible in the products module for internal management.
- Retain their movement history.

## Deleting a Product

To delete a product, click the trash icon. The system will ask for confirmation. You cannot delete a product that has associated sales, orders, or stock movements.

## Stock Management

Each presentation's stock is automatically updated when:

- **You register a reception** (increases stock).
- **You register a sale** (decreases stock).
- **You confirm an order as delivered** (decreases stock).
- **You register an extra adjustment movement** (loss, correction).

You can check the current stock in the product detail or in the Inventory Report.

## Bulk Import

You can import products from an Excel file (XLSX). The file must follow In-ventra's standard format. Access this feature from the Products menu → **Import XLSX**.

## Tips

> **Tip**: Set the minimum stock for each presentation so the Dashboard alerts you when restocking is needed.

> **Tip**: Use product types to organize your catalog and enable filtering in reports.

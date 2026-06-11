# Composite Products

Composite products (also called **kits**) are bundles of simple products sold together as a single unit. They are ideal for creating combos, packs, or sets of related products.

## What is a Composite Product?

A composite product is a set of catalog products sold as a single item. Example:

**"Home Cleaning Kit"** composed of:
- 1x Detergent 1 liter
- 2x Kitchen sponge
- 1x Bleach 500 ml

When selling this kit, the system automatically deducts stock from each component.

## Differences from Simple Products

| Feature | Simple Product | Composite Product |
|---|---|---|
| Own price | Yes | No (derived from components) |
| Own stock | Yes | No (availability depends on components) |
| Components | No | Yes (list of products with quantities) |
| Used in sales/orders | Yes | Yes |

## Composite Product Attributes

- **Name**: name of the kit or combo (e.g. "Premium Starter Kit").
- **Code**: optional internal code for quick identification.
- **Status**: active or inactive. Inactive ones don't appear in sales.
- **Items**: list of products composing the kit, each with:
  - Selected product
  - Product presentation
  - Required quantity

## Creating a Composite Product

1. Navigate to **Products → Composite Products**.
2. Click **New Composite Product**.
3. Enter the kit name and optionally a code.
4. Click **+ Add component**.
5. Search and select the product, choose the presentation and quantity.
6. Repeat for each kit component.
7. Click **Save**.

## Availability and Price

- **Price**: the system automatically calculates the kit price as the sum of component prices multiplied by quantity.
- **Availability**: the kit is available for sale only if all components have sufficient stock. If any component runs out, the kit becomes unavailable.

## Use in Sales and Orders

When creating a sale or order, you can search the kit by name or code. The system adds it as a single item, but when confirming the sale registers individual stock movements for each component.

## Tips

> **Tip**: Use composite products to create seasonal promotional combos without needing to create a new simple product.

> **Tip**: If a kit has components with low stock, it will appear in the Dashboard as a kit with limited availability.

# Composite Products

Composite products (also called **kits**) are bundles of simple products that define the components of a combo, pack, or set of related products.

## What is a Composite Product?

A composite product is a set of catalog products grouped as a single item. Example:

**"Home Cleaning Kit"** composed of:
- 1x Detergent 1 liter
- 2x Kitchen sponge
- 1x Bleach 500 ml

Composite products currently don't have their own sale price and can't be added to a sale or order: they work as a component list for internal reference.

## Differences from Simple Products

| Feature | Simple Product | Composite Product |
|---|---|---|
| Own price | Yes | No |
| Own stock | Yes | No (it's a list of components) |
| Components | No | Yes (list of products with quantities) |
| Used in sales/orders | Yes | No |

## Composite Product Attributes

- **Name**: name of the kit or combo (e.g. "Premium Starter Kit").
- **Code**: optional internal code for quick identification.
- **Status**: active or inactive.
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

## Price

Composite products don't have their own sale price: it isn't automatically calculated from the price of their components, and they currently can't be sold directly as a single unit.

## Use in Sales and Orders

Composite products currently can't be added to a sale or an order: the sale and order creation screens only support simple products.

## Tips

> **Tip**: Use composite products to model combos or packs of related products without needing to create a new simple product.

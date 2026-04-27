# Promotions

The Promotions module allows creating special offers that group multiple products at a special combined sale price. They are similar to composite products but with the ability to set a custom sale price.

## What is a Promotion?

A promotion is a grouping of products with a **special sale price**. Unlike kits (composite products) whose price is automatically calculated from components, in a promotion you can set your own sale price that doesn't necessarily match the sum of components.

Example:

**"Buy 2 Shampoo Promo"**
- 2x Shampoo 400ml (normal price: $300 each = $600)
- Promotion price: $450

## Promotion Attributes

- **Name**: descriptive name of the promotion (e.g. "Summer Pack").
- **Code**: optional internal code.
- **Sale price**: special price applied when selling this promotion. If left empty, price is calculated as the sum of components.
- **Status**: active or inactive.
- **Items**: list of products with their presentations and quantities.

## Difference from Composite Products

| Feature | Composite Product (Kit) | Promotion |
|---|---|---|
| Price | Automatically calculated | Manually configurable |
| Purpose | Permanent bundles | Special offers with different price |
| Validity | No expiration | Can be activated/deactivated by season |

## Creating a Promotion

1. Navigate to **Products → Promotions**.
2. Click **New Promotion**.
3. Enter the name and optionally a code.
4. Define the special sale price (optional).
5. Click **+ Add product** and select products with their presentations and quantities.
6. Review the component summary and price.
7. Click **Save**.

## Use in Sales and Orders

When creating a sale or order, search the promotion by name or code. The system adds it as a single item with the configured price. On confirmation, it deducts stock from each component.

## Tips

> **Tip**: Create a promotion with a special price to liquidate slow-moving stock products.

> **Tip**: Activate and deactivate promotions by season (Mother's Day, Christmas, etc.) without recreating them each time.

> **Tip**: Combine with the Quotes module to offer the promotion before confirming the sale.

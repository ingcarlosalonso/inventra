# Presentations and Presentation Types

Presentations define **how each product is measured and sold**. This module manages the units of measure available in the system.

## Presentation Types

A **Presentation Type** is the base unit of measure category. Examples:

| Presentation Type | Abbreviation | Description |
|---|---|---|
| Kilogram | kg | Weight unit |
| Gram | g | Weight unit (smaller) |
| Liter | l | Volume unit |
| Milliliter | ml | Volume unit (smaller) |
| Unit | u | Individual item |
| Meter | m | Length unit |

### Creating a Presentation Type

1. Navigate to **Configuration → Presentation Types**.
2. Click **New Presentation Type**.
3. Enter the name (e.g. "Kilogram") and abbreviation (e.g. "kg").
4. Click **Save**.

## Presentations

A **Presentation** combines a presentation type with a specific quantity. For example:

| Presentation | Type | Quantity | Description |
|---|---|---|---|
| 1 kg | Kilogram | 1 | One kilogram |
| 500 g | Gram | 500 | Five hundred grams |
| 1 liter | Liter | 1 | One liter |
| 6 units | Unit | 6 | Pack of six |

Presentations are global and reusable: created once and assigned to multiple products.

### Creating a Presentation

1. Navigate to **Configuration → Presentations**.
2. Click **New Presentation**.
3. Select the **Presentation type** (e.g. Kilogram).
4. Enter the **quantity** (e.g. 1 for "1 kg", or 500 for "500 g").
5. Click **Save**.

The presentation will be available to assign to products.

## Relationship with Products

When creating or editing a product, in the presentations section:

1. Select a presentation from the global list (e.g. "1 kg").
2. Define the **sale price** for that presentation of that product.
3. Define the **current stock** and **minimum stock**.

A single product can have multiple presentations with different prices and stocks. For example, "Rice" can have:
- Presentation "500 g" → price $150, stock 80
- Presentation "1 kg" → price $280, stock 120
- Presentation "5 kg" → price $1,200, stock 30

## Tips

> **Tip**: Define all presentation types and common presentations before starting to load the product catalog.

> **Tip**: For products sold in simple units, use the "Unit" type with quantity 1.

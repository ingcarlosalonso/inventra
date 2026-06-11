# Product Types

Product types are the **categories** of the catalog. They allow organizing and grouping products hierarchically to facilitate searching, filtering, and report generation.

## Hierarchical Structure

Product types support a tree structure with parent and child levels. For example:

```
Food
├── Oils and fats
│   ├── Vegetable oils
│   └── Margarines
├── Dairy
│   ├── Milk
│   └── Cheeses
└── Beverages
    ├── Juices
    └── Water
```

Each type can have a parent type (higher category) or be a root category.

## Product Type Attributes

- **Name**: category name (e.g. "Oils and fats").
- **Parent type**: the higher category it belongs to (optional). If not selected, the type will be a root category.

## Creating a Product Type

1. Click **New Product Type**.
2. Enter the category name.
3. If it's a subcategory, select the **Parent type**.
4. Click **Save**.

## Editing a Product Type

Click the edit icon in the type row. You can change the name and parent.

> **Warning**: Changing the parent type moves the category and all its subcategories in the tree.

## Deleting a Product Type

To delete a product type it requires:

1. No active subcategories.
2. No products assigned to it.

If there are subcategories, first delete or reassign the children. If there are products, reassign them to another type before deleting.

## Use in Other Modules

- **Products**: each product is assigned to a type when created.
- **Reports**: you can filter sales and inventory by product type.
- **Search**: in the products list you can filter by type.

## Tips

> **Tip**: Define product types before loading the catalog.

> **Tip**: Use a hierarchy of no more than 3 levels to keep navigation simple.

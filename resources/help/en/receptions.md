# Stock Entry (Receptions)

The Receptions module records incoming merchandise to the warehouse. Each reception represents a purchase or entry of products from a supplier, and automatically updates available stock.

## What is a Reception?

A reception is the record of purchasing products from a supplier. Upon saving it:

1. **Increases stock** for each received presentation.
2. **Registers the expense** in the linked daily cash (if one is selected).

## Reception Attributes

**Header:**
- **Supplier**: who supplies the merchandise (required).
- **Invoice number**: supplier's invoice or delivery note number (optional, for reference).
- **Reception date**: date the merchandise was received.
- **Notes**: internal observations about the delivery.
- **Linked daily cash**: if the supplier payment is registered in an open cash register, select it here.

**Items:**
- **Product and presentation**: what was received.
- **Quantity**: how many units came in.
- **Unit cost**: purchase price per unit.
- **Subtotal**: automatically calculated (quantity x unit cost).

## Creating a Reception

1. Navigate to **Stock Entry** in the sidebar.
2. Click **New Reception**.
3. Select the **supplier** from the list.
4. Fill in the invoice number and date (optional).
5. If you want to register the expense in the cash register, select the open **daily cash**.
6. Click **+ Add product** to add items:
   - Search for the product.
   - Select the presentation.
   - Enter the received quantity.
   - Enter the unit purchase cost.
7. Repeat for each received product.
8. Review the total and click **Save**.

## Effect on Stock

Once saved, each presentation's stock increases automatically. You can verify this:

- In the **Products** module → product detail.
- In the **Inventory Report**.
- On the **Dashboard** (if products were in low stock, they should exit the alert).

## Effect on Daily Cash

If you linked the reception to an open daily cash, the system automatically registers an **outgoing movement** in that cash for the purchase total. This reflects the supplier payment in the day's cash balance.

## Tips

> **Tip**: Register the reception on the same day merchandise arrives to keep stock updated in real time.

> **Tip**: Use the supplier's invoice number to cross-reference data with your accounting.

> **Tip**: If you receive merchandise from multiple suppliers on the same day, create one reception per supplier to maintain purchase details.

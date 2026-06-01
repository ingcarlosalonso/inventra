# Quotes

The Quotes module allows generating quotations for clients before finalizing a sale. Quotes don't affect stock until they are converted into a sale or order.

## What is a Quote?

A quote is a sales proposal with a list of products and prices delivered to the client for evaluation. It has validity until a certain date. If the client accepts, it's converted into a sale or order.

## Quote Attributes

**Header:**
- **Client**: quote recipient (optional).
- **Start date**: from when the quote is valid.
- **Expiration date**: until when the quote is valid.
- **Notes**: conditions, observations, or clarifications for the client.
- **Status**: Pending or Converted.

**Items:** same structure as sales:
- Product + presentation
- Quantity
- Unit price (editable)
- Discount (percentage or fixed amount)
- Subtotal

## Creating a Quote

1. Click **New Quote** (from the top bar or from Quotes).
2. Select the client (optional).
3. Define start and expiration dates.
4. Add items: search product, select presentation, define quantity and price.
5. Apply discounts if applicable.
6. Add notes with quote conditions.
7. Click **Save**.

## Generating PDF

From the quote detail, click **Download PDF** to generate a PDF file ready to send by email or print. The PDF includes:

- Your company logo and data (configured in General Parameters).
- Client data.
- Product list with prices, discounts, and subtotals.
- Quote total.
- Validity dates.
- Notes and conditions.

## Converting to Sale

When the client accepts the quote:

1. Open the quote detail.
2. Click **Convert to Sale**.
3. The system opens the new sale form pre-loaded with the quote items.
4. Select the point of sale and complete payments.
5. Confirm the sale.

The quote automatically changes to **Converted** status and remains linked to the created sale.

## Converting to Order

You can also convert a quote into a delivery order:

1. Open the quote detail.
2. Click **Convert to Order**.
3. The system opens the new order form pre-loaded.
4. Complete delivery details (address, courier, delivery date).
5. Confirm the order.

## Tips

> **Tip**: Use the notes field to include payment terms, delivery times, or any special consideration of the quote.

> **Tip**: Quotes don't affect stock, so you can create as many as needed for different clients on the same products.

> **Tip**: Set a standard validity period (e.g. 15 days) so sellers have a uniform criterion when quoting.

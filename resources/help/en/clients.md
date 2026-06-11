# Clients

The Clients module manages your company's customer directory. Clients are used in sales, orders, and quotes to associate each transaction with a person or company.

## Client Attributes

- **First and last name**: client's full name.
- **Tax ID / ID number**: tax or identification number.
- **Phone**: contact number.
- **Email**: email address.
- **Address**: client's address (useful for delivery orders).
- **Notes**: internal observations (preferences, special conditions, etc.).
- **Credit limit**: maximum amount the client can have outstanding.

## Creating a Client

1. Click **New Client**.
2. Fill in the first and last name (required).
3. Enter contact details: tax ID, phone, email (optional).
4. Enter the address if the client receives delivery orders.
5. Define the credit limit if applicable (0 = no limit).
6. Add internal notes if needed.
7. Click **Save**.

## Purchase History

From the client detail you can access their history:

- List of sales made with dates and amounts.
- Total purchased in the period.
- Outstanding balance.

You can also consult the **Clients** report for purchase rankings and behavior analysis.

## Credit Limit

The credit limit is an internal control. If a client has unpaid sales exceeding their limit, the system can warn the operator when creating a new sale. This doesn't automatically block the sale, but serves as an alert.

## Tips

> **Tip**: Although a sale without a client is possible, we recommend always associating a client to maintain an accurate purchase history.

> **Tip**: Fill in the client's address if you use the Orders module with home delivery: the address pre-fills automatically when creating an order.

> **Tip**: Use the notes field to record client preferences, such as "always pays by transfer" or "requests invoice A".

# Daily Cash

The Daily Cash module manages the money flow of each point of sale. It automatically records all sales income and payments, and allows registering manual deposit or withdrawal movements.

## What is a Daily Cash?

A daily cash is the record of money movement at a point of sale over a period of time. It has:

- **Opening**: initial balance at the start of the day.
- **Movements**: income and expenses during the day.
- **Closing**: final balance at end of day and reconciliation.

Only **one cash register can be open per point of sale** at the same time.

## Opening a Cash Register

1. Navigate to **Daily Cash**.
2. Click **Open Cash**.
3. Select the **point of sale**.
4. Enter the **opening balance** (money in the register at start).
5. Click **Confirm Opening**.

The cash register is now open and starts recording movements.

## Viewing Cash Detail

Click on the cash register to see its complete detail:

### Current Balance

The system shows in real time:

- **Opening balance**: amount with which the cash started.
- **Income**: sum of all received payments (sales, order payments).
- **Expenses**: sum of all outgoing payments (supplier payments from receptions, outgoing extra movements).
- **Current balance**: opening + income - expenses.

### Movements

The movements table shows chronologically:

- **Sale payments**: each payment received in a sale, with payment method.
- **Order payments**: payments registered when creating or updating orders.
- **Receptions**: supplier payments registered when entering stock.
- **Extra movements**: manual deposits or withdrawals.

Each movement shows: date/time, type, description, payment method, and amount (+ or -).

## Manual Extra Movements

Extra movements allow registering money entries or exits not related to sales:

- **Deposit**: money entry (e.g. owner deposit, collection of prior debt).
- **Withdrawal**: money exit (e.g. payment of operating expenses, cash withdrawal).

To add an extra movement:

1. In the open cash detail, click **+ Extra Movement**.
2. Select the **movement type** (configured in Configuration → Cash Movement Types).
3. Enter the amount.
4. Enter a description or notes.
5. Confirm.

## Closing the Cash Register

1. In the open cash detail, click **Close Cash**.
2. The system shows the calculated balance (based on registered movements).
3. Enter the **real balance** (physical count of money in the register).
4. If there's a difference, the system records it.
5. Confirm the closing.

Once closed, the cash register **cannot be modified or deleted**. The history is permanently saved.

## Tips

> **Tip**: Open the cash register at the start of the shift and close it at the end, even if there are no movements. This maintains an accurate and organized history.

> **Tip**: Before closing the cash, physically count the money and enter it as "real balance" to detect cash differences.

> **Tip**: Use extra movements to record day's operating expenses (supplies, services) for a complete record of outflows.

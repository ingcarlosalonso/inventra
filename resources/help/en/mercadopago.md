# Mercado Pago

The Mercado Pago module lets you charge sales and orders by card through a Mercado Pago Point terminal (posnet), connected to your own account.

> This module is optional and must be enabled for your company. If you don't see this section under Settings, contact support.

## Connecting your account

1. Go to **Settings → Mercado Pago**.
2. Click **Connect with Mercado Pago**.
3. Sign in with your Mercado Pago account and authorize access.
4. When you're redirected back, you'll see the **Account connected** status.

The connection uses your own Mercado Pago account — charges go directly to it, not to a shared account.

## Choosing the local payment method

Once your account is connected, select which **payment method** (from the ones configured under Settings → Payment Methods) should be used to record charges approved by Mercado Pago. This determines how those payments show up in Daily Cash and reports.

## Assigning a terminal to each point of sale

To charge with posnet you need to link a physical Point terminal to each point of sale:

1. Under **Settings → Mercado Pago**, find the points of sale table.
2. For each point of sale, pick the matching terminal from the dropdown.
3. Points of sale without an assigned terminal won't be able to charge with posnet.

Available terminals are fetched directly from your Mercado Pago account.

## Charging with posnet

When registering a payment (**Payments → Register Payment**) for a sale or order whose point of sale has an assigned terminal, you'll see the **Charge with Mercado Pago** option:

1. Enter the amount to charge.
2. Click **Charge with Mercado Pago**.
3. The amount is sent to the terminal — the customer completes the card payment on the posnet.
4. In-ventra waits for confirmation (you'll see "Waiting for payment on the terminal…").
5. Once Mercado Pago confirms the payment, it's automatically recorded as a payment on the sale/order — no manual entry needed.

If the payment is declined or canceled on the terminal, no payment is recorded and you can try again.

## Disconnecting the account

From **Settings → Mercado Pago**, the **Disconnect** button removes the connection. Posnet charges stop being available until you reconnect an account.

## Tips

> **Tip**: Make sure every active point of sale has its terminal assigned before operating with posnet.

> **Tip**: If a charge stays "waiting" for a long time, check the physical terminal — the operation may have expired or been canceled manually.

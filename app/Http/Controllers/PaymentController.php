<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payment\StorePaymentRequest;
use App\Http\Resources\Payment\PaymentResource;
use App\Models\Currency;
use App\Models\DailyCash;
use App\Models\Order;
use App\Models\Order\Scopes\BySearch as OrderBySearch;
use App\Models\Order\Scopes\WithPendingBalance as OrderWithPendingBalance;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\Sale\Scopes\BySearch as SaleBySearch;
use App\Models\Sale\Scopes\WithPendingBalance as SaleWithPendingBalance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function pending(Request $request): JsonResponse
    {
        $type = $request->input('type', 'all');
        $search = $request->input('search');
        $results = collect();

        if (in_array($type, ['all', 'sale'])) {
            $salesQuery = Sale::with(['client', 'pointOfSale'])
                ->withSum('payments', 'amount')
                ->withScopes(new SaleWithPendingBalance);

            if ($search) {
                $salesQuery->withScopes(new SaleBySearch($search));
            }

            foreach ($salesQuery->orderByDesc('id')->limit(30)->get() as $sale) {
                $paidAmount = (float) ($sale->payments_sum_amount ?? 0);
                $results->push([
                    'id' => $sale->uuid,
                    'type' => 'sale',
                    'client' => $sale->client?->name,
                    'reference' => $sale->pointOfSale?->name,
                    'total' => (float) $sale->total,
                    'paid_amount' => $paidAmount,
                    'pending_amount' => round((float) $sale->total - $paidAmount, 2),
                    'created_at' => $sale->created_at->toISOString(),
                ]);
            }
        }

        if (in_array($type, ['all', 'order'])) {
            $ordersQuery = Order::with(['client', 'pointOfSale'])
                ->withSum('payments', 'amount')
                ->withScopes(new OrderWithPendingBalance);

            if ($search) {
                $ordersQuery->withScopes(new OrderBySearch($search));
            }

            foreach ($ordersQuery->orderByDesc('id')->limit(30)->get() as $order) {
                $paidAmount = (float) ($order->payments_sum_amount ?? 0);
                $results->push([
                    'id' => $order->uuid,
                    'type' => 'order',
                    'client' => $order->client?->name,
                    'reference' => $order->pointOfSale?->name,
                    'total' => (float) $order->total,
                    'paid_amount' => $paidAmount,
                    'pending_amount' => round((float) $order->total - $paidAmount, 2),
                    'created_at' => $order->created_at->toISOString(),
                ]);
            }
        }

        return response()->json([
            'data' => $results->sortByDesc('created_at')->values(),
        ]);
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        $data = $request->validated();

        $payable = match ($data['payable_type']) {
            'sale' => Sale::where('uuid', $data['payable_id'])->firstOrFail(),
            'order' => Order::where('uuid', $data['payable_id'])->firstOrFail(),
        };

        $paymentMethodId = PaymentMethod::where('uuid', $data['payment_method_id'])->value('id');

        $currencyId = isset($data['currency_id'])
            ? Currency::where('uuid', $data['currency_id'])->value('id')
            : null;

        $dailyCashId = isset($data['daily_cash_id'])
            ? DailyCash::where('uuid', $data['daily_cash_id'])->value('id')
            : DailyCash::where('point_of_sale_id', $payable->point_of_sale_id)
                ->where('is_closed', false)
                ->orderByDesc('id')
                ->value('id');

        $payment = Payment::create([
            'payable_type' => $data['payable_type'],
            'payable_id' => $payable->id,
            'payment_method_id' => $paymentMethodId,
            'currency_id' => $currencyId,
            'daily_cash_id' => $dailyCashId,
            'amount' => (float) $data['amount'],
            'exchange_rate' => isset($data['exchange_rate']) ? (float) $data['exchange_rate'] : null,
            'notes' => $data['notes'] ?? null,
        ]);

        return PaymentResource::make($payment->load(['paymentMethod', 'currency']))
            ->response()
            ->setStatusCode(201);
    }
}

<?php

namespace App\Http\Requests\Sale;

use App\Enums\DiscountType;
use App\Enums\SaleItemType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['nullable', 'string', 'exists:tenant.clients,uuid'],
            'point_of_sale_id' => ['required', 'string', 'exists:tenant.points_of_sale,uuid'],
            'sale_state_id' => ['nullable', 'string', 'exists:tenant.sale_states,uuid'],
            'currency_id' => ['nullable', 'string', 'exists:tenant.currencies,uuid'],
            'notes' => ['nullable', 'string'],
            'discount_type' => ['nullable', Rule::enum(DiscountType::class)],
            'discount_value' => ['nullable', 'numeric', 'min:0'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.item_type' => ['required', Rule::enum(SaleItemType::class)],
            'items.*.saleable_id' => ['required', 'string'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_type' => ['nullable', Rule::enum(DiscountType::class)],
            'items.*.discount_value' => ['nullable', 'numeric', 'min:0'],

            'payments' => ['nullable', 'array'],
            'payments.*.payment_method_id' => ['required', 'string', 'exists:tenant.payment_methods,uuid'],
            'payments.*.currency_id' => ['nullable', 'string', 'exists:tenant.currencies,uuid'],
            'payments.*.amount' => ['required', 'numeric', 'min:0.01'],
            'payments.*.exchange_rate' => ['nullable', 'numeric', 'min:0'],
            'payments.*.notes' => ['nullable', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $seen = [];
                $tableMap = [
                    SaleItemType::Product->value => 'product_presentations',
                    SaleItemType::Composite->value => 'composite_products',
                    SaleItemType::Promotion->value => 'promotions',
                ];

                foreach ($this->input('items', []) as $i => $item) {
                    $type = $item['item_type'] ?? null;
                    $uuid = $item['saleable_id'] ?? null;

                    if (! $type || ! $uuid || ! isset($tableMap[$type])) {
                        continue;
                    }

                    // Distinct per (type, uuid) combination
                    $key = "{$type}:{$uuid}";
                    if (isset($seen[$key])) {
                        $validator->errors()->add("items.{$i}.saleable_id", __('validation.distinct'));

                        continue;
                    }
                    $seen[$key] = true;

                    // Existence check per type
                    $exists = DB::connection('tenant')
                        ->table($tableMap[$type])
                        ->where('uuid', $uuid)
                        ->exists();

                    if (! $exists) {
                        $validator->errors()->add("items.{$i}.saleable_id", __('validation.exists'));
                    }
                }
            },
        ];
    }
}

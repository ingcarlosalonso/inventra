<?php

namespace App\Models;

use App\Enums\MercadoPagoOrderStatus;
use App\Models\Concerns\HasAuditFields;
use App\Models\Concerns\HasUuid;
use Database\Factories\MercadoPagoOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MercadoPagoOrder extends Model
{
    /** @use HasFactory<MercadoPagoOrderFactory> */
    use HasAuditFields, HasFactory, HasUuid, SoftDeletes;

    protected $connection = 'tenant';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => MercadoPagoOrderStatus::class,
            'amount' => 'decimal:2',
            'processed_at' => 'datetime',
        ];
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function pointOfSale(): BelongsTo
    {
        return $this->belongsTo(PointOfSale::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}

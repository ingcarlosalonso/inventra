<?php

namespace App\Models;

use Database\Factories\MercadoPagoCredentialFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MercadoPagoCredential extends Model
{
    /** @use HasFactory<MercadoPagoCredentialFactory> */
    use HasFactory;

    protected $connection = 'tenant';

    protected $guarded = [];

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'live_mode' => 'boolean',
            'token_expires_at' => 'datetime',
            'connected_at' => 'datetime',
        ];
    }
}

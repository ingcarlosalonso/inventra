<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashMovement extends Model
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function dailyCash(): BelongsTo
    {
        return $this->belongsTo(DailyCash::class);
    }

    public function cashMovementType(): BelongsTo
    {
        return $this->belongsTo(CashMovementType::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

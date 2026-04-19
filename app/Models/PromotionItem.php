<?php

namespace App\Models;

use App\Models\Concerns\HasAuditFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionItem extends Model
{
    use HasAuditFields, HasFactory;

    protected $connection = 'tenant';

    protected $guarded = [];

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

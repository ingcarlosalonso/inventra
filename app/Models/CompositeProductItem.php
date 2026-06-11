<?php

namespace App\Models;

use App\Models\Concerns\HasAuditFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompositeProductItem extends Model
{
    use HasAuditFields, HasFactory;

    protected $connection = 'tenant';

    protected $guarded = [];

    public function compositeProduct(): BelongsTo
    {
        return $this->belongsTo(CompositeProduct::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\HasAuditFields;
use Illuminate\Database\Eloquent\Model as BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductMovement extends BaseModel
{
    use HasAuditFields, SoftDeletes;

    protected $connection = 'tenant';

    protected $guarded = [];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productPresentation(): BelongsTo
    {
        return $this->belongsTo(ProductPresentation::class);
    }

    public function productMovementType(): BelongsTo
    {
        return $this->belongsTo(ProductMovementType::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

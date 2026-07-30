<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Barcode extends Model
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $guarded = [];

    public function productPresentation(): BelongsTo
    {
        return $this->belongsTo(ProductPresentation::class);
    }
}

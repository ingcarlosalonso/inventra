<?php

namespace App\Models;

use App\Models\Concerns\HasAuditFields;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductType extends Model
{
    use HasUuid, HasAuditFields, SoftDeletes;

    protected $connection = 'tenant';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProductType::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ProductType::class, 'parent_id');
    }
}

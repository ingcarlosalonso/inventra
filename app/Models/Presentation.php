<?php

namespace App\Models;

use App\Models\Concerns\HasAuditFields;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Presentation extends Model
{
    use HasAuditFields, HasFactory, HasUuid, SoftDeletes;

    protected $connection = 'tenant';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    /**
     * There is no stored "name" column — the table only has quantity + presentation_type_id.
     * This is the single source of truth for a human-readable label (e.g. "2 L").
     */
    protected function display(): Attribute
    {
        return Attribute::make(
            get: fn () => trim(((float) $this->quantity).' '.($this->presentationType?->abbreviation ?? '')),
        );
    }

    public function presentationType(): BelongsTo
    {
        return $this->belongsTo(PresentationType::class);
    }

    public function productPresentations(): HasMany
    {
        return $this->hasMany(ProductPresentation::class);
    }
}

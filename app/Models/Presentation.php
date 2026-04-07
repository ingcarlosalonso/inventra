<?php

namespace App\Models;

use App\Models\Concerns\HasAuditFields;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Presentation extends Model
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

    public function presentationType(): BelongsTo
    {
        return $this->belongsTo(PresentationType::class);
    }
}

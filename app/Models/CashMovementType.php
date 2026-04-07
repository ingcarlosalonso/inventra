<?php

namespace App\Models;

use App\Models\Concerns\HasAuditFields;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashMovementType extends Model
{
    use HasUuid, HasAuditFields, SoftDeletes;

    protected $connection = 'tenant';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_income' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}

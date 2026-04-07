<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\SoftDeletes;

class Currency extends Model
{
    use HasUuid, SoftDeletes;

    protected $connection = 'tenant';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active'  => 'boolean',
        ];
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\SoftDeletes;

class PointOfSale extends Model
{
    use HasUuid, SoftDeletes;

    protected $connection = 'tenant';

    protected $table = 'points_of_sale';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}

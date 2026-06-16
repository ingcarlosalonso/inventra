<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserReleaseRead extends Model
{
    use HasFactory;

    protected $connection = 'tenant';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'release_uuid',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

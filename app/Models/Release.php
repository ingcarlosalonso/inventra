<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Release extends Model
{
    protected $attributes = [
        'is_published' => false,
    ];

    protected $fillable = [
        'uuid',
        'version',
        'title',
        'summary',
        'is_published',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Release $release) {
            if (empty($release->uuid)) {
                $release->uuid = (string) Str::uuid();
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReleaseItem::class)->orderBy('order');
    }
}

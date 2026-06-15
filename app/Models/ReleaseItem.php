<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReleaseItem extends Model
{
    protected $fillable = [
        'release_id',
        'type',
        'title',
        'order',
    ];

    public function release(): BelongsTo
    {
        return $this->belongsTo(Release::class);
    }
}

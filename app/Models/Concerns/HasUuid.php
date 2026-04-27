<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait HasUuid
{
    protected static function bootHasUuid(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function resolveRouteBinding(mixed $value, $field = null): ?self
    {
        return $this->where($field ?? 'uuid', $value)->firstOrFail();
    }
}

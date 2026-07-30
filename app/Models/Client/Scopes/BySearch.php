<?php

namespace App\Models\Client\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class BySearch implements Scope
{
    public function __construct(private string $search) {}

    public function apply(Builder $builder, Model $model): void
    {
        $terms = array_filter(preg_split('/\s+/', trim($this->search)) ?: []);

        if ($terms === []) {
            return;
        }

        $builder->where(function (Builder $query) use ($terms) {
            foreach ($terms as $term) {
                $query->where(function (Builder $q) use ($term) {
                    $q->where('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%");
                });
            }
        });
    }
}

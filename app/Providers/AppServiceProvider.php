<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        Builder::macro('withScopes', function (Scope|array $scopes): Builder {
            /** @var Builder $this */
            foreach (Arr::wrap($scopes) as $scope) {
                $scope->apply($this, $this->getModel());
            }

            return $this;
        });
    }
}

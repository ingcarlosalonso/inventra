<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserReleaseRead;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<UserReleaseRead>
 */
class UserReleaseReadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'release_uuid' => (string) Str::uuid(),
            'read_at' => now(),
        ];
    }
}

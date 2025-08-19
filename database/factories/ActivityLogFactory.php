<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => $this->faker->randomElement(['created', 'updated', 'deleted']),
            'subject_type' => $this->faker->randomElement([
                'App\\Models\\Order',
                'App\\Models\\Server',
                'App\\Models\\Customer',
            ]),
            'subject_id' => $this->faker->numberBetween(1, 1000),
            'properties' => [
                'ip' => $this->faker->ipv4(),
                'meta' => $this->faker->sentence(),
            ],
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
        ];
    }
}

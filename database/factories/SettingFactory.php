<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

class SettingFactory extends Factory
{
    protected $model = Setting::class;

    public function definition(): array
    {
        return [
            'key' => 'setting_' . $this->faker->unique()->word(),
            'value' => $this->faker->sentence(),
            'description' => $this->faker->sentence(8),
        ];
    }
}

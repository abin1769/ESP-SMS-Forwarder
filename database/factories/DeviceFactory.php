<?php

namespace Database\Factories;

use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Device>
 */
class DeviceFactory extends Factory
{
    protected $model = Device::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'ESP32-' . $this->faker->unique()->city(),
            'token' => Device::generateToken(),
            'status' => $this->faker->randomElement(['online', 'offline']),
            'signal' => $this->faker->numberBetween(10, 31),
            'operator' => $this->faker->randomElement(['TELKOMSEL', 'INDOSAT', 'XL AXIATA', 'THREE']),
            'last_seen' => $this->faker->dateTimeBetween('-2 days', 'now'),
        ];
    }

    /**
     * Indicate that device is online.
     */
    public function online(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'online',
            'last_seen' => now(),
        ]);
    }
}

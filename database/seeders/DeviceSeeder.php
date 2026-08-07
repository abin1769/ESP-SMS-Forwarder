<?php

namespace Database\Seeders;

use App\Models\Device;
use Illuminate\Database\Seeder;

class DeviceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Default Primary Gateway Device
        Device::updateOrCreate(
            ['token' => 'ESP32_DEFAULT_SECRET_TOKEN_12345'],
            [
                'name' => 'ESP32-Gateway-Node01',
                'status' => 'online',
                'signal' => 27,
                'operator' => 'TELKOMSEL',
                'last_seen' => now(),
            ]
        );

        // Additional sample devices
        Device::updateOrCreate(
            ['token' => 'ESP32_SECONDARY_TOKEN_67890'],
            [
                'name' => 'ESP32-Gateway-Node02',
                'status' => 'offline',
                'signal' => 18,
                'operator' => 'INDOSAT',
                'last_seen' => now()->subHours(6),
            ]
        );

        Device::factory()->count(3)->create();
    }
}

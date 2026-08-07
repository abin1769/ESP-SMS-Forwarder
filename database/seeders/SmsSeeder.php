<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Sms;
use Illuminate\Database\Seeder;

class SmsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $devices = Device::all();

        if ($devices->isEmpty()) {
            $devices = Device::factory()->count(2)->create();
        }

        foreach ($devices as $device) {
            Sms::factory()->count(10)->create([
                'device_id' => $device->id,
            ]);
        }
    }
}

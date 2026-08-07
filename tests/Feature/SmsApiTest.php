<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Sms;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_fetch_sms_list(): void
    {
        $device = Device::factory()->create();
        Sms::factory()->count(5)->create(['device_id' => $device->id]);

        $response = $this->getJson('/api/sms');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'device_id', 'phone', 'message', 'received_at', 'processed']
                ]
            ]);
    }

    public function test_can_store_incoming_sms_with_valid_token(): void
    {
        $device = Device::factory()->create([
            'token' => 'SMS_TEST_TOKEN_999',
        ]);

        $payload = [
            'token' => 'SMS_TEST_TOKEN_999',
            'phone' => '+6281234567890',
            'message' => 'Tes pesan SMS masuk dari ESP32 SIM800L',
            'received_at' => '2026-08-07 10:00:00',
        ];

        $response = $this->postJson('/api/sms', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'phone' => '+6281234567890',
                    'message' => 'Tes pesan SMS masuk dari ESP32 SIM800L',
                ]
            ]);

        $this->assertDatabaseHas('sms', [
            'device_id' => $device->id,
            'phone' => '+6281234567890',
            'message' => 'Tes pesan SMS masuk dari ESP32 SIM800L',
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_fetch_device_list(): void
    {
        Device::factory()->count(3)->create();

        $response = $this->getJson('/api/device');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'token', 'status', 'is_online', 'signal', 'operator', 'last_seen']
                ]
            ]);
    }

    public function test_heartbeat_requires_valid_token(): void
    {
        $response = $this->postJson('/api/device/heartbeat', [
            'signal' => 25,
            'operator' => 'TELKOMSEL',
        ]);

        $response->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    public function test_heartbeat_updates_device_status_with_valid_token(): void
    {
        $device = Device::factory()->create([
            'status' => 'offline',
            'token' => 'VALID_TEST_TOKEN_123',
        ]);

        $response = $this->postJson('/api/device/heartbeat', [
            'token' => 'VALID_TEST_TOKEN_123',
            'signal' => 28,
            'operator' => 'INDOSAT',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $device->id,
                    'status' => 'online',
                    'signal' => 28,
                    'operator' => 'INDOSAT',
                ]
            ]);

        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'status' => 'online',
            'signal' => 28,
            'operator' => 'INDOSAT',
        ]);
    }
}

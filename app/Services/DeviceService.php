<?php

namespace App\Services;

use App\Models\Device;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class DeviceService
{
    /**
     * Get all devices.
     */
    public function getAllDevices(): Collection
    {
        return Device::latest()->get();
    }

    /**
     * Get paginated devices with optional search query.
     */
    public function getPaginatedDevices(int $perPage = 10, ?string $search = null): LengthAwarePaginator
    {
        return Device::when($search, function ($query, $search) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('token', 'like', "%{$search}%")
                  ->orWhere('operator', 'like', "%{$search}%");
        })
        ->latest('updated_at')
        ->paginate($perPage);
    }

    /**
     * Create a new device.
     */
    public function createDevice(array $data): Device
    {
        if (empty($data['token'])) {
            $data['token'] = Device::generateToken();
        }

        $data['status'] = $data['status'] ?? 'offline';

        return Device::create($data);
    }

    /**
     * Update existing device details.
     */
    public function updateDevice(Device $device, array $data): Device
    {
        $device->update($data);
        return $device->fresh();
    }

    /**
     * Delete device.
     */
    public function deleteDevice(Device $device): bool
    {
        return (bool) $device->delete();
    }

    /**
     * Regenerate device authentication token.
     */
    public function regenerateToken(Device $device): string
    {
        $newToken = Device::generateToken();
        $device->update(['token' => $newToken]);
        return $newToken;
    }

    /**
     * Process heartbeat signal from ESP32, updating signal, operator, sim_status, reg_status.
     */
    public function handleHeartbeat(
        Device $device,
        ?int $signal = null,
        ?string $operator = null,
        ?string $simStatus = null,
        ?string $regStatus = null
    ): Device {
        $updateData = [
            'status' => 'online',
            'last_seen' => now(),
        ];

        if (!is_null($signal)) {
            $updateData['signal'] = $signal;
        }

        if (!is_null($operator)) {
            $updateData['operator'] = $operator;
        }

        if (!is_null($simStatus)) {
            $updateData['sim_status'] = $simStatus;
        }

        if (!is_null($regStatus)) {
            $updateData['reg_status'] = $regStatus;
        }

        $device->update($updateData);

        return $device->fresh();
    }

    /**
     * Queue an AT Command for ESP32 to execute on next heartbeat/poll.
     */
    public function queueCommand(Device $device, string $command): Device
    {
        $device->update([
            'pending_command' => trim($command),
        ]);

        return $device->fresh();
    }

    /**
     * Save AT Command execution response received from ESP32.
     */
    public function saveCommandResponse(Device $device, string $command, string $response): Device
    {
        $device->update([
            'pending_command' => null, // Clear pending command
            'command_response' => "[AT Command Sent]: " . $command . "\n\n[SIM800L Response]:\n" . $response,
            'command_updated_at' => now(),
        ]);

        return $device->fresh();
    }

    /**
     * Get device summary statistics.
     */
    public function getDeviceStats(): array
    {
        $allDevices = Device::all();

        $onlineCount = $allDevices->filter(function ($device) {
            return $device->is_online;
        })->count();

        $offlineCount = $allDevices->count() - $onlineCount;

        return [
            'total' => $allDevices->count(),
            'online' => $onlineCount,
            'offline' => $offlineCount,
            'active_operators' => $allDevices->pluck('operator')->filter()->unique()->values()->toArray(),
        ];
    }
}

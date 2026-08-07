<?php

namespace App\Services;

use App\Models\Device;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

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
     * Process heartbeat signal from ESP32.
     */
    public function handleHeartbeat(Device $device, ?int $signal = null, ?string $operator = null): Device
    {
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

        $device->update($updateData);

        return $device->fresh();
    }

    /**
     * Get device summary statistics.
     */
    public function getDeviceStats(): array
    {
        $allDevices = Device::all();

        // Mark devices as offline if last_seen > 5 minutes ago
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

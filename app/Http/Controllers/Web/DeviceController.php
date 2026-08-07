<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreDeviceRequest;
use App\Http\Requests\Web\UpdateDeviceRequest;
use App\Models\Device;
use App\Services\DeviceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

class DeviceController extends Controller
{
    public function __construct(
        protected DeviceService $deviceService
    ) {}

    /**
     * Display listing of devices.
     */
    public function index(Request $request): View
    {
        $search = $request->get('search');
        $devices = $this->deviceService->getPaginatedDevices(10, $search);
        $deviceStats = $this->deviceService->getDeviceStats();

        return view('devices.index', compact('devices', 'search', 'deviceStats'));
    }

    /**
     * Store a newly created device.
     */
    public function store(StoreDeviceRequest $request): RedirectResponse
    {
        $device = $this->deviceService->createDevice($request->validated());

        return redirect()->route('devices.index')
            ->with('success', "Device '{$device->name}' created successfully with Token: {$device->token}");
    }

    /**
     * Update specified device.
     */
    public function update(UpdateDeviceRequest $request, Device $device): RedirectResponse
    {
        $this->deviceService->updateDevice($device, $request->validated());

        return redirect()->route('devices.index')
            ->with('success', "Device '{$device->name}' updated successfully.");
    }

    /**
     * Remove specified device.
     */
    public function destroy(Device $device): RedirectResponse
    {
        $deviceName = $device->name;
        $this->deviceService->deleteDevice($device);

        return redirect()->route('devices.index')
            ->with('success', "Device '{$deviceName}' deleted successfully.");
    }

    /**
     * Regenerate token for a device.
     */
    public function regenerateToken(Device $device): RedirectResponse
    {
        $newToken = $this->deviceService->regenerateToken($device);

        return redirect()->route('devices.index')
            ->with('success', "New token generated for '{$device->name}': {$newToken}");
    }
}

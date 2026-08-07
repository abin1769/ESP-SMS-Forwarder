<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreDeviceRequest;
use App\Http\Requests\Web\UpdateDeviceRequest;
use App\Models\Device;
use App\Services\DeviceService;
use Illuminate\Http\JsonResponse;
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
     * Display detailed GSM debug console for device.
     */
    public function show(Device $device): View
    {
        return view('devices.show', compact('device'));
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

    /**
     * Queue custom AT Command to be executed by ESP32.
     */
    public function sendCommand(Request $request, Device $device): JsonResponse|RedirectResponse
    {
        $request->validate([
            'command' => ['required', 'string', 'max:255'],
        ]);

        $command = trim($request->input('command'));
        $this->deviceService->queueCommand($device, $command);

        if ($request->expectsJson() || $request->ajax() || $request->wantsJson() || $request->header('Accept') === 'application/json') {
            return response()->json([
                'success' => true,
                'message' => "AT Command '{$command}' queued for {$device->name}.",
                'pending_command' => $command,
            ]);
        }

        return redirect()->back()
            ->with('success', "AT Command '{$command}' queued for {$device->name}. ESP32 will execute it shortly.");
    }

    /**
     * Get real-time command response and status via AJAX.
     */
    public function getCommandStatus(Device $device): JsonResponse
    {
        return response()->json([
            'success' => true,
            'is_online' => $device->is_online,
            'signal' => $device->signal,
            'operator' => $device->operator ?? 'UNKNOWN',
            'sim_status' => $device->sim_status ?? 'UNKNOWN',
            'reg_status' => $device->reg_status ?? 'UNKNOWN',
            'pending_command' => $device->pending_command,
            'command_response' => $device->command_response ?? "// Belum ada output AT Command dari modul SIM800L.\n// Ketik perintah AT lalu klik Send AT.",
            'command_updated_at' => $device->command_updated_at ? $device->command_updated_at->format('d/m/Y H:i:s') : null,
            'last_seen_human' => $device->last_seen ? $device->last_seen->diffForHumans() : 'Never',
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DeviceHeartbeatRequest;
use App\Http\Resources\DeviceResource;
use App\Models\Device;
use App\Services\DeviceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class DeviceController extends Controller
{
    public function __construct(
        protected DeviceService $deviceService
    ) {}

    /**
     * Get device status list.
     * GET /api/device
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $devices = $this->deviceService->getAllDevices();
        return DeviceResource::collection($devices);
    }

    /**
     * Handle device heartbeat update.
     * POST /api/device/heartbeat
     */
    public function heartbeat(DeviceHeartbeatRequest $request): JsonResponse
    {
        /** @var Device|null $device */
        $device = $request->attributes->get('authenticated_device');

        // Fallback search if middleware isn't attached (e.g. token passed directly in request body)
        if (!$device && $request->filled('token')) {
            $device = Device::where('token', $request->input('token'))->first();
        }

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Device authorization failed. Valid token required.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $updatedDevice = $this->deviceService->handleHeartbeat(
            $device,
            $request->validated('signal'),
            $request->validated('operator')
        );

        return response()->json([
            'success' => true,
            'message' => 'Heartbeat received successfully.',
            'data' => new DeviceResource($updatedDevice),
        ], Response::HTTP_OK);
    }
}

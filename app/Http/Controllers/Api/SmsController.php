<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSmsRequest;
use App\Http\Resources\SmsResource;
use App\Models\Device;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class SmsController extends Controller
{
    public function __construct(
        protected SmsService $smsService
    ) {}

    /**
     * Get paginated SMS list.
     * GET /api/sms
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['search', 'device_id', 'processed']);
        $perPage = (int) $request->get('per_page', 15);

        $smsList = $this->smsService->getPaginatedSms($filters, $perPage);
        return SmsResource::collection($smsList);
    }

    /**
     * Store incoming SMS from ESP32.
     * POST /api/sms
     */
    public function store(StoreSmsRequest $request): JsonResponse
    {
        /** @var Device|null $device */
        $device = $request->attributes->get('authenticated_device');

        // Fallback search if middleware token wasn't attached
        if (!$device && $request->filled('token')) {
            $device = Device::where('token', $request->input('token'))->first();
        }

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Device authentication failed. Valid token required.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $sms = $this->smsService->storeIncomingSms($device, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'SMS saved successfully.',
            'data' => new SmsResource($sms->load('device')),
        ], Response::HTTP_CREATED);
    }
}

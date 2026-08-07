<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\DeviceService;
use App\Services\SmsService;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected DeviceService $deviceService,
        protected SmsService $smsService
    ) {}

    /**
     * Render main dashboard view.
     */
    public function __invoke(): View
    {
        $deviceStats = $this->deviceService->getDeviceStats();
        $smsStats = $this->smsService->getSmsStats();
        $chartData = $this->smsService->getDailySmsChartData(7);
        $recentSms = $this->smsService->getPaginatedSms([], 5);
        $devices = $this->deviceService->getAllDevices();

        return view('dashboard', compact(
            'deviceStats',
            'smsStats',
            'chartData',
            'recentSms',
            'devices'
        ));
    }
}

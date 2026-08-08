<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Sms;
use App\Services\DeviceService;
use App\Services\SmsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SmsController extends Controller
{
    public function __construct(
        protected SmsService $smsService,
        protected DeviceService $deviceService
    ) {}

    /**
     * Display a listing of SMS logs with search, filter, and pagination.
     */
    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->get('search'),
            'device_id' => $request->get('device_id'),
            'processed' => $request->get('processed'),
        ];

        $smsList = $this->smsService->getPaginatedSms($filters, 15);
        $devices = $this->deviceService->getAllDevices();
        $smsStats = $this->smsService->getSmsStats();

        return view('sms.index', compact('smsList', 'devices', 'filters', 'smsStats'));
    }

    /**
     * Display detailed SMS view modal or page.
     */
    public function show(Sms $sm): View
    {
        $sm->load('device');
        return view('sms.show', ['sms' => $sm]);
    }

    /**
     * Remove the specified SMS from storage.
     */
    public function destroy(Sms $sm): RedirectResponse
    {
        $this->smsService->deleteSms($sm);

        return redirect()->back()
            ->with('success', 'SMS message deleted successfully.');
    }

    /**
     * Trigger sync/pull of SMS stored in SIM card memory from all or specified device.
     */
    public function syncFromSim(Request $request): RedirectResponse
    {
        $deviceId = $request->input('device_id');
        $devices = $deviceId 
            ? Device::where('id', $deviceId)->get() 
            : Device::all();

        if ($devices->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada perangkat ESP32 yang terdaftar untuk menarik SMS dari SIM.');
        }

        $names = [];
        foreach ($devices as $dev) {
            $this->deviceService->queueCommand($dev, 'SYNC_SIM_SMS');
            $names[] = $dev->name;
        }

        $namesList = implode(', ', $names);
        return redirect()->back()->with(
            'success',
            "Perintah penarikan SMS dari memori kartu SIM berhasil dikirim ke perangkat: {$namesList}. ESP32 akan membaca semua pesan di SIM dan menyimpannya ke database."
        );
    }

    /**
     * Toggle SMS processed status.
     */
    public function toggleProcessed(Sms $sm): RedirectResponse
    {
        $updated = $this->smsService->toggleProcessed($sm);
        $statusText = $updated->processed ? 'Marked as processed' : 'Marked as un-processed';

        return redirect()->back()
            ->with('success', "SMS #{$sm->id} {$statusText}.");
    }
}


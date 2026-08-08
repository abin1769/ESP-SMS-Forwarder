@extends('layouts.app')

@section('title', 'Dashboard Gateway')
@section('page-title', 'System Overview & Gateway Statistics')

@section('content')

<!-- Stat Cards Grid -->
<div class="row g-4 mb-4">

    <!-- Total Devices -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card p-3 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">TOTAL GATEWAYS</span>
                    <h3 class="fw-bold mb-0 mt-1">{{ $deviceStats['total'] }}</h3>
                    <div class="mt-2 small">
                        <span class="badge badge-online me-1"><i class="bi bi-wifi"></i> {{ $deviceStats['online'] }} Online</span>
                        <span class="badge badge-offline"><i class="bi bi-wifi-off"></i> {{ $deviceStats['offline'] }} Offline</span>
                    </div>
                </div>
                <div class="stat-icon bg-primary-subtle text-primary">
                    <i class="bi bi-router"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Total SMS -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card p-3 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">TOTAL SMS TERTERIMA</span>
                    <h3 class="fw-bold mb-0 mt-1">{{ number_format($smsStats['total_sms']) }}</h3>
                    <div class="mt-2 small text-muted">
                        <i class="bi bi-inbox me-1"></i> Terproses: {{ $smsStats['total_sms'] - $smsStats['unprocessed_sms'] }}
                    </div>
                </div>
                <div class="stat-icon bg-success-subtle text-success">
                    <i class="bi bi-envelope-paper"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- SMS Hari Ini -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card p-3 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">SMS HARI INI</span>
                    <h3 class="fw-bold mb-0 mt-1 text-primary">{{ number_format($smsStats['sms_today']) }}</h3>
                    <div class="mt-2 small text-muted">
                        <i class="bi bi-calendar-event me-1"></i> Tanggal {{ now()->format('d M Y') }}
                    </div>
                </div>
                <div class="stat-icon bg-info-subtle text-info">
                    <i class="bi bi-send-check"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Operators -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card p-3 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">OPERATOR AKTIF</span>
                    <h4 class="fw-bold mb-0 mt-1">
                        @if(count($deviceStats['active_operators']) > 0)
                            {{ implode(', ', $deviceStats['active_operators']) }}
                        @else
                            <span class="text-muted fs-6">Belum Terdeteksi</span>
                        @endif
                    </h4>
                    <div class="mt-2 small text-muted">
                        <i class="bi bi-broadcast me-1"></i> SIM800L Module
                    </div>
                </div>
                <div class="stat-icon bg-warning-subtle text-warning">
                    <i class="bi bi-sim"></i>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Device Live Status & SMS Chart Row -->
<div class="row g-4 mb-4">

    <!-- Active Devices Live Status -->
    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold m-0"><i class="bi bi-activity text-primary me-2"></i>Status Perangkat ESP32</h6>
                <div class="d-flex align-items-center gap-2">
                    <form action="{{ route('sms.sync-sim') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-primary" title="Tarik semua pesan dari SIM">
                            <i class="bi bi-cloud-arrow-down-fill me-1"></i> Tarik SMS
                        </button>
                    </form>
                    <a href="{{ route('devices.index') }}" class="btn btn-sm btn-light">Kelola</a>
                </div>
            </div>
            <div class="card-body p-4">
                @forelse($devices as $device)
                    <div class="p-3 mb-3 bg-light rounded-3 border">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon {{ $device->is_online ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                    <i class="bi bi-cpu"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-1">
                                        <a href="{{ route('devices.show', $device) }}" class="text-decoration-none text-dark">
                                            {{ $device->name }}
                                        </a>
                                    </h6>
                                    <div class="small text-muted d-flex align-items-center gap-2">
                                        <span><i class="bi bi-sim me-1"></i>{{ $device->operator ?? 'No SIM' }}</span>
                                        <span>•</span>
                                        <span><i class="bi bi-reception-4 me-1 text-warning"></i>Signal: {{ $device->signal ?? 0 }}/31</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end">
                                @if($device->is_online)
                                    <span class="badge badge-online px-2 py-1 mb-1">ONLINE</span>
                                @else
                                    <span class="badge badge-offline px-2 py-1 mb-1">OFFLINE</span>
                                @endif
                                <div class="small text-muted" style="font-size: 0.75rem;">
                                    {{ $device->last_seen ? $device->last_seen->diffForHumans() : 'Belum Pernah' }}
                                </div>
                            </div>
                        </div>

                        <!-- SIM & CREG Diagnostics Bar -->
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-2 border-top">
                            <div class="d-flex flex-wrap gap-1">
                                <!-- SIM Badge -->
                                @if(($device->sim_status ?? 'READY') === 'READY')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 0.72rem;">
                                        <i class="bi bi-sim-fill me-1"></i>SIM READY
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" style="font-size: 0.72rem;">
                                        <i class="bi bi-sim me-1"></i>{{ $device->sim_status ?? 'UNKNOWN' }}
                                    </span>
                                @endif

                                <!-- CREG Badge -->
                                @php
                                    $reg = $device->reg_status ?? 'Registered Home';
                                @endphp
                                @if(str_contains($reg, 'Home') || str_contains($reg, '0,1'))
                                    <span class="badge bg-success text-white px-2 py-1" style="font-size: 0.72rem;">
                                        <i class="bi bi-broadcast-pin me-1"></i>+CREG: 0,1
                                    </span>
                                @elseif(str_contains($reg, 'Roaming') || str_contains($reg, '0,5'))
                                    <span class="badge bg-info text-white px-2 py-1" style="font-size: 0.72rem;">
                                        <i class="bi bi-globe me-1"></i>+CREG: 0,5
                                    </span>
                                @elseif(str_contains($reg, 'Searching') || str_contains($reg, '0,2'))
                                    <span class="badge bg-warning text-dark px-2 py-1" style="font-size: 0.72rem;">
                                        <i class="bi bi-arrow-repeat me-1"></i>Mencari Sinyal...
                                    </span>
                                @else
                                    <span class="badge bg-secondary text-white px-2 py-1" style="font-size: 0.72rem;">
                                        {{ $reg }}
                                    </span>
                                @endif
                            </div>

                            <form action="{{ route('devices.sync-sms', $device) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-light border text-primary" style="font-size: 0.75rem;" title="Tarik SMS dari SIM Device Ini">
                                    <i class="bi bi-cloud-arrow-down-fill me-1"></i> Tarik SIM
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
                        Belum ada device terdaftar.
                        <div class="mt-2">
                            <a href="{{ route('devices.index') }}" class="btn btn-sm btn-primary">+ Tambah Device</a>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- 7-Day SMS Traffic Chart -->
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                <h6 class="fw-bold m-0"><i class="bi bi-graph-up text-primary me-2"></i>Grafik SMS Terkirim (7 Hari Terakhir)</h6>
            </div>
            <div class="card-body p-4">
                <canvas id="smsChart" height="230"></canvas>
            </div>
        </div>
    </div>

</div>

<!-- Recent SMS Table -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold m-0"><i class="bi bi-chat-left-dots text-primary me-2"></i>SMS Terbaru Diterima</h6>
        <a href="{{ route('sms.index') }}" class="btn btn-sm btn-primary">Lihat Semua SMS <i class="bi bi-arrow-right ms-1"></i></a>
    </div>
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th>Device</th>
                        <th>Pengirim (Phone)</th>
                        <th>Pesan SMS</th>
                        <th>Waktu Terima</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentSms as $sms)
                        <tr>
                            <td><span class="fw-semibold">#{{ $sms->id }}</span></td>
                            <td>
                                <span class="badge bg-secondary-subtle text-dark border">
                                    <i class="bi bi-cpu me-1"></i>{{ $sms->device ? $sms->device->name : 'Unknown' }}
                                </span>
                            </td>
                            <td>
                                <span class="fw-semibold text-primary"><i class="bi bi-telephone me-1"></i>{{ $sms->phone }}</span>
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width: 350px;">
                                    {{ $sms->message }}
                                </div>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <i class="bi bi-clock me-1"></i>{{ $sms->received_at ? $sms->received_at->format('d/m/Y H:i') : $sms->created_at->format('d/m/Y H:i') }}
                                </small>
                            </td>
                            <td>
                                @if($sms->processed)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Diproses</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Belum Diproses</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                Belum ada pesan SMS diterima.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('smsChart').getContext('2d');
        const labels = @json($chartData['labels']);
        const dataValues = @json($chartData['data']);

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah SMS',
                    data: dataValues,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.35,
                    pointRadius: 5,
                    pointBackgroundColor: '#3b82f6',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    }
                }
            }
        });
    });
</script>
@endpush


@extends('layouts.app')

@section('title', 'Data Pesan SMS')
@section('page-title', 'SMS Gateway Inbox Logs')

@section('content')

<!-- Header Filter & Sync Card -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3 pb-3 border-bottom">
            <div>
                <h6 class="fw-bold mb-1"><i class="bi bi-inbox-fill text-primary me-2"></i>Pencarian & Sinkronisasi Pesan</h6>
                <p class="text-muted mb-0 small">Filter pesan masuk atau tarik pesan yang tersimpan di memori kartu SIM dari perangkat ESP32.</p>
            </div>
            <div>
                <!-- Button Trigger Modal Tarik SMS dari SIM -->
                <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#syncSimModal">
                    <i class="bi bi-cloud-arrow-down-fill me-1"></i> Tarik Pesan dari SIM
                </button>
            </div>
        </div>

        <form method="GET" action="{{ route('sms.index') }}" class="row g-3 align-items-end">
            <!-- Search Keyword -->
            <div class="col-12 col-md-4">
                <label for="search" class="form-label small fw-semibold text-muted">CARI PESAN / NOMOR HP</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" id="search" class="form-control border-start-0" placeholder="Ketik nomor HP, pesan, atau nama device..." value="{{ $filters['search'] ?? '' }}">
                </div>
            </div>

            <!-- Filter Device -->
            <div class="col-12 col-md-3">
                <label for="device_id" class="form-label small fw-semibold text-muted">FILTER DEVICE</label>
                <select name="device_id" id="device_id" class="form-select">
                    <option value="">Semua Device Gateway</option>
                    @foreach($devices as $device)
                        <option value="{{ $device->id }}" {{ ($filters['device_id'] ?? '') == $device->id ? 'selected' : '' }}>
                            {{ $device->name }} ({{ $device->operator ?? 'No SIM' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Status -->
            <div class="col-12 col-md-3">
                <label for="processed" class="form-label small fw-semibold text-muted">STATUS PROSES</label>
                <select name="processed" id="processed" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="0" {{ ($filters['processed'] ?? '') === '0' ? 'selected' : '' }}>Belum Diproses</option>
                    <option value="1" {{ ($filters['processed'] ?? '') === '1' ? 'selected' : '' }}>Sudah Diproses</option>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-filter me-1"></i> Filter</button>
                <a href="{{ route('sms.index') }}" class="btn btn-outline-secondary" title="Reset Filter"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- SMS Table Card -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold m-0"><i class="bi bi-envelope-paper-fill text-primary me-2"></i>Daftar Inbox SMS Diterima</h6>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2">
            Total: {{ $smsList->total() }} SMS
        </span>
    </div>

    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;">No</th>
                        <th>Device ESP32</th>
                        <th>Nomor Pengirim</th>
                        <th>Isi Pesan SMS</th>
                        <th>Waktu Diterima</th>
                        <th>Status</th>
                        <th style="width: 140px;" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($smsList as $index => $sms)
                        <tr>
                            <td>
                                <span class="fw-semibold text-muted">
                                    {{ $smsList->firstItem() + $index }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-secondary-subtle text-dark border">
                                    <i class="bi bi-cpu me-1 text-primary"></i>{{ $sms->device ? $sms->device->name : 'Unknown Device' }}
                                </span>
                            </td>
                            <td>
                                <span class="fw-bold text-primary"><i class="bi bi-telephone-inbound me-1"></i>{{ $sms->phone }}</span>
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width: 380px;">
                                    {{ $sms->message }}
                                </div>
                            </td>
                            <td>
                                <small class="text-muted d-block">
                                    <i class="bi bi-calendar-event me-1"></i>{{ $sms->received_at ? $sms->received_at->format('d/m/Y H:i:s') : $sms->created_at->format('d/m/Y H:i:s') }}
                                </small>
                                <small class="text-muted" style="font-size: 0.75rem;">
                                    ({{ $sms->received_at ? $sms->received_at->diffForHumans() : $sms->created_at->diffForHumans() }})
                                </small>
                            </td>
                            <td>
                                @if($sms->processed)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        <i class="bi bi-check2-circle me-1"></i>Diproses
                                    </span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                        <i class="bi bi-clock-history me-1"></i>Pending
                                    </span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <!-- View Detail Button -->
                                    <button class="btn btn-light border btn-view-sms"
                                            data-bs-toggle="modal"
                                            data-bs-target="#viewSmsModal"
                                            data-id="{{ $sms->id }}"
                                            data-device="{{ $sms->device ? $sms->device->name : 'Unknown' }}"
                                            data-phone="{{ $sms->phone }}"
                                            data-message="{{ $sms->message }}"
                                            data-time="{{ $sms->received_at ? $sms->received_at->format('d M Y H:i:s') : $sms->created_at->format('d M Y H:i:s') }}"
                                            data-processed="{{ $sms->processed ? '1' : '0' }}"
                                            title="Lihat Detail Pesan">
                                        <i class="bi bi-eye-fill text-primary"></i>
                                    </button>

                                    <!-- Toggle Processed Status -->
                                    <form action="{{ route('sms.toggle-processed', $sms) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-light border" title="Toggle Status Diproses">
                                            <i class="bi {{ $sms->processed ? 'bi-x-circle text-warning' : 'bi-check-circle text-success' }}"></i>
                                        </button>
                                    </form>

                                    <!-- Delete SMS Form -->
                                    <form action="{{ route('sms.destroy', $sms) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus pesan SMS ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-light border text-danger" title="Hapus SMS">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                Tidak ada data SMS yang sesuai dengan kriteria filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap">
            <small class="text-muted">
                Menampilkan {{ $smsList->firstItem() ?? 0 }} - {{ $smsList->lastItem() ?? 0 }} dari total {{ $smsList->total() }} SMS
            </small>
            <div>
                {{ $smsList->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>

<!-- Modal Tarik Pesan dari SIM (Sync SIM) -->
<div class="modal fade" id="syncSimModal" tabindex="-1" aria-labelledby="syncSimModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="syncSimModalLabel">
                    <i class="bi bi-cloud-arrow-down-fill text-primary me-2"></i>Tarik Pesan dari Kartu SIM
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('sms.sync-sim') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small">
                        Perintah ini akan menginstruksikan ESP32 untuk membaca seluruh SMS yang tersimpan di memori kartu SIM (<code>AT+CMGL="ALL"</code>) dan meneruskannya ke web.
                    </p>
                    <div class="mb-3">
                        <label for="sync_device_id" class="form-label fw-semibold">Pilih Device Gateway ESP32</label>
                        <select name="device_id" id="sync_device_id" class="form-select">
                            <option value="">Semua Perangkat Gateway (Broadcast Sync)</option>
                            @foreach($devices as $device)
                                <option value="{{ $device->id }}">{{ $device->name }} ({{ $device->operator ?? 'No SIM' }}) - {{ $device->is_online ? 'ONLINE' : 'OFFLINE' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="alert alert-info border-0 p-3 mb-0 small">
                        <i class="bi bi-info-circle-fill me-1"></i> ESP32 akan mengeksekusi penarikan pesan pada siklus heartbeat berikutnya dan pesan yang berhasil ditarik akan langsung masuk ke tabel ini.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-cloud-arrow-down-fill me-1"></i> Mulai Tarik SMS
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal View Detail SMS -->
<div class="modal fade" id="viewSmsModal" tabindex="-1" aria-labelledby="viewSmsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="viewSmsModalLabel"><i class="bi bi-chat-square-text text-primary me-2"></i>Detail Pesan SMS</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="small text-muted fw-semibold">DEVICE PENERIMA</label>
                        <p class="fw-bold text-dark mb-0" id="modal_device">-</p>
                    </div>
                    <div class="col-6">
                        <label class="small text-muted fw-semibold">NOMOR PENGIRIM</label>
                        <p class="fw-bold text-primary mb-0" id="modal_phone">-</p>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="small text-muted fw-semibold">WAKTU DITERIMA</label>
                    <p class="text-dark mb-0" id="modal_time">-</p>
                </div>
                <div class="mb-3">
                    <label class="small text-muted fw-semibold">ISI PESAN SMS</label>
                    <div class="p-3 bg-light rounded-3 border text-break font-monospace" id="modal_message">
                        -
                    </div>
                </div>
                <div>
                    <label class="small text-muted fw-semibold">STATUS PROSES</label>
                    <div id="modal_processed_badge"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll(".btn-view-sms").forEach(button => {
            button.addEventListener("click", function() {
                const device = this.getAttribute("data-device");
                const phone = this.getAttribute("data-phone");
                const message = this.getAttribute("data-message");
                const time = this.getAttribute("data-time");
                const processed = this.getAttribute("data-processed");

                document.getElementById("modal_device").textContent = device;
                document.getElementById("modal_phone").textContent = phone;
                document.getElementById("modal_message").textContent = message;
                document.getElementById("modal_time").textContent = time;

                const badgeContainer = document.getElementById("modal_processed_badge");
                if (processed === "1") {
                    badgeContainer.innerHTML = '<span class="badge bg-success-subtle text-success border border-success-subtle p-2"><i class="bi bi-check-circle-fill me-1"></i>Sudah Diproses</span>';
                } else {
                    badgeContainer.innerHTML = '<span class="badge bg-warning-subtle text-warning border border-warning-subtle p-2"><i class="bi bi-clock-history me-1"></i>Belum Diproses</span>';
                }
            });
        });
    });
</script>
@endpush


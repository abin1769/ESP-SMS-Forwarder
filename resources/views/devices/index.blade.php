@extends('layouts.app')

@section('title', 'Manajemen Devices ESP32')
@section('page-title', 'Device Gateway Management (ESP32 + SIM800L)')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p class="text-muted mb-0">Daftar perangkat ESP32 Gateway yang diotorisasi untuk mengirimkan SMS ke sistem.</p>
    </div>
    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addDeviceModal">
        <i class="bi bi-plus-lg me-1"></i> Tambah Device Baru
    </button>
</div>

<!-- Devices Table Card -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
        <form method="GET" action="{{ route('devices.index') }}" class="row g-2 align-items-center">
            <div class="col-12 col-md-6 col-lg-4">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Cari nama device, token, operator..." value="{{ $search }}">
                </div>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-secondary">Cari</button>
                @if($search)
                    <a href="{{ route('devices.index') }}" class="btn btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Nama Device</th>
                        <th>Token Autentikasi</th>
                        <th>Status</th>
                        <th>Sinyal (CSQ)</th>
                        <th>Operator</th>
                        <th>Terakhir Terlihat</th>
                        <th style="width: 160px;" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($devices as $device)
                        <tr>
                            <td><span class="fw-semibold">#{{ $device->id }}</span></td>
                            <td>
                                <div class="fw-bold text-dark">{{ $device->name }}</div>
                                <small class="text-muted">Registered: {{ $device->created_at->format('d/m/Y') }}</small>
                            </td>
                            <td>
                                <div class="input-group input-group-sm" style="max-width: 220px;">
                                    <input type="text" class="form-control form-control-sm font-monospace text-truncate bg-light" value="{{ $device->token }}" readonly id="token-{{ $device->id }}">
                                    <button class="btn btn-outline-secondary btn-copy" type="button" data-token="{{ $device->token }}" title="Salin Token">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                            </td>
                            <td>
                                @if($device->is_online)
                                    <span class="badge badge-online px-3 py-2"><i class="bi bi-circle-fill fs-6 me-1" style="font-size: 8px !important;"></i> ONLINE</span>
                                @else
                                    <span class="badge badge-offline px-3 py-2"><i class="bi bi-circle-fill fs-6 me-1" style="font-size: 8px !important;"></i> OFFLINE</span>
                                @endif
                            </td>
                            <td>
                                @if(!is_null($device->signal))
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 6px; min-width: 60px;">
                                            <div class="progress-bar {{ $device->signal > 15 ? 'bg-success' : ($device->signal > 8 ? 'bg-warning' : 'bg-danger') }}" 
                                                 role="progressbar" 
                                                 style="width: {{ min(100, round(($device->signal / 31) * 100)) }}%"></div>
                                        </div>
                                        <small class="fw-semibold">{{ $device->signal }}/31</small>
                                    </div>
                                @else
                                    <span class="text-muted small">N/A</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <i class="bi bi-sim me-1 text-primary"></i>{{ $device->operator ?? 'Unknown' }}
                                </span>
                            </td>
                            <td>
                                <small class="text-muted">
                                    {{ $device->last_seen ? $device->last_seen->diffForHumans() : 'Belum pernah konek' }}
                                </small>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <!-- GSM Debug Console Button -->
                                    <a href="{{ route('devices.show', $device) }}" class="btn btn-light border text-primary" title="GSM Live Terminal & Debugging">
                                        <i class="bi bi-terminal-fill"></i>
                                    </a>

                                    <!-- Edit Button -->
                                    <button class="btn btn-light border btn-edit-device" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editDeviceModal"
                                            data-id="{{ $device->id }}"
                                            data-name="{{ $device->name }}"
                                            data-status="{{ $device->status }}"
                                            data-operator="{{ $device->operator }}"
                                            title="Edit Device">
                                        <i class="bi bi-pencil-fill text-secondary"></i>
                                    </button>

                                    <!-- Regenerate Token Form -->
                                    <form action="{{ route('devices.regenerate-token', $device) }}" method="POST" class="d-inline" onsubmit="return confirm('Regenerate token akan memutuskan koneksi ESP32 yang menggunakan token lama. Yakin?');">
                                        @csrf
                                        <button type="submit" class="btn btn-light border text-warning" title="Regenerate Token">
                                            <i class="bi bi-key-fill"></i>
                                        </button>
                                    </form>

                                    <!-- Delete Form -->
                                    <form action="{{ route('devices.destroy', $device) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus device {{ $device->name }} beserta seluruh SMS-nya?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-light border text-danger" title="Hapus Device">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-router fs-2 d-block mb-2"></i>
                                Tidak ada device ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $devices->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

<!-- Modal Tambah Device -->
<div class="modal fade" id="addDeviceModal" tabindex="-1" aria-labelledby="addDeviceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="addDeviceModalLabel"><i class="bi bi-cpu text-primary me-2"></i>Tambah Gateway ESP32</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('devices.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Nama Device <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required placeholder="misal: ESP32-Server-Pos1">
                    </div>
                    <div class="mb-3">
                        <label for="token" class="form-label fw-semibold">Token Custom (Opsional)</label>
                        <input type="text" class="form-control" id="token" name="token" placeholder="Kosongkan untuk otomatis generate token acak">
                        <small class="text-muted">Jika dikosongkan, sistem akan membuat 32-karakter token unik otomatis.</small>
                    </div>
                    <div class="mb-3">
                        <label for="operator" class="form-label fw-semibold">Operator SIM (Opsional)</label>
                        <input type="text" class="form-control" id="operator" name="operator" placeholder="misal: TELKOMSEL, INDOSAT">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Device</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Device -->
<div class="modal fade" id="editDeviceModal" tabindex="-1" aria-labelledby="editDeviceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="editDeviceModalLabel"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Perangkat Device</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editDeviceForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label fw-semibold">Nama Device <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_status" class="form-label fw-semibold">Status Override</label>
                        <select class="form-select" id="edit_status" name="status" required>
                            <option value="online">Online</option>
                            <option value="offline">Offline</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_operator" class="form-label fw-semibold">Operator SIM</label>
                        <input type="text" class="form-control" id="edit_operator" name="operator">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Update Device</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Copy Token functionality
        document.querySelectorAll(".btn-copy").forEach(button => {
            button.addEventListener("click", function() {
                const token = this.getAttribute("data-token");
                navigator.clipboard.writeText(token).then(() => {
                    const originalHTML = this.innerHTML;
                    this.innerHTML = '<i class="bi bi-check-lg text-success"></i>';
                    setTimeout(() => { this.innerHTML = originalHTML; }, 2000);
                });
            });
        });

        // Edit Modal Data Populator
        document.querySelectorAll(".btn-edit-device").forEach(button => {
            button.addEventListener("click", function() {
                const id = this.getAttribute("data-id");
                const name = this.getAttribute("data-name");
                const status = this.getAttribute("data-status");
                const operator = this.getAttribute("data-operator");

                const form = document.getElementById("editDeviceForm");
                form.action = `/devices/${id}`;

                document.getElementById("edit_name").value = name;
                document.getElementById("edit_status").value = status;
                document.getElementById("edit_operator").value = operator || "";
            });
        });
    });
</script>
@endpush

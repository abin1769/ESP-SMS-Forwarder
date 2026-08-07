@extends('layouts.app')

@section('title', 'GSM Debugging & Console - ' . $device->name)
@section('page-title', 'Live GSM Debugging & Remote AT Console')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold m-0"><i class="bi bi-cpu text-primary me-2"></i>{{ $device->name }}</h4>
        <p class="text-muted mb-0 small">Token: <code class="bg-light px-2 py-1 rounded text-dark">{{ $device->token }}</code></p>
    </div>
    <a href="{{ route('devices.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Device
    </a>
</div>

<!-- Diagnostics Grid Cards -->
<div class="row g-4 mb-4">

    <!-- SIM Card Status Card -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">STATUS SIM CARD</span>
                    <h5 class="fw-bold mb-0 mt-1" id="sim_status_val">
                        @if(($device->sim_status ?? 'READY') === 'READY')
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-sim-fill me-1"></i>READY</span>
                        @else
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="bi bi-sim me-1"></i>{{ $device->sim_status ?? 'UNKNOWN' }}</span>
                        @endif
                    </h5>
                    <div class="mt-2 small text-muted">AT+CPIN? Command</div>
                </div>
                <div class="stat-icon bg-success-subtle text-success">
                    <i class="bi bi-sim-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Registration Status Card -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">REGISTRASI JARINGAN</span>
                    <h6 class="fw-bold mb-0 mt-1 text-primary" id="reg_status_val">
                        {{ $device->reg_status ?? 'Registered Home' }}
                    </h6>
                    <div class="mt-2 small text-muted">AT+CREG? Status</div>
                </div>
                <div class="stat-icon bg-primary-subtle text-primary">
                    <i class="bi bi-reception-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Signal Strength CSQ Card -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">KUAT SINYAL (CSQ)</span>
                    <h5 class="fw-bold mb-0 mt-1" id="signal_val">
                        {{ $device->signal ?? 0 }}/31
                    </h5>
                    <div class="progress mt-2" style="height: 6px; width: 120px;">
                        <div id="signal_bar" class="progress-bar {{ ($device->signal ?? 0) > 15 ? 'bg-success' : 'bg-warning' }}" 
                             role="progressbar" 
                             style="width: {{ min(100, round((($device->signal ?? 0) / 31) * 100)) }}%"></div>
                    </div>
                </div>
                <div class="stat-icon bg-warning-subtle text-warning">
                    <i class="bi bi-broadcast"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Operator & Last Seen -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">OPERATOR SELULER</span>
                    <h5 class="fw-bold mb-0 mt-1" id="operator_val">
                        {{ $device->operator ?? 'UNKNOWN' }}
                    </h5>
                    <div class="mt-2 small text-muted" id="last_seen_val">
                        Terlihat: {{ $device->last_seen ? $device->last_seen->diffForHumans() : 'Never' }}
                    </div>
                </div>
                <div class="stat-icon bg-info-subtle text-info">
                    <i class="bi bi-globe2"></i>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- AT Command Terminal & Debug Console -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-dark text-white border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold m-0 font-monospace text-success"><i class="bi bi-terminal-fill me-2"></i>Interactive SIM800L AT Console</h6>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-secondary font-monospace" id="consoleStatus">Ready</span>
            <button type="button" class="btn btn-sm btn-outline-light" id="btn-refresh-status" title="Refresh Output">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>
    </div>

    <div class="card-body p-4 bg-dark rounded-bottom-4 text-white">
        <!-- Preset AT Buttons -->
        <div class="mb-3 d-flex flex-wrap gap-2">
            <span class="small text-muted align-self-center me-2 font-monospace">Presets:</span>
            <button class="btn btn-sm btn-outline-success font-monospace btn-preset-at" data-cmd="AT">AT</button>
            <button class="btn btn-sm btn-outline-success font-monospace btn-preset-at" data-cmd="AT+CPIN?">AT+CPIN?</button>
            <button class="btn btn-sm btn-outline-success font-monospace btn-preset-at" data-cmd="AT+CREG?">AT+CREG?</button>
            <button class="btn btn-sm btn-outline-success font-monospace btn-preset-at" data-cmd="AT+CSQ">AT+CSQ</button>
            <button class="btn btn-sm btn-outline-success font-monospace btn-preset-at" data-cmd="AT+COPS?">AT+COPS?</button>
            <button class="btn btn-sm btn-outline-success font-monospace btn-preset-at" data-cmd="AT+CBC">AT+CBC (Baterai)</button>
        </div>

        <!-- Command Form -->
        <form id="atCommandForm" class="row g-2 mb-4">
            @csrf
            <div class="col">
                <div class="input-group">
                    <span class="input-group-text bg-secondary text-white border-0 font-monospace">></span>
                    <input type="text" id="commandInput" name="command" class="form-control bg-secondary text-white border-0 font-monospace" placeholder="Ketik AT Command (misal: AT+CSQ)..." required>
                </div>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-success font-monospace" id="btnSendCmd">
                    <i class="bi bi-send-fill me-1"></i> Send AT
                </button>
            </div>
        </form>

        <!-- Response Console Screen -->
        <div class="mb-2 d-flex justify-content-between align-items-center">
            <span class="small text-muted font-monospace">CONSOLE OUTPUT HISTORY:</span>
            <small class="text-muted font-monospace" id="responseTime">
                {{ $device->command_updated_at ? 'Updated: ' . $device->command_updated_at->format('H:i:s') : '' }}
            </small>
        </div>
        <div class="p-3 rounded-3 font-monospace border border-secondary" style="background-color: #0d1117; min-height: 220px; max-height: 400px; overflow-y: auto;" id="consoleOutput">
            <pre class="m-0 text-success" id="consoleOutputText" style="white-space: pre-wrap;">{{ $device->command_response ?? "// Belum ada output AT Command dari modul SIM800L.\n// Ketik perintah AT di atas lalu klik Send AT." }}</pre>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const deviceId = {{ $device->id }};
        const atForm = document.getElementById("atCommandForm");
        const cmdInput = document.getElementById("commandInput");
        const consoleOutputText = document.getElementById("consoleOutputText");
        const consoleStatus = document.getElementById("consoleStatus");
        const responseTime = document.getElementById("responseTime");
        let pollingInterval = null;

        // Preset button click listener
        document.querySelectorAll(".btn-preset-at").forEach(btn => {
            btn.addEventListener("click", function(e) {
                e.preventDefault();
                cmdInput.value = this.getAttribute("data-cmd");
                if (typeof atForm.requestSubmit === 'function') {
                    atForm.requestSubmit();
                } else {
                    atForm.dispatchEvent(new Event('submit', { cancelable: true }));
                }
            });
        });

        // Submit AT Command
        atForm.addEventListener("submit", function(e) {
            e.preventDefault();
            const command = cmdInput.value.trim();
            if (!command) return;

            consoleStatus.textContent = "Sending...";
            consoleStatus.className = "badge bg-warning font-monospace";

            fetch(`/devices/${deviceId}/send-command`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "Accept": "application/json"
                },
                body: JSON.stringify({ command: command })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    consoleStatus.textContent = "Queued on Backend";
                    consoleStatus.className = "badge bg-info font-monospace";
                    consoleOutputText.textContent = `[System]: Perintah '${command}' berhasil dikirim ke backend Laravel.\n[System]: Menunggu ESP32 mengeksekusi pada Heartbeat berikutnya...\n\n` + consoleOutputText.textContent;
                    startPollingStatus();
                } else {
                    consoleStatus.textContent = "Error";
                    consoleStatus.className = "badge bg-danger font-monospace";
                }
            })
            .catch(err => {
                consoleStatus.textContent = "Error Sending";
                consoleStatus.className = "badge bg-danger font-monospace";
            });
        });

        // Refresh Status via AJAX
        function fetchStatus() {
            fetch(`/devices/${deviceId}/command-status`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById("sim_status_val").innerHTML = data.sim_status === 'READY' 
                        ? '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-sim-fill me-1"></i>READY</span>'
                        : `<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">${data.sim_status}</span>`;
                    
                    document.getElementById("reg_status_val").textContent = data.reg_status;
                    document.getElementById("signal_val").textContent = `${data.signal}/31`;
                    document.getElementById("operator_val").textContent = data.operator;
                    document.getElementById("last_seen_val").textContent = `Terlihat: ${data.last_seen_human}`;

                    if (data.command_response) {
                        consoleOutputText.textContent = data.command_response;
                    }

                    if (data.command_updated_at) {
                        responseTime.textContent = `Updated: ${data.command_updated_at}`;
                    }

                    if (data.pending_command) {
                        consoleStatus.textContent = `Queued: ${data.pending_command}`;
                        consoleStatus.className = "badge bg-warning font-monospace";
                    } else {
                        consoleStatus.textContent = "Ready";
                        consoleStatus.className = "badge bg-success font-monospace";
                        if (pollingInterval && !data.pending_command) {
                            clearInterval(pollingInterval);
                            pollingInterval = null;
                        }
                    }
                })
                .catch(err => console.error(err));
        }

        document.getElementById("btn-refresh-status").addEventListener("click", fetchStatus);

        function startPollingStatus() {
            if (pollingInterval) clearInterval(pollingInterval);
            pollingInterval = setInterval(fetchStatus, 2000);
        }
    });
</script>
@endpush

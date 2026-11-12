@extends('layout.app')

@section('title', 'Dashboard Admin')

@section('content')
    <!-- Header Dashboard Admin -->
    <link rel="icon" type="image/jpeg" href="{{ asset('images/logo.jpg') }}">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0 fw-bold">Admin</h1>
        <div class="badge bg-success p-2">
            Role: {{ auth()->user()->role ?? 'Admin' }}
        </div>
    </div>

    <!-- Informasi Singkat -->
    <div class="alert alert-info mb-4">
        <i class="fas fa-info-circle mr-2"></i>
        <strong>Selamat datang di Sistem SIPADUKA</strong> - Dashboard untuk monitoring data keluarga
    </div>

    <!-- Grafik Real-time (menggantikan Looker Studio) -->
    <div class="card shadow">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Dashboard Monitoring Data</h6>
            <small class="text-muted">Data real-time</small>

            @if(auth()->user() && method_exists(auth()->user(), 'isAdmin') && auth()->user()->isAdmin())
                <div class="float-end">
                    <button id="forceRefreshBtn" class="btn btn-sm btn-warning"> Refresh</button>
                </div>
            @endif
        </div>
        <div class="card-body p-3">
            <div class="realtime-chart" style="position:relative;">
                <div class="realtime-grid">
                    <div class="chart-card">
                        <h6 class="mb-2">Distribusi Pendapatan</h6>
                        <div class="chart-wrap chart-constrained"><canvas id="incomeChart"></canvas></div>
                    </div>
                    <div class="chart-card">
                        <h6 class="mb-2">Status Kesehatan</h6>
                        <div class="chart-wrap"><canvas id="healthChart"></canvas></div>
                    </div>
                    <div class="chart-card chart-card-wide">
                        <h6 class="mb-2">Kategori Data Tambahan</h6>
                        <div class="chart-wrap chart-constrained"><canvas id="tambahanChart"></canvas></div>
                    </div>
                </div>
                <div id="realtimeError" class="realtime-error">Gagal memuat data grafik. Cek console untuk detail.</div>
            </div>
        </div>
        <div class="card-footer text-muted">
            <small>Terakhir diperbarui: <span id="last-updated">{{ date('d M Y H:i') }}</span></small>
            @if(auth()->user() && method_exists(auth()->user(), 'isAdmin') && auth()->user()->isAdmin())
                <small id="force-refresh-info" class="ms-3 text-muted">&nbsp;</small>
            @endif
        </div>
        @if(auth()->user() && method_exists(auth()->user(), 'isAdmin') && auth()->user()->isAdmin())
            <div class="card-body p-2 small">
                <button id="toggleHistoryBtn" class="btn btn-sm btn-outline-secondary mb-2">Lihat Riwayat Refresh</button>
                <div id="force-refresh-history" style="display:none; max-height:220px; overflow:auto;">
                    <ul id="force-refresh-history-list" class="list-group list-group-flush"></ul>
                </div>
            </div>
        @endif
    </div>
@endsection

@section('styles')
<style>
    /* Ganti semua style yang lama dengan yang ini */

.dashboard-iframe {
    position: relative;
    overflow: hidden;
}

.realtime-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    grid-auto-rows: minmax(180px, 260px);
    gap: 12px;
}

.chart-card {
    background: #fff;
    padding: 12px;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    box-shadow: none;
    min-height: 180px;
    overflow: hidden;
}

.chart-card-wide { 
    grid-column: 1 / -1; 
}

.chart-wrap {
    flex: 1 1 auto;
    position: relative;
    min-height: 140px;
    height: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
}

/* PERBAIKAN: Batasi lebar chart untuk Distribusi Pendapatan */
#incomeChart {
    max-width: 500px !important;
    max-height: 200px !important;
    margin: 0 auto;
}

/* Batasi lebar chart untuk Kategori Data Tambahan */
#tambahanChart {
    max-width: 700px !important;
    max-height: 220px !important;
    margin: 0 auto;
}

/* Chart Status Kesehatan (donut) tetap proporsional */
#healthChart {
    max-width: 280px !important;
    max-height: 280px !important;
    margin: 0 auto;
}

.chart-wrap canvas {
    display: block;
}

.realtime-error {
    position: absolute; 
    inset: 0; 
    display: none; 
    align-items: center; 
    justify-content: center; 
    background: rgba(255,255,255,0.8); 
    color: #333;
}

.card {
    border-radius: 10px;
    transition: all 0.3s;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.card.bg-light:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}
</style>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // Minimal realtime chart skeleton.

        document.addEventListener('DOMContentLoaded', () => {
            console.log('[INFO] Initializing realtime chart');
            const errEl = document.getElementById('realtimeError');

            // Create three charts
            const incomeCtx = document.getElementById('incomeChart');
            const healthCtx = document.getElementById('healthChart');
            const tambahanCtx = document.getElementById('tambahanChart');

            // Use server-side proxy endpoint to avoid CORS/network issues
            const webAppUrl = {!! json_encode(url('/api/appscript/proxy')) !!};

            if (!incomeCtx || !healthCtx || !tambahanCtx) {
                console.error('[ERROR] One or more chart canvas elements are missing', { incomeCtx, healthCtx, tambahanCtx });
                if (errEl) errEl.style.display = 'flex';
                return;
            }

            const incomeChart = new Chart(incomeCtx, {
                type: 'bar',
                data: { labels: [], datasets: [{ label: 'Jumlah KK', data: [], backgroundColor: ['#90EE90','#FFB6C1','#CCCCCC'] }] },
                options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } }, animation: { duration: 400 } }
            });

            const healthChart = new Chart(healthCtx, {
                type: 'doughnut',
                data: { labels: [], datasets: [{ label: 'Status', data: [], backgroundColor: ['#00B050','#FF0000','#FFA500','#999999'] }] },
                options: { responsive: true, maintainAspectRatio: false }
            });

            const tambahanChart = new Chart(tambahanCtx, {
                type: 'bar',
                data: { labels: [], datasets: [{ label: 'Jumlah', data: [], backgroundColor: '#4e73df' }] },
                options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } }, animation: { duration: 400 } }
            });

            console.log('[INFO] WebApp URL:', webAppUrl);

            // Helper: update charts and show percentage in labels
            function updateChartsFromAgg(agg) {
                try {
                    // income
                    if (agg.income && Array.isArray(agg.income.labels) && Array.isArray(agg.income.values)) {
                        const labels = agg.income.labels;
                        const values = agg.income.values.map(v => Number(v) || 0);
                        const total = values.reduce((a,b)=>a+b,0);
                        const labelsWithPct = labels.map((l,i) => {
                            const pct = total > 0 ? Math.round((values[i]/total)*1000)/10 : 0;
                            return `${l} (${pct}%)`;
                        });
                        incomeChart.data.labels = labelsWithPct;
                        incomeChart.data.datasets[0].data = values;
                        incomeChart.update();
                    }

                    // health (doughnut)
                    if (agg.health && Array.isArray(agg.health.labels) && Array.isArray(agg.health.values)) {
                        const labels = agg.health.labels;
                        const values = agg.health.values.map(v => Number(v) || 0);
                        const total = values.reduce((a,b)=>a+b,0);
                        const labelsWithPct = labels.map((l,i) => {
                            const pct = total > 0 ? Math.round((values[i]/total)*1000)/10 : 0;
                            return `${l} (${pct}%)`;
                        });
                        healthChart.data.labels = labelsWithPct;
                        healthChart.data.datasets[0].data = values;
                        healthChart.update();
                    }

                    // tambahan
                    if (agg.tambahan && Array.isArray(agg.tambahan.labels) && Array.isArray(agg.tambahan.values)) {
                        const labels = agg.tambahan.labels;
                        const values = agg.tambahan.values.map(v => Number(v) || 0);
                        const total = values.reduce((a,b)=>a+b,0);
                        const labelsWithPct = labels.map((l,i) => {
                            const pct = total > 0 ? Math.round((values[i]/total)*1000)/10 : 0;
                            return `${l} (${pct}%)`;
                        });
                        tambahanChart.data.labels = labelsWithPct;
                        tambahanChart.data.datasets[0].data = values;
                        tambahanChart.update();
                    }
                } catch (e) {
                    console.error('[ERROR] updateChartsFromAgg failed', e);
                }
            }

            // CSRF token (fallback to blade-rendered token if meta tag missing)
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : {!! json_encode(csrf_token()) !!};
            const metaUrl = {!! json_encode(url('/admin/appscript/refresh/meta')) !!};

            // Small helper to show temporary alerts inside the card
            function showTempAlert(type, message, timeout = 4000) {
                const container = document.createElement('div');
                container.className = `alert alert-${type} mt-2`;
                container.style.zIndex = 9999;
                container.textContent = message;

                const cardBody = document.querySelector('.card .card-body');
                if (cardBody) {
                    cardBody.prepend(container);
                    setTimeout(() => { container.remove(); }, timeout);
                } else {
                    console[type === 'success' ? 'log' : 'error'](message);
                }
            }

            // Force refresh button (admin-only) - posts to the server route which is protected by auth
            const forceBtn = document.getElementById('forceRefreshBtn');
            if (forceBtn) {
                forceBtn.addEventListener('click', async () => {
                    forceBtn.disabled = true;
                    const original = forceBtn.innerHTML;
                    forceBtn.innerHTML = 'Refreshing...';
                    try {
                        const resp = await fetch({!! json_encode(url('/admin/appscript/refresh')) !!}, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify({})
                        });

                        const payload = await resp.json().catch(() => ({}));
                        if (!resp.ok) {
                            console.error('[ERROR] Force refresh failed', payload);
                            showTempAlert('danger', 'Force refresh gagal: ' + (payload.error || payload.message || resp.status));
                        } else {
                            showTempAlert('success', 'Cache refreshed (' + (payload.cached || 0) + ' items)');

                            // If server returned fresh data, update charts immediately
                            if (payload.data && Array.isArray(payload.data)) {
                                try {
                                    const agg = applyRules(payload.data);
                                    if (agg) {
                                        updateChartsFromAgg(agg);
                                        document.getElementById('last-updated').textContent = new Date().toLocaleString();
                                    }
                                } catch (e) {
                                    console.error('[ERROR] Updating charts after force refresh failed', e);
                                }
                                // update force-refresh meta area from response if provided
                                if (payload.meta) {
                                    const infoEl = document.getElementById('force-refresh-info');
                                    if (infoEl) {
                                        infoEl.textContent = `Terakhir force refresh: ${payload.meta.user_name || payload.meta.user_id} pada ${payload.meta.time} (${payload.meta.count || 0} items)`;
                                    }
                                }

                                // update history area if provided
                                if (payload.history && Array.isArray(payload.history)) {
                                    try { renderForceHistory(payload.history); } catch (e) { console.debug('renderForceHistory failed', e); }
                                } else {
                                    // fallback: refresh meta/history from server
                                    fetchForceMeta();
                                }
                            }
                        }
                    } catch (err) {
                        console.error('[ERROR] Force refresh exception', err);
                        showTempAlert('danger', 'Force refresh error');
                    } finally {
                        forceBtn.disabled = false;
                        forceBtn.innerHTML = original;
                    }
                });
            }

            // Fetch and render last forced-refresh metadata and history (admin-only)
            async function fetchForceMeta() {
                try {
                    const resp = await fetch(metaUrl, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
                    if (!resp.ok) return;
                    const j = await resp.json().catch(() => ({}));
                    const info = (j && j.meta) ? j.meta : null;
                    const history = (j && j.history) ? j.history : [];
                    const infoEl = document.getElementById('force-refresh-info');
                    if (!infoEl) return;
                    if (!info) {
                        infoEl.textContent = 'Belum ada force refresh.';
                    } else {
                        infoEl.textContent = `Terakhir force refresh: ${info.user_name || info.user_id} pada ${info.time} (${info.count || 0} items)`;
                    }

                    // render history list
                    try { renderForceHistory(history); } catch(e){ console.debug('renderForceHistory failed', e); }
                } catch (e) {
                    console.debug('[DEBUG] fetchForceMeta failed', e);
                }
            }

            // initial meta & history load for admins
            fetchForceMeta();

            // render history list
            function renderForceHistory(history) {
                const listEl = document.getElementById('force-refresh-history-list');
                if (!listEl) return;
                listEl.innerHTML = '';
                if (!Array.isArray(history) || history.length === 0) {
                    const li = document.createElement('li');
                    li.className = 'list-group-item small text-muted';
                    li.textContent = 'Belum ada riwayat.';
                    listEl.appendChild(li);
                    return;
                }

                history.forEach(item => {
                    const li = document.createElement('li');
                    li.className = 'list-group-item';
                    const user = item.user_name || item.user_id || 'Unknown';
                    const time = item.time || '';
                    const count = item.count || 0;
                    li.innerHTML = `<div><strong>${user}</strong> — <small class="text-muted">${time}</small></div><div class="text-muted">${count} items</div>`;
                    listEl.appendChild(li);
                });
            }

            // toggle history visibility
            const toggleBtn = document.getElementById('toggleHistoryBtn');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', () => {
                    const box = document.getElementById('force-refresh-history');
                    if (!box) return;
                    if (box.style.display === 'none' || !box.style.display) {
                        box.style.display = 'block';
                        toggleBtn.textContent = 'Sembunyikan Riwayat';
                    } else {
                        box.style.display = 'none';
                        toggleBtn.textContent = 'Lihat Riwayat Refresh';
                    }
                });
            }

        // Rules copied / adapted from your Apps Script
        function cleanWhatsAppFormatting(text) {
            if (!text || typeof text !== 'string') return '';
            text = text.replace(/[•\u200B\u200C\u200D\uFEFF]/g, '');
            text = text.replace(/\*{1,2}([^\*]+)\*{1,2}/g, '$1');
            text = text.replace(/_+([^_]+)_+/g, '$1');
            text = text.replace(/~+([^~]+)~+/g, '$1');
            text = text.replace(/`+([^`]+)`+/g, '$1');
            text = text.replace(/—+/g, '');
            text = text.replace(/[\-\u2013\u2014]{2,}/g, '');
            text = text.replace(/\s+/g, ' ').trim();
            return text;
        }

        function normalizeKey(key) {
            if (!key || typeof key !== 'string') return null;
            key = key.toLowerCase().trim();
            key = cleanWhatsAppFormatting(key);
            key = key.replace(/:$/, '');

            const mapping = {
                'nomor kk': 'Nomor KK',
                'nomor kk:': 'Nomor KK',
                'no kk': 'Nomor KK',
                'kk': 'Nomor KK',
                'alamat sesuai kk': 'Alamat Sesuai KK',
                'alamat': 'Alamat Sesuai KK',
                'rt/rw': 'RT/RW',
                'rt rw': 'RT/RW',
                'nama lengkap kepala keluarga': 'Nama Lengkap Kepala Keluarga',
                'nama kepala keluarga': 'Nama Lengkap Kepala Keluarga',
                'nik kepala keluarga': 'NIK Kepala Keluarga',
                'nik suami': 'NIK Kepala Keluarga',
                'nama lengkap istri': 'Nama Lengkap Istri',
                'nama istri': 'Nama Lengkap Istri',
                'nik istri': 'NIK Istri',
                'nama lengkap anak 1': 'Nama Lengkap Anak 1',
                'nama anak 1': 'Nama Lengkap Anak 1',
                'anak 1': 'Nama Lengkap Anak 1',
                'nik anak 1': 'NIK Anak 1',
                'nama lengkap anak 2': 'Nama Lengkap Anak 2',
                'nama anak 2': 'Nama Lengkap Anak 2',
                'anak 2': 'Nama Lengkap Anak 2',
                'nik anak 2': 'NIK Anak 2',
                'nama lengkap anak 3': 'Nama Lengkap Anak 3',
                'nama anak 3': 'Nama Lengkap Anak 3',
                'anak 3': 'Nama Lengkap Anak 3',
                'nik anak 3': 'NIK Anak 3',
                'nama lengkap anak 4': 'Nama Lengkap Anak 4',
                'nama anak 4': 'Nama Lengkap Anak 4 ',
                'anak 4': 'Nama Lengkap Anak 4',
                'nik anak 4': 'NIK Anak 4',
                'nama lengkap anak 5': 'Nama Lengkap Anak 5',
                'nama anak 5': 'Nama Lengkap Anak 5',
                'anak 5': 'Nama Lengkap Anak 5',
                'nik anak 5': 'NIK Anak 5',
                'nama lengkap anak 6': 'Nama Lengkap Anak 6',
                'nama anak 6': 'Nama Lengkap Anak  6',
                'anak 6': 'Nama Lengkap Anak 6',
                'nik anak 6': 'NIK Anak 6',
                'status pendidikan kepala keluarga': 'Status Pendidikan Kepala Keluarga',
                'pendidikan kepala keluarga': 'Status Pendidikan Kepala Keluarga',
                'status pendidikan istri': 'Status Pendidikan Istri',
                'pendidikan istri': 'Status Pendidikan Istri',
                'status pendidikan anak 1': 'Status Pendidikan Anak 1',
                'pendidikan anak 1': 'Status Pendidikan Anak 1',
                'status pendidikan anak 2': 'Status Pendidikan Anak 2',
                'pendidikan anak 2': 'Status Pendidikan Anak 2',
                'status pendidikan anak 3': 'Status Pendidikan Anak 3',
                'pendidikan anak 3': 'Status Pendidikan Anak 3',
                'status pendidikan anak 4': 'Status Pendidikan Anak 4',
                'pendidikan anak 4': 'Status Pendidikan Anak 4',
                'status pendidikan anak 5': 'Status Pendidikan Anak 5',
                'pendidikan anak 5': 'Status Pendidikan Anak 5',
                'status pendidikan anak 6': 'Status Pendidikan Anak 6',
                'pendidikan anak 6': 'Status Pendidikan Anak 6',
                'status pekerjaan kepala keluarga': 'Status Pekerjaan Kepala Keluarga',
                'pekerjaan kepala keluarga': 'Status Pekerjaan Kepala Keluarga',
                'status pekerjaan istri': 'Status Pekerjaan Istri',
                'pekerjaan istri': 'Status Pekerjaan Istri',
               'status pekerjaan anak 1': 'Status Pekerjaan Anak 1',
                'pekerjaan anak 1': 'Status Pekerjaan Anak 1',
                'status pekerjaan anak 2': 'Status Pekerjaan Anak 2',
                'pekerjaan anak 2': 'Status Pekerjaan Anak 2',
                'status pekerjaan anak 3': 'Status Pekerjaan Anak 3',
                'pekerjaan anak 3': 'Status Pekerjaan Anak 3',
                'status pekerjaan anak 4': 'Status Pekerjaan Anak 4',
                'pekerjaan anak 4': 'Status Pekerjaan Anak 4',
                'status pekerjaan anak 5': 'Status Pekerjaan Anak 5',
                'pekerjaan anak 5': 'Status Pekerjaan Anak 5',
                'status pekerjaan anak 6': 'Status Pekerjaan Anak 6',
                'pekerjaan anak 6': 'Status Pekerjaan Anak 6',
                'gaji/pendapatan suami': 'Gaji / Pendapatan Kepala Keluarga',
                'pendapatan suami': 'Gaji / Pendapatan Kepala Keluarga',
                'gaji suami': 'Gaji / Pendapatan Kepala Keluarga',
                'gaji/pendapatan kepala keluarga': 'Gaji / Pendapatan Kepala Keluarga',
                'pendapatan kepala keluarga': 'Gaji / Pendapatan Kepala Keluarga',
                'gaji kepala keluarga': 'Gaji / Pendapatan Kepala Keluarga',
                'gaji/pendapatan istri': 'Gaji / Pendapatan Istri',
                'pendapatan istri': 'Gaji / Pendapatan Istri',
                'gaji istri': 'Gaji / Pendapatan Istri',
                'total pendapatan keluarga': 'Total Pendapatan Keluarga',
                'total pendapatan': 'Total Pendapatan Keluarga',
                'total anggota keluarga': 'Total Anggota Keluarga',
                'jumlah anggota keluarga': 'Total Anggota Keluarga',
                'kesehatan kepala keluarga': 'Kesehatan Kepala Keluarga',
                'kesehatan suami': 'Kesehatan Kepala Keluarga',
                'kesehatan istri': 'Kesehatan Istri',
                'kesehatan anak 1': 'Kesehatan Anak 1',
                'kesehatan anak 2': 'Kesehatan Anak 2',
                'kesehatan anak 3': 'Kesehatan Anak 3',
                'kesehatan anak 4': 'Kesehatan Anak 4',
                'kesehatan anak 5': 'Kesehatan Anak 5',
                'kesehatan anak 6': 'Kesehatan Anak 6',
                'data tambahan': 'Data Tambahan (anggota lain, catatan, perubahan data, dll)',
                'catatan tambahan': 'Data Tambahan (anggota lain, catatan, perubahan data, dll)',
                'data tambahan (anggota lain, catatan, perubahan data, dll)': 'Data Tambahan (anggota lain, catatan, perubahan data, dll)',
                'anggota lain': 'Data Tambahan (anggota lain, catatan, perubahan data, dll)',
                'catatan': 'Data Tambahan (anggota lain, catatan, perubahan data, dll)'
            };

            if (mapping[key]) {
                return mapping[key];
            }

            const cleanedKey = key.replace(/[^a-z0-9\s]/g, '');
            for (const pattern in mapping) {
                const cleanedPattern = pattern.replace(/[^a-z0-9\s]/g, '');
                if (cleanedKey.includes(cleanedPattern) || cleanedPattern.includes(cleanedKey)) {
                    return mapping[pattern];
                }
            }

            return null;
        }

        function applyRules(rawData) {
            // rawData expected: array of objects returned by your Apps Script doGet?action=list
            if (!Array.isArray(rawData)) {
                if (rawData.labels && rawData.values) return rawData;
                return { labels: [], values: [] };
            }

            // Normalize each row's keys using the mapping logic
            const normalizedRows = rawData.map(row => {
                const out = {};
                Object.keys(row).forEach(k => {
                    const norm = normalizeKey(String(k).toLowerCase()) || k;
                    out[norm] = row[k];
                });
                return out;
            });

            // Aggregate income: only consider rows that have a Nomor KK; remove 'Unknown' category
            const pendField = 'Total Pendapatan Keluarga';
            const kkField = 'Nomor KK';
            let incomeHigh = 0, incomeLow = 0;

            const rowsWithKK = normalizedRows.filter(r => {
                const kk = r[kkField];
                return kk !== undefined && kk !== null && String(kk).trim() !== '';
            });

            rowsWithKK.forEach(r => {
                const raw = r[pendField];
                if (raw === undefined || raw === null || raw === '') { return; }
                const num = Number(String(raw).replace(/[^\d.-]/g, ''));
                if (isNaN(num)) { return; }
                if (num >= 4000000) incomeHigh++; else incomeLow++;
            });

            const income = {
                labels: ['>= 4.000.000', '< 4.000.000'],
                values: [incomeHigh, incomeLow]
            };

            // Aggregate health statuses across any 'Kesehatan' fields (ignore unknowns)
            const healthCounts = { SEHAT:0, SAKIT:0, RIWAYAT:0 };
            normalizedRows.forEach(r => {
                Object.keys(r).forEach(k => {
                    if (String(k).toLowerCase().includes('kesehatan')) {
                        const v = (r[k] || '').toString().toUpperCase();
                        if (v.includes('SEHAT')) healthCounts.SEHAT++;
                        else if (v.includes('SAKIT')) healthCounts.SAKIT++;
                        else if (v.includes('RIWAYAT')) healthCounts.RIWAYAT++;
                        // otherwise: ignore unknown / unspecified health values
                    }
                });
            });

            const health = {
                labels: Object.keys(healthCounts),
                values: Object.keys(healthCounts).map(k => healthCounts[k])
            };

            // Aggregate Data Tambahan (keywords)
            const tambahanField = 'Data Tambahan (anggota lain, catatan, perubahan data, dll)';
            const tambahanCounts = { meninggal:0, 'pindah masuk':0, 'pindah keluar':0, 'tidak tercantum':0, 'tidak tinggal':0, lainnya:0 };
            normalizedRows.forEach(r => {
                const raw = (r[tambahanField] || '').toString().toLowerCase();
                if (!raw) return;
                if (raw.includes('meninggal')) tambahanCounts.meninggal++;
                else if (raw.includes('pindah masuk')) tambahanCounts['pindah masuk']++;
                else if (raw.includes('pindah keluar')) tambahanCounts['pindah keluar']++;
                else if (raw.includes('tidak tercantum')) tambahanCounts['tidak tercantum']++;
                else if (raw.includes('tidak tinggal')) tambahanCounts['tidak tinggal']++;
                else tambahanCounts.lainnya++;
            });

            const tambahan = {
                labels: Object.keys(tambahanCounts).map(k => k.charAt(0).toUpperCase() + k.slice(1)),
                values: Object.keys(tambahanCounts).map(k => tambahanCounts[k])
            };

            return { income, health, tambahan };
        }

            async function fetchAndUpdate() {
                if (!webAppUrl || webAppUrl === 'REPLACE_WITH_APPSCRIPT_WEBAPP_URL') {
                    console.warn('[WARN] WebApp URL not configured');
                    return;
                }
                try {
                    // ensure we request list action
                    let url = webAppUrl;
                    if (url && !/([?&])action=/.test(url)) {
                        url += (url.indexOf('?') === -1 ? '?' : '&') + 'action=list';
                    }

                    const res = await fetch(url, { cache: 'no-store' });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const json = await res.json();
                    console.log('[INFO] Received data sample:', Array.isArray(json) ? json.slice(0,3) : json);

                    // applyRules now returns aggregated metrics
                    let agg;
                    try {
                        agg = applyRules(json);
                    } catch (err) {
                        console.error('[ERROR] applyRules failed', err, json);
                        agg = null;
                    }

                    if (!agg || !agg.income || !agg.health || !agg.tambahan) {
                        console.warn('[WARN] Aggregation missing or malformed — will keep previous chart values', agg);
                    } else {
                        if (agg) updateChartsFromAgg(agg);
                    }

                    document.getElementById('last-updated').textContent = new Date().toLocaleString();
                    if (errEl) errEl.style.display = 'none';
                } catch (err) {
                    console.error('[ERROR] Fetch chart data failed', err);
                    if (errEl) errEl.style.display = 'flex';
                }
            }

            // initial load and polling every 10s
            fetchAndUpdate();
            setInterval(fetchAndUpdate, 10000);
        });
        
    </script>
@endpush
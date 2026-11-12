@extends('layout.app')

@section('title', 'Data dari Google Sheets')

@section('content')
<div class="container-fluid px-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-0">
                        <i class="bi bi-google me-2"></i>Data Keluarga dari Google Sheets
                    </h3>
                    <p class="text-muted mb-0">Data yang dikumpulkan via WhatsApp</p>
                </div>
                <div>
                    <button class="btn btn-outline-secondary me-2" onclick="refreshData()">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                    <button class="btn btn-success me-2" onclick="syncToLocal()">
                        <i class="bi bi-download"></i> Sync ke Database
                    </button>
                    <a href="{{ route('keluarga.index') }}" class="btn btn-outline-primary">
                        <i class="bi bi-database"></i> Data Lokal
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Connection Status -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="alert alert-info d-flex justify-content-between align-items-center">
                <div>
                    <i class="bi bi-info-circle me-2"></i>
                    <span id="connectionStatus">Checking connection...</span>
                </div>
                <button class="btn btn-sm btn-outline-info" onclick="testConnection()">
                    <i class="bi bi-wifi"></i> Test Koneksi
                </button>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="input-group">
                <span class="input-group-text bg-transparent border-end-0">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" class="form-control border-start-0" 
                       id="searchInput" placeholder="Cari data keluarga...">
            </div>
        </div>
        <div class="col-md-6 text-md-end">
            <div class="text-muted">
                <span id="dataCount">0</span> data ditemukan
            </div>
        </div>
    </div>

    <!-- Main Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 70vh; overflow-y: auto;">
                        <table class="table table-hover table-bordered mb-0" id="keluargaTable">
                            <thead class="table-light position-sticky top-0 z-1">
                                <tr id="tableHeader">
                                    <th colspan="10" class="text-center py-3">
                                        <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                        Memuat data dari Google Sheets...
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                <!-- Data akan diisi oleh JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Data Keluarga</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent">
                Loading...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" onclick="syncSingleData()">
                    <i class="bi bi-download"></i> Simpan ke Database
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .table th {
        position: sticky;
        top: 0;
        background: white;
        z-index: 10;
        box-shadow: 0 2px 2px -1px rgba(0,0,0,0.1);
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(0,123,255,0.05);
        cursor: pointer;
    }
    
    .selected-row {
        background-color: rgba(0,123,255,0.1) !important;
    }
</style>
@endpush

@push('scripts')
<script>
// Global variables
let allData = [];
let currentData = [];
let selectedRowData = null;

document.addEventListener('DOMContentLoaded', function() {
    loadData();
    testConnection();
    
    // Setup search
    document.getElementById('searchInput').addEventListener('input', function() {
        filterData(this.value);
    });
});

function loadData(showLoading = true) {
    if (showLoading) {
        showLoadingState();
    }
    
    fetch("{{ route('google-sheets.data') }}?nocache=" + new Date().getTime())
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Data received:', data);
            
            allData = Array.isArray(data) ? data : [];
            currentData = [...allData];
            
            updateDataCount(allData.length);
            
            if (allData.length === 0) {
                showNoDataMessage();
            } else {
                renderTable();
            }
        })
        .catch(error => {
            console.error('Error loading data:', error);
            showError('Gagal memuat data. Periksa koneksi internet atau coba refresh.');
        });
}

function testConnection() {
    const statusEl = document.getElementById('connectionStatus');
    statusEl.innerHTML = '<div class="spinner-border spinner-border-sm me-2" role="status"></div> Testing connection...';
    
    fetch("{{ route('google-sheets.test') }}")
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                statusEl.innerHTML = `<i class="bi bi-check-circle text-success me-2"></i> 
                    Terhubung ke Google Sheets (${data.data_count || 0} data tersedia)`;
            } else {
                statusEl.innerHTML = `<i class="bi bi-x-circle text-danger me-2"></i> 
                    Koneksi gagal: ${data.message}`;
            }
        })
        .catch(error => {
            console.error('Connection test error:', error);
            statusEl.innerHTML = `<i class="bi bi-x-circle text-danger me-2"></i> 
                Gagal menguji koneksi`;
        });
}

function showLoadingState() {
    const header = document.getElementById('tableHeader');
    const body = document.getElementById('tableBody');
    
    header.innerHTML = `
        <tr>
            <th colspan="10" class="text-center py-4">
                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                Memuat data dari Google Sheets...
            </th>
        </tr>
    `;
    body.innerHTML = '';
}

function showNoDataMessage() {
    const header = document.getElementById('tableHeader');
    const body = document.getElementById('tableBody');
    
    header.innerHTML = `
        <tr>
            <th colspan="10" class="text-center py-4">
                <i class="bi bi-database-x fs-4 text-muted d-block mb-2"></i>
                Tidak ada data di Google Sheets
            </th>
        </tr>
    `;
    body.innerHTML = '';
}

function showError(message) {
    const header = document.getElementById('tableHeader');
    const body = document.getElementById('tableBody');
    
    header.innerHTML = `
        <tr>
            <th colspan="10" class="text-center py-4 text-danger">
                <i class="bi bi-exclamation-triangle me-2"></i>
                ${message}
            </th>
        </tr>
    `;
    body.innerHTML = '';
}

function renderTable() {
    if (currentData.length === 0) {
        showNoDataMessage();
        return;
    }
    
    // Ambil headers dari data pertama (atau tentukan headers yang ingin ditampilkan)
    const displayHeaders = [
        'Nomor KK',
        'Nama Lengkap Kepala Keluarga', 
        'Alamat Sesuai KK',
        'RT/RW',
        'Total Anggota Keluarga',
        'Total Pendapatan Keluarga'
    ];
    
    // Render header
    const headerRow = document.getElementById('tableHeader');
    headerRow.innerHTML = `
        <th width="60">No</th>
        ${displayHeaders.map(h => `<th>${formatHeader(h)}</th>`).join('')}
        <th width="150">Aksi</th>
    `;
    
    // Render body
    const body = document.getElementById('tableBody');
    body.innerHTML = '';
    
    currentData.forEach((item, index) => {
        const row = document.createElement('tr');
        row.className = 'data-row';
        row.onclick = () => showDetail(index);
        
        let rowHtml = `
            <td class="text-center fw-bold">${index + 1}</td>
        `;
        
        // Tambahkan data untuk setiap header
        displayHeaders.forEach(header => {
            const value = item[header] || '';
            rowHtml += `<td title="${formatHeader(header)}: ${value}">${truncateText(value, 30)}</td>`;
        });
        
        // Tombol aksi
        const nomorKK = item['Nomor KK'] || '';
        rowHtml += `
            <td class="text-center">
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-info" 
                            onclick="event.stopPropagation(); showDetail(${index})" 
                            title="Detail">
                        <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-outline-danger" 
                            onclick="event.stopPropagation(); confirmDelete('${nomorKK}', '${escapeString(item['Nama Lengkap Kepala Keluarga'] || '')}')" 
                            title="Hapus">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        `;
        
        row.innerHTML = rowHtml;
        body.appendChild(row);
    });
}

function formatHeader(header) {
    return header
        .replace(/([A-Z])/g, ' $1')
        .replace(/^./, str => str.toUpperCase())
        .replace(/\s+/g, ' ')
        .trim();
}

function truncateText(text, maxLength) {
    if (!text) return '';
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
}

function escapeString(str) {
    return String(str).replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0');
}

function filterData(searchTerm) {
    if (!searchTerm) {
        currentData = [...allData];
    } else {
        const term = searchTerm.toLowerCase();
        currentData = allData.filter(item => {
            return Object.values(item).some(value => 
                String(value).toLowerCase().includes(term)
            );
        });
    }
    
    updateDataCount(currentData.length);
    renderTable();
}

function updateDataCount(count) {
    document.getElementById('dataCount').textContent = count;
}

function showDetail(index) {
    const item = currentData[index];
    if (!item) return;
    
    selectedRowData = item;
    
    let detailHtml = '<div class="row">';
    
    // Urutkan field yang ingin ditampilkan
    const fieldOrder = [
        'Timestamp',
        'Nomor KK',
        'Alamat Sesuai KK',
        'RT/RW',
        'Nama Lengkap Kepala Keluarga',
        'NIK Kepala Keluarga',
        'Nama Lengkap Istri',
        'NIK Istri',
        'Status Pendidikan Kepala Keluarga',
        'Status Pekerjaan Kepala Keluarga',
        'Total Anggota Keluarga',
        'Total Pendapatan Keluarga',
        'Data Tambahan (anggota lain, catatan, perubahan data, dll)'
    ];
    
    // Tampilkan field yang diurutkan terlebih dahulu
    fieldOrder.forEach(field => {
        if (item[field]) {
            detailHtml += `
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold text-primary">${formatHeader(field)}</label>
                    <div class="form-control bg-light" style="min-height: 38px;">
                        ${item[field] || '<span class="text-muted">-</span>'}
                    </div>
                </div>
            `;
        }
    });
    
    // Tampilkan field lainnya
    for (const [key, value] of Object.entries(item)) {
        if (!fieldOrder.includes(key) && value && !key.startsWith('_')) {
            detailHtml += `
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold text-primary">${formatHeader(key)}</label>
                    <div class="form-control bg-light" style="min-height: 38px;">
                        ${value || '<span class="text-muted">-</span>'}
                    </div>
                </div>
            `;
        }
    }
    
    detailHtml += '</div>';
    
    document.getElementById('detailContent').innerHTML = detailHtml;
    
    const modal = new bootstrap.Modal(document.getElementById('detailModal'));
    modal.show();
}

function confirmDelete(nomorKK, nama) {
    if (!confirm(`Yakin ingin menghapus data dengan:\nNomor KK: ${nomorKK}\nNama: ${nama || 'Tidak ada nama'}?`)) {
        return;
    }
    
    fetch(`{{ route('google-sheets.delete-by-nomor-kk', '') }}/${encodeURIComponent(nomorKK)}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            loadData();
        } else {
            alert('Gagal menghapus: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Delete error:', error);
        alert('Terjadi kesalahan saat menghapus data');
    });
}

function syncToLocal() {
    if (!confirm('Yakin ingin menyinkronkan semua data dari Google Sheets ke database lokal?')) {
        return;
    }
    
    fetch("{{ route('google-sheets.sync') }}", {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Sync berhasil!\nDitambahkan: ${data.synced}\nDiupdate: ${data.updated}`);
            if (data.errors && data.errors.length > 0) {
                console.log('Errors during sync:', data.errors);
            }
        } else {
            alert('Sync gagal: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Sync error:', error);
        alert('Terjadi kesalahan saat sinkronisasi');
    });
}

function syncSingleData() {
    if (!selectedRowData) return;
    
    const nomorKK = selectedRowData['Nomor KK'] || '';
    const nama = selectedRowData['Nama Lengkap Kepala Keluarga'] || '';
    
    if (!confirm(`Simpan data ini ke database lokal?\nNomor KK: ${nomorKK}\nNama: ${nama}`)) {
        return;
    }
    
    // Kirim data ke endpoint untuk menyimpan ke database lokal
    fetch("{{ route('keluarga.store') }}", {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            nomor_kk: nomorKK,
            alamat: selectedRowData['Alamat Sesuai KK'] || '',
            rt: extractRT(selectedRowData['RT/RW'] || ''),
            rw: extractRW(selectedRowData['RT/RW'] || ''),
            kepala_keluarga: nama,
            nik_kepala: selectedRowData['NIK Kepala Keluarga'] || '',
            nama_istri: selectedRowData['Nama Lengkap Istri'] || null,
            nik_istri: selectedRowData['NIK Istri'] || null,
            total_anggota: selectedRowData['Total Anggota Keluarga'] || 0,
            total_pendapatan: parseCurrency(selectedRowData['Total Pendapatan Keluarga'] || '0'),
            data_tambahan: selectedRowData['Data Tambahan (anggota lain, catatan, perubahan data, dll)'] || null,
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Data berhasil disimpan ke database lokal!');
            // Tutup modal
            bootstrap.Modal.getInstance(document.getElementById('detailModal')).hide();
        } else {
            alert('Gagal menyimpan: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Save error:', error);
        alert('Terjadi kesalahan saat menyimpan data');
    });
}

// Helper functions
function extractRT(rtRw) {
    const parts = rtRw.split('/');
    return parts[0] || '';
}

function extractRW(rtRw) {
    const parts = rtRw.split('/');
    return parts[1] || '';
}

function parseCurrency(value) {
    // Remove currency symbols and thousands separators
    const clean = String(value).replace(/[^0-9\.]/g, '');
    return parseFloat(clean) || 0;
}

function refreshData() {
    loadData(true);
}
</script>
@endpush
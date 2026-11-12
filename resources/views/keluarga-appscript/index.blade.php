@extends('layout.app')

@section('title', 'Data Keluarga')
<link rel="icon" type="image/jpeg" href="{{ asset('images/logo.jpg') }}">
@section('content')
<div class="container-fluid px-3">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="mb-0">Data Keluarga</h4>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div style="min-width: 250px; max-width: 400px;">
                <input 
                    type="text"
                    id="keluargaSearchInput" 
                    class="form-control form-control-sm" 
                    placeholder="Cari No KK, Nama Kepala Keluarga, RT..." 
                    aria-label="Pencarian data keluarga"
                    style="display: block !important; visibility: visible !important;">
            </div>
            
            <!-- Dropdown Export -->
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-success btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-download"></i> Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="#" onclick="event.preventDefault(); exportToExcel('all');">
                            <i class="bi bi-file-earmark-excel text-success"></i> Export Semua Data (Excel)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#" onclick="event.preventDefault(); exportToExcel('filtered');">
                            <i class="bi bi-funnel text-primary"></i> Export Data Terfilter (Excel)
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="#" onclick="event.preventDefault(); exportToCSV('all');">
                            <i class="bi bi-filetype-csv text-info"></i> Export Semua Data (CSV)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#" onclick="event.preventDefault(); exportToCSV('filtered');">
                            <i class="bi bi-filter-circle text-warning"></i> Export Data Terfilter (CSV)
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Button Info/Help -->
            <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#helpModal">
                <i class="bi bi-question-circle"></i> Panduan
            </button>
            
            <a href="{{ route('keluarga-appscript.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle"></i> Tambah Data
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover align-middle mb-0 small"
                    id="keluargaTable"
                    style="white-space: nowrap;">
                    <thead class="bg-sipaduka position-sticky top-0" style="z-index: 10;">
                        <tr id="tableHeader">
                            <th class="text-center">
                                <div class="spinner-border spinner-border-sm text-light" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <tr>
                            <td class="text-center text-muted py-4">
                                <div class="spinner-border spinner-border-sm me-2" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                Memuat data...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Info hasil pencarian -->
    <div id="searchInfo" class="mt-2 text-muted small" style="display: none;">
        Menampilkan <span id="visibleCount">0</span> dari <span id="totalCount">0</span> data
    </div>

</div>

<!-- Modal Panduan Export -->
<div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="helpModalLabel">
                    <i class="bi bi-lightbulb"></i> Panduan Export Data
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    <!-- Cara Export Semua Data -->
                    <div class="col-md-6">
                        <div class="card h-100 border-success">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0"><i class="bi bi-files"></i> Export Semua Data</h6>
                            </div>
                            <div class="card-body">
                                <ol class="mb-0 ps-3">
                                    <li class="mb-2">Klik tombol <span class="badge bg-success">Export</span></li>
                                    <li class="mb-2">Pilih <strong>"Export Semua Data (Excel)"</strong> atau <strong>"Export Semua Data (CSV)"</strong></li>
                                    <li class="mb-2">File akan otomatis terdownload</li>
                                </ol>
                                <div class="alert alert-success mt-3 mb-0 small">
                                    <i class="bi bi-info-circle"></i> Semua data akan diexport tanpa filter
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cara Export Data Terfilter -->
                    <div class="col-md-6">
                        <div class="card h-100 border-primary">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="bi bi-funnel"></i> Export Data Terfilter</h6>
                            </div>
                            <div class="card-body">
                                <ol class="mb-0 ps-3">
                                    <li class="mb-2">Ketik kata kunci di <strong>kotak pencarian</strong> (contoh: RT, nama, dll)</li>
                                    <li class="mb-2">Data di tabel akan terfilter otomatis</li>
                                    <li class="mb-2">Klik tombol <span class="badge bg-success">Export</span></li>
                                    <li class="mb-2">Pilih <strong>"Export Data Terfilter"</strong></li>
                                </ol>
                                <div class="alert alert-primary mt-3 mb-0 small">
                                    <i class="bi bi-info-circle"></i> Hanya data yang muncul di tabel yang akan diexport
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Perbedaan Format -->
                    <div class="col-12">
                        <div class="card border-info">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-file-earmark-arrow-down"></i> Perbedaan Format File</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-success"><i class="bi bi-file-earmark-excel"></i> Format Excel (.xlsx)</h6>
                                        <ul class="small">
                                            <li>Dapat diedit langsung di Microsoft Excel</li>
                                            <li>Mendukung formatting (warna, border, dll)</li>
                                            <li>Ukuran file lebih besar</li>
                                            <li>Cocok untuk analisis data</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-info"><i class="bi bi-filetype-csv"></i> Format CSV (.csv)</h6>
                                        <ul class="small">
                                            <li>Dapat dibuka di Excel & Google Sheets</li>
                                            <li>Format text sederhana</li>
                                            <li>Ukuran file lebih kecil</li>
                                            <li>Cocok untuk import ke sistem lain</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tips & Trik -->
                    <div class="col-12">
                        <div class="card border-warning">
                            <div class="card-header bg-warning bg-opacity-25">
                                <h6 class="mb-0"><i class="bi bi-star"></i> Tips & Trik</h6>
                            </div>
                            <div class="card-body">
                                <ul class="mb-0 small">
                                    <li class="mb-2"><strong>Pencarian Multi Kata:</strong> Gunakan spasi untuk mencari beberapa kata (contoh: "RT 01 Jakarta")</li>
                                    <li class="mb-2"><strong>Clear Search:</strong> Tekan tombol <kbd>ESC</kbd> untuk menghapus pencarian</li>
                                    <li class="mb-2"><strong>Nama File:</strong> File akan tersimpan dengan format <code>Data_Keluarga_YYYY-MM-DD-HH-MM-SS.xlsx</code></li>
                                    <li class="mb-2"><strong>Lihat Progress:</strong> Perhatikan info "Menampilkan X dari Y data" di bawah tabel</li>
                                    <li class="mb-2"><strong>File Tidak Terdownload?</strong> Periksa popup blocker browser Anda</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Loading Export - DIHAPUS, tidak digunakan lagi -->

@endsection

@push('scripts')
<!-- Library SheetJS untuk Excel -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<script>
// Variable global untuk menyimpan data
let allData = [];
let filteredData = [];
let allHeaders = [];
let isFiltered = false; // Flag untuk cek apakah sedang dalam mode filter

document.addEventListener('DOMContentLoaded', () => {
    loadData();
});

// Fungsi untuk memuat data
function loadData() {
    fetch("{{ route('api-keluarga-appscript.index') }}")
        .then(res => {
            if (!res.ok) {
                throw new Error('Network response was not ok');
            }
            return res.json();
        })
        .then(data => {
            console.log('Data berhasil dimuat:', data);
            allData = data;
            filteredData = data; // Set filtered data sama dengan all data awalnya

            const tableBody = document.getElementById('tableBody');
            const tableHeader = document.getElementById('tableHeader');

            if (!data || data.length === 0) {
                tableHeader.innerHTML = '<th class="text-center">Tidak Ada Data</th>';
                tableBody.innerHTML = `
                    <tr>
                        <td class="text-center text-muted py-4">
                            <i class="bi bi-inbox"></i>
                            <p class="mb-0 mt-2">Belum ada data keluarga</p>
                        </td>
                    </tr>`;
                return;
            }

            // Ambil headers dari object pertama
            allHeaders = Object.keys(data[0]);
            
            // Render header
            renderHeader();
            
            // Render data
            renderTableData(data);
            
            // Inisialisasi fungsi pencarian
            initSearch();
            
            // Update info
            updateSearchInfo(data.length, data.length);

        })
        .catch(err => {
            console.error('Error memuat data:', err);
            document.getElementById('tableBody').innerHTML = `
                <tr>
                    <td class="text-center text-danger py-4">
                        <i class="bi bi-exclamation-triangle"></i>
                        <p class="mb-0 mt-2">Gagal memuat data</p>
                        <button class="btn btn-sm btn-outline-danger mt-2" onclick="loadData()">
                            <i class="bi bi-arrow-clockwise"></i> Coba Lagi
                        </button>
                    </td>
                </tr>`;
        });
}

// Fungsi untuk render header tabel
function renderHeader() {
    const headerRow = document.getElementById('tableHeader');
    headerRow.innerHTML = '';
    
    allHeaders.forEach(h => {
        const th = document.createElement('th');
        th.textContent = h;
        th.style.minWidth = '120px';
        headerRow.appendChild(th);
    });
    
    // Tambah kolom aksi
    const thAction = document.createElement('th');
    thAction.textContent = 'Aksi';
    thAction.style.minWidth = '100px';
    thAction.className = 'text-center';
    headerRow.appendChild(thAction);
}

// Fungsi untuk render data tabel
function renderTableData(data) {
    const tbody = document.getElementById('tableBody');
    tbody.innerHTML = '';
    
    if (!data || data.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="${allHeaders.length + 1}" class="text-center text-muted py-3">
                    <i class="bi bi-search"></i> Tidak ada data yang sesuai
                </td>
            </tr>`;
        return;
    }
    
    data.forEach((row, index) => {
        const tr = document.createElement('tr');
        tr.dataset.index = index;
        
        allHeaders.forEach(h => {
            const td = document.createElement('td');
            // Perbaikan: pastikan value tidak null/undefined
            const value = row[h];
            td.textContent = (value !== null && value !== undefined) ? value : '-';
            tr.appendChild(td);
        });
        
        // Kolom aksi
        const tdAction = document.createElement('td');
        tdAction.className = 'text-center';
        // Perbaikan: pastikan Nomor KK tidak null/undefined
        const nomorKK = row['Nomor KK'] || '';
        tdAction.innerHTML = `
            <button class="btn btn-danger btn-sm" 
                onclick="hapus('${escapeHtml(nomorKK)}')"
                title="Hapus data">
                <i class="bi bi-trash"></i>
            </button>
        `;
        tr.appendChild(tdAction);
        
        tbody.appendChild(tr);
    });
}

// Fungsi untuk escape HTML (mencegah XSS)
function escapeHtml(text) {
    // Tangani null, undefined, atau nilai kosong
    if (text === null || text === undefined || text === '') {
        return '';
    }
    
    // Konversi ke string terlebih dahulu
    text = String(text);
    
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// Fungsi pencarian
function initSearch() {
    const searchInput = document.getElementById('keluargaSearchInput');
    
    if (!searchInput) {
        console.error('Search input tidak ditemukan!');
        return;
    }
    
    console.log('Pencarian berhasil diinisialisasi');
    
    searchInput.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase().trim();
        
        if (searchTerm === '') {
            // Tampilkan semua data
            filteredData = allData;
            isFiltered = false;
            renderTableData(allData);
            updateSearchInfo(allData.length, allData.length);
            return;
        }
        
        // Filter data berdasarkan pencarian
        filteredData = allData.filter(row => {
            // Cari di semua kolom
            return allHeaders.some(header => {
                const value = row[header];
                if (value === null || value === undefined) return false;
                return String(value).toLowerCase().includes(searchTerm);
            });
        });
        
        isFiltered = true;
        
        // Render hasil pencarian
        renderTableData(filteredData);
        updateSearchInfo(filteredData.length, allData.length);
    });
    
    // Tambahkan icon clear di input
    searchInput.addEventListener('keyup', function(e) {
        if (e.key === 'Escape') {
            this.value = '';
            this.dispatchEvent(new Event('input'));
        }
    });
}

// Update info hasil pencarian
function updateSearchInfo(visible, total) {
    const searchInfo = document.getElementById('searchInfo');
    const visibleCount = document.getElementById('visibleCount');
    const totalCount = document.getElementById('totalCount');
    
    if (searchInfo && visibleCount && totalCount) {
        visibleCount.textContent = visible;
        totalCount.textContent = total;
        
        if (visible < total) {
            searchInfo.style.display = 'block';
        } else {
            searchInfo.style.display = 'none';
        }
    }
}

// ============================================
// FUNGSI EXPORT TO EXCEL
// ============================================

function exportToExcel(type = 'all') {
    // Tampilkan loading indicator sederhana
    showLoadingIndicator(true);

    // Delay untuk memberikan feedback visual
    setTimeout(() => {
        try {
            // Tentukan data yang akan diexport
            const dataToExport = type === 'filtered' ? filteredData : allData;
            
            console.log('Export type:', type);
            console.log('Data to export:', dataToExport.length, 'records');
            
            if (!dataToExport || dataToExport.length === 0) {
                alert('Tidak ada data untuk diexport!');
                showLoadingIndicator(false);
                return;
            }

            // Siapkan data untuk export (hapus kolom yang tidak perlu)
            const exportData = dataToExport.map(row => {
                const newRow = {};
                allHeaders.forEach(header => {
                    newRow[header] = row[header] ?? '-';
                });
                return newRow;
            });

            // Buat workbook dan worksheet
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.json_to_sheet(exportData);

            // Set lebar kolom otomatis
            const colWidths = allHeaders.map(header => {
                const maxLength = Math.max(
                    header.length,
                    ...exportData.map(row => String(row[header] || '').length)
                );
                return { wch: Math.min(maxLength + 2, 50) }; // Max 50 karakter
            });
            ws['!cols'] = colWidths;

            // Tambahkan worksheet ke workbook
            XLSX.utils.book_append_sheet(wb, ws, 'Data Keluarga');

            // Generate nama file dengan timestamp
            const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, '-');
            const fileName = `Data_Keluarga_${type === 'filtered' ? 'Filtered_' : ''}${timestamp}.xlsx`;

            // Download file
            XLSX.writeFile(wb, fileName);

            // Sembunyikan loading
            showLoadingIndicator(false);

            // Tampilkan notifikasi sukses
            showToast('success', `Berhasil mengekspor ${dataToExport.length} data ke Excel!`);

        } catch (error) {
            console.error('Error saat export:', error);
            showLoadingIndicator(false);
            alert('Terjadi kesalahan saat mengekspor data: ' + error.message);
        }
    }, 500);
}

// ============================================
// FUNGSI LOADING INDICATOR
// ============================================

function showLoadingIndicator(show) {
    let loadingDiv = document.getElementById('exportLoadingIndicator');
    
    if (show) {
        // Buat loading indicator jika belum ada
        if (!loadingDiv) {
            loadingDiv = document.createElement('div');
            loadingDiv.id = 'exportLoadingIndicator';
            loadingDiv.className = 'position-fixed top-50 start-50 translate-middle';
            loadingDiv.style.zIndex = '9999';
            loadingDiv.innerHTML = `
                <div class="bg-white rounded-3 shadow-lg p-4 text-center" style="min-width: 200px;">
                    <div class="spinner-border text-success mb-3" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h6 class="mb-2">Mengekspor Data...</h6>
                    <p class="text-muted small mb-0">Mohon tunggu sebentar</p>
                </div>
            `;
            document.body.appendChild(loadingDiv);
            
            // Tambahkan backdrop
            const backdrop = document.createElement('div');
            backdrop.id = 'exportLoadingBackdrop';
            backdrop.className = 'modal-backdrop fade show';
            backdrop.style.zIndex = '9998';
            document.body.appendChild(backdrop);
            document.body.classList.add('modal-open');
        }
        loadingDiv.style.display = 'block';
    } else {
        // Hapus loading indicator
        if (loadingDiv) {
            loadingDiv.style.display = 'none';
        }
        const backdrop = document.getElementById('exportLoadingBackdrop');
        if (backdrop) {
            backdrop.remove();
        }
        document.body.classList.remove('modal-open');
    }
}

// ============================================
// FUNGSI EXPORT TO CSV
// ============================================

function exportToCSV(type = 'all') {
    try {
        // Tentukan data yang akan diexport
        const dataToExport = type === 'filtered' ? filteredData : allData;
        
        console.log('Export CSV type:', type);
        console.log('Data to export:', dataToExport.length, 'records');
        
        if (!dataToExport || dataToExport.length === 0) {
            alert('Tidak ada data untuk diexport!');
            return;
        }

        // Tampilkan loading
        showLoadingIndicator(true);

        setTimeout(() => {
            try {
                // Buat CSV content dengan BOM untuk Excel compatibility
                let csvContent = '\uFEFF'; // BOM untuk UTF-8
                
                // Tambahkan header
                csvContent += allHeaders.map(h => `"${h}"`).join(',') + '\r\n';
                
                // Tambahkan data
                dataToExport.forEach(row => {
                    const rowData = allHeaders.map(header => {
                        let value = row[header] ?? '';
                        // Konversi ke string dan escape quotes
                        value = String(value).replace(/"/g, '""');
                        // Wrap dengan quotes
                        return `"${value}"`;
                    });
                    csvContent += rowData.join(',') + '\r\n';
                });

                // Buat blob dan download
                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                const url = URL.createObjectURL(blob);
                
                const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, '-');
                const fileName = `Data_Keluarga_${type === 'filtered' ? 'Filtered_' : ''}${timestamp}.csv`;
                
                link.setAttribute('href', url);
                link.setAttribute('download', fileName);
                link.style.visibility = 'hidden';
                
                document.body.appendChild(link);
                link.click();
                
                // Cleanup
                setTimeout(() => {
                    document.body.removeChild(link);
                    URL.revokeObjectURL(url);
                }, 100);

                // Sembunyikan loading
                showLoadingIndicator(false);

                // Tampilkan notifikasi
                showToast('success', `Berhasil mengekspor ${dataToExport.length} data ke CSV!`);

            } catch (innerError) {
                console.error('Inner error saat export CSV:', innerError);
                showLoadingIndicator(false);
                alert('Terjadi kesalahan saat mengekspor data: ' + innerError.message);
            }
        }, 300);

    } catch (error) {
        console.error('Error saat export CSV:', error);
        showLoadingIndicator(false);
        alert('Terjadi kesalahan saat mengekspor data: ' + error.message);
    }
}

// ============================================
// FUNGSI TOAST NOTIFICATION
// ============================================

function showToast(type, message) {
    // Buat container toast jika belum ada
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        toastContainer.style.zIndex = '10000';
        document.body.appendChild(toastContainer);
    }
    
    // Buat toast element
    const toastElement = document.createElement('div');
    toastElement.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0`;
    toastElement.setAttribute('role', 'alert');
    toastElement.setAttribute('aria-live', 'assertive');
    toastElement.setAttribute('aria-atomic', 'true');
    toastElement.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    // Tambahkan ke container
    toastContainer.appendChild(toastElement);
    
    // Cek apakah Bootstrap tersedia
    if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
        // Gunakan Bootstrap Toast
        const toast = new bootstrap.Toast(toastElement, { delay: 3000 });
        toast.show();
        
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    } else {
        // Fallback: tampilkan manual dengan CSS
        toastElement.classList.add('show');
        
        // Auto hide setelah 3 detik
        setTimeout(() => {
            toastElement.classList.remove('show');
            setTimeout(() => toastElement.remove(), 300);
        }, 3000);
        
        // Close button handler
        const closeBtn = toastElement.querySelector('.btn-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                toastElement.classList.remove('show');
                setTimeout(() => toastElement.remove(), 300);
            });
        }
    }
}

// Fungsi hapus data
function hapus(nomorKk) {
    if (!nomorKk || nomorKk === 'undefined' || nomorKk === 'null') {
        alert('Nomor KK tidak valid');
        return;
    }

    if (!confirm(`Yakin ingin menghapus data dengan Nomor KK: ${nomorKk}?`)) {
        return;
    }
    
    // Tampilkan loading
    const tbody = document.getElementById('tableBody');
    const originalContent = tbody.innerHTML;
    tbody.innerHTML = `
        <tr>
            <td colspan="${allHeaders.length + 1}" class="text-center py-3">
                <div class="spinner-border spinner-border-sm me-2" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                Menghapus data...
            </td>
        </tr>`;

    fetch(`{{ url('api-keluarga-appscript') }}/${encodeURIComponent(nomorKk)}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(res => {
        if (!res.ok) {
            throw new Error('Gagal menghapus data');
        }
        return res.json();
    })
    .then(result => {
        console.log('Hasil hapus:', result);

        if (result.success) {
            showToast('success', result.message || 'Data berhasil dihapus!');
            // Reload data tanpa refresh halaman
            loadData();
        } else {
            alert(result.message || 'Gagal menghapus data');
            tbody.innerHTML = originalContent;
        }
    })
    .catch(err => {
        console.error('Error menghapus data:', err);
        alert('Terjadi kesalahan saat menghapus data: ' + err.message);
        tbody.innerHTML = originalContent;
    });
}
</script>

<style>
/* Pastikan search input selalu terlihat */
#keluargaSearchInput {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    height: auto !important;
    border: 1px solid #ced4da !important;
}

#keluargaSearchInput:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

/* Styling untuk tabel */
#keluargaTable thead th {
    background-color: var(--bs-primary, #0d6efd);
    color: white;
    font-weight: 600;
    vertical-align: middle;
}

.bg-sipaduka {
    background-color: #0d6efd !important;
}

/* Dropdown styling */
.dropdown-item {
    font-size: 0.9rem;
    padding: 0.5rem 1rem;
}

.dropdown-item i {
    width: 20px;
    text-align: center;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
}

/* Responsive table */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.85rem;
    }
    
    #keluargaSearchInput {
        min-width: 100% !important;
    }
    
    .btn-group {
        width: 100%;
    }
    
    .btn-group .btn {
        width: 100%;
    }
}

/* Loading animation */
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.spinner-border-sm {
    width: 1rem;
    height: 1rem;
    border-width: 0.15em;
}

/* Toast styling */
.toast-container {
    z-index: 10000;
}

.toast {
    min-width: 300px;
}

.toast.show {
    display: block;
    opacity: 1;
}

/* Loading backdrop */
.modal-backdrop {
    background-color: rgba(0, 0, 0, 0.5);
}

.modal-open {
    overflow: hidden;
}

/* Help Modal Styling */
.modal-header.bg-info {
    background-color: #0dcaf0 !important;
}

.modal-body .card {
    transition: transform 0.2s, box-shadow 0.2s;
}

.modal-body .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

kbd {
    padding: 2px 6px;
    font-size: 0.875em;
    color: #fff;
    background-color: #212529;
    border-radius: 3px;
}

code {
    font-size: 0.875em;
    color: #d63384;
    background-color: #f8f9fa;
    padding: 2px 6px;
    border-radius: 3px;
}
</style>
@endpush
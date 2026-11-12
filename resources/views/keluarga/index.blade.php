@extends('layout.app')

@section('title', 'Data Keluarga')
<link rel="icon" type="image/jpeg" href="{{ asset('images/logo.jpg') }}">

@section('content')
<div class="container px-0 overflow-x-auto">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Data Keluarga</h4>
        <a href="{{ route('keluarga-appscript.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Tambah Data
        </a>
    </div>

    <!-- Alert Notification -->
    <div id="alertContainer"></div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover align-middle mb-0 small" 
                       id="keluargaTable" style="white-space: nowrap;">
                    <thead class="table-dark position-sticky top-0">
                        <tr id="tableHeader"></tr>
                    </thead>
                    <tbody id="tableBody">
                        <tr>
                            <td class="text-center text-muted py-4" colspan="100">
                                <div class="spinner-border spinner-border-sm" role="status">
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

</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
    #keluargaTable th {
        position: sticky;
        top: 0;
        z-index: 10;
        background-color: #212529;
        color: white;
    }
    
    .btn-delete {
        transition: all 0.3s;
    }
    
    .btn-delete:hover {
        transform: scale(1.1);
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    loadData();
    
    // Auto refresh setiap 10 detik
    setInterval(loadData, 10000);
});

function loadData() {
    fetch("{{ route('api-keluarga-appscript.index') }}")
        .then(res => {
            if (!res.ok) {
                throw new Error('Network response was not ok');
            }
            return res.json();
        })
        .then(data => {
            const tableBody = document.getElementById('tableBody');
            const tableHeader = document.getElementById('tableHeader');
            
            if (!data || data.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td class="text-center text-muted py-4" colspan="100">
                            <i class="bi bi-database-x"></i> Tidak ada data keluarga
                        </td>
                    </tr>
                `;
                return;
            }

            // Get headers from first data row
            const headers = Object.keys(data[0]);
            
            // Build header row
            let headerHTML = '';
            headers.forEach(h => {
                if (h !== 'id') { // Exclude id column from display
                    headerHTML += `<th>${formatHeader(h)}</th>`;
                }
            });
            headerHTML += `<th class="text-center">Aksi</th>`;
            tableHeader.innerHTML = headerHTML;

            // Build body rows
            let bodyHTML = '';
            data.forEach((row, index) => {
                let rowHTML = '<tr>';
                
                headers.forEach(h => {
                    if (h !== 'id') {
                        const value = row[h] || '';
                        rowHTML += `<td title="${h}: ${value}">${truncateText(value, 30)}</td>`;
                    }
                });
                
                // Action buttons
                rowHTML += `
                    <td class="text-center">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary btn-sm" 
                                    onclick="viewDetail('${row['Nomor KK']}')" 
                                    title="Lihat Detail">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm btn-delete" 
                                    onclick="confirmDelete('${row['Nomor KK']}', '${escapeString(row['Nama Lengkap Kepala Keluarga'] || '')}')" 
                                    title="Hapus Data">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>`;
                
                bodyHTML += rowHTML;
            });
            
            tableBody.innerHTML = bodyHTML;
        })
        .catch(err => {
            console.error('Error:', err);
            showAlert('Gagal memuat data. Silakan refresh halaman.', 'error');
        });
}

function confirmDelete(nomorKK, nama) {
    Swal.fire({
        title: 'Konfirmasi Hapus',
        html: `Apakah Anda yakin ingin menghapus data keluarga:<br>
               <strong>${nama || 'Tidak ada nama'}</strong><br>
               <small>Nomor KK: ${nomorKK}</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        backdrop: true,
        allowOutsideClick: false,
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return fetch(`{{ url('api/keluarga-appscript') }}/${encodeURIComponent(nomorKK)}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Gagal menghapus data');
                }
                return data;
            })
            .catch(error => {
                Swal.showValidationMessage(`Request failed: ${error.message}`);
            });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Berhasil!',
                text: result.value.message || 'Data berhasil dihapus',
                icon: 'success',
                confirmButtonColor: '#28a745',
                timer: 2000,
                timerProgressBar: true
            }).then(() => {
                // Refresh data
                loadData();
            });
        }
    });
}

function viewDetail(nomorKK) {
    Swal.fire({
        title: 'Detail Data Keluarga',
        html: `<div class="text-center">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
               </div>`,
        showConfirmButton: false,
        allowOutsideClick: false,
        width: '800px'
    });
    
    // Fetch detail data
    fetch(`{{ route('api-keluarga-appscript.index') }}`)
        .then(res => res.json())
        .then(data => {
            const keluarga = data.find(item => item['Nomor KK'] === nomorKK);
            
            if (!keluarga) {
                Swal.fire({
                    title: 'Error',
                    text: 'Data tidak ditemukan',
                    icon: 'error'
                });
                return;
            }
            
            let detailHTML = '<div class="table-responsive" style="max-height: 400px; overflow-y: auto;">';
            detailHTML += '<table class="table table-sm">';
            detailHTML += '<tbody>';
            
            for (const [key, value] of Object.entries(keluarga)) {
                if (key !== 'id' && key !== 'Timestamp') {
                    detailHTML += `
                        <tr>
                            <td class="fw-bold" style="width: 40%;">${formatHeader(key)}</td>
                            <td>${value || '-'}</td>
                        </tr>
                    `;
                }
            }
            
            detailHTML += '</tbody></table></div>';
            
            Swal.fire({
                title: `Detail: ${keluarga['Nama Lengkap Kepala Keluarga'] || 'Data Keluarga'}`,
                html: detailHTML,
                width: '800px',
                confirmButtonText: 'Tutup',
                showCloseButton: true
            });
        })
        .catch(err => {
            Swal.fire({
                title: 'Error',
                text: 'Gagal memuat detail data',
                icon: 'error'
            });
        });
}

// Helper functions
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
    return str
        .replace(/'/g, "\\'")
        .replace(/"/g, '\\"')
        .replace(/\n/g, ' ');
}

function showAlert(message, type = 'success') {
    const alertContainer = document.getElementById('alertContainer');
    const alertClass = type === 'error' ? 'alert-danger' : 'alert-success';
    
    alertContainer.innerHTML = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    // Auto hide after 5 seconds
    setTimeout(() => {
        const alert = alertContainer.querySelector('.alert');
        if (alert) {
            alert.remove();
        }
    }, 5000);
}
</script>
@endpush
@extends('layout.app')

@section('title', 'Dashboard RW')
<link rel="icon" type="image/jpeg" href="{{ asset('images/logo.jpg') }}">

@section('content')
    <!-- Header Dashboard RW -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">RW {{ $nomorRw ?? '001' }}</h1>
            <p class="text-muted mb-0">Selamat datang, {{ $userName ?? auth()->user()->name }}</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success" id="autoUpdateStatus">
                <i class="fas fa-sync-alt me-1"></i> Auto-update aktif (30 detik)
            </span>
            <div class="badge bg-primary p-2">
                <i class="fas fa-user-tie me-1"></i> RW {{ $nomorRw ?? '001' }}
            </div>
        </div>
    </div>

    <!-- Alert jika ada error -->
    @if(isset($error) && $error)
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        {{ $error }}
        <br><small>Data yang ditampilkan mungkin tidak akurat</small>
    </div>
    @endif

    <!-- Quick Stats untuk RW -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total KK
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalKK">{{ $totalKK ?? 0 }}</div>
                            <small class="text-muted">Kartu Keluarga</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-home fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Jiwa
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalJiwa">{{ $totalJiwa ?? 0 }}</div>
                            <small class="text-muted" id="jiwaPerkk">
                                @if(($totalJiwa ?? 0) > 0)
                                {{ ($totalKK ?? 0) > 0 ? round($totalJiwa/$totalKK, 1) : 0 }} jiwa/KK
                                @else
                                Data tidak tersedia
                                @endif
                            </small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                RT Terdaftar
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalRT">{{ $totalRT ?? 0 }}</div>
                            <small class="text-muted">Wilayah RT</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-map-marker-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Rata-rata
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="rataAnggota">{{ $rataAnggota ?? 0 }}</div>
                            <small class="text-muted">Anggota per KK</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-bar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Klasifikasi: Jumlah Warga per RT -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Klasifikasi Warga per RT</h6>
                    <span class="badge bg-info" id="lastUpdate">Update: {{ now()->format('H:i:s') }}</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered" id="rtTable">
                            <thead>
                                <tr>
                                    <th>RT</th>
                                    <th class="text-end">Jumlah Warga</th>
                                    <th class="text-end">Jumlah KK</th>
                                    <th class="text-end">Persentase</th>
                                </tr>
                            </thead>
                            <tbody id="rtTableBody">
                                @if(isset($perRtCounts) && $perRtCounts->count() > 0)
                                    @php
                                        $totalWarga = $perRtCounts->sum('warga');
                                    @endphp
                                    @foreach($perRtCounts as $rt)
                                    @php
                                        $persentase = $totalWarga > 0 ? round(($rt->warga / $totalWarga) * 100, 1) : 0;
                                    @endphp
                                    <tr>
                                        <td>RT {{ $rt->rt }}</td>
                                        <td class="text-end">{{ number_format($rt->warga, 0, ',', '.') }}</td>
                                        <td class="text-end">{{ $rt->kk_count }}</td>
                                        <td class="text-end">
                                            <span class="badge bg-info">{{ $persentase }}%</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                    <tr class="table-secondary">
                                        <td><strong>Total</strong></td>
                                        <td class="text-end"><strong>{{ number_format($totalWarga, 0, ',', '.') }}</strong></td>
                                        <td class="text-end"><strong>{{ $perRtCounts->sum('kk_count') }}</strong></td>
                                        <td class="text-end"><strong>100%</strong></td>
                                    </tr>
                                @else
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Belum ada data</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart Visualisasi per RT -->
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Visualisasi Data per RT</h6>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" id="chartTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="bar-tab" data-bs-toggle="tab" data-bs-target="#bar-chart" type="button" role="tab">
                                <i class="fas fa-chart-bar me-1"></i> Bar Chart
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pie-tab" data-bs-toggle="tab" data-bs-target="#pie-chart" type="button" role="tab">
                                <i class="fas fa-chart-pie me-1"></i> Pie Chart (Warga)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pie-kk-tab" data-bs-toggle="tab" data-bs-target="#pie-kk-chart" type="button" role="tab">
                                <i class="fas fa-chart-pie me-1"></i> Pie Chart (KK)
                            </button>
                        </li>
                    </ul>
                    <div class="tab-content mt-3">
                        <div class="tab-pane fade show active" id="bar-chart" role="tabpanel">
                            <canvas id="rtBarChart" height="200"></canvas>
                        </div>
                        <div class="tab-pane fade" id="pie-chart" role="tabpanel">
                            <canvas id="rtPieChart" height="200"></canvas>
                        </div>
                        <div class="tab-pane fade" id="pie-kk-chart" role="tabpanel">
                            <canvas id="rtPieChartKK" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Keluarga Terbaru -->
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Keluarga Terbaru</h6>
                    <span class="badge bg-secondary">Terakhir diperbarui: {{ now()->format('d/m/Y H:i') }}</span>
                </div>
                <div class="card-body">
                    @if(isset($keluargaTerbaru) && $keluargaTerbaru->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="keluargaTable">
                            <thead class="table-light">
                                <tr>
                                    <th>No KK</th>
                                    <th>Kepala Keluarga</th>
                                    <th>RT</th>
                                    <th>RW</th>
                                    <th class="text-end">Jumlah Anggota</th>
                                    <th>Tanggal Input</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($keluargaTerbaru as $keluarga)
                                <tr>
                                    <td>
                                        <span class="badge bg-primary">{{ $keluarga->nomor_kk }}</span>
                                    </td>
                                    <td>
                                        <i class="fas fa-user me-1 text-muted"></i>
                                        {{ $keluarga->kepala_keluarga }}
                                    </td>
                                    <td>
                                        <span class="badge bg-info">RT {{ $keluarga->rt }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning">RW {{ $keluarga->rw }}</span>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge bg-success">{{ $keluarga->total_anggota }} orang</span>
                                    </td>
                                    <td>
                                        <i class="fas fa-calendar-alt me-1 text-muted"></i>
                                        {{ $keluarga->created_at->format('d/m/Y H:i') }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Belum ada data keluarga terbaru.
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Script Auto-Refresh -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <script>
    // Set interval untuk reload halaman
    let refreshInterval = setInterval(function() {
        location.reload();
    }, 30000); // Reload setiap 30 detik

    // Fungsi untuk menghentikan refresh jika halaman berpindah
    window.addEventListener('beforeunload', function() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
    });

    // Inisialisasi chart setelah DOM siap
    document.addEventListener('DOMContentLoaded', function() {
        // Fungsi untuk membuat warna chart
        function generateColors(count) {
            const colors = [];
            const baseColors = [
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', 
                '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#36A2EB'
            ];
            
            for (let i = 0; i < count; i++) {
                colors.push(baseColors[i % baseColors.length]);
            }
            
            return colors;
        }

        // Inisialisasi chart jika elemen canvas ada
        @if(isset($perRtCounts) && $perRtCounts->count() > 0)
            const labels = [];
            const wargaData = [];
            const kkData = [];

            @foreach($perRtCounts as $rt)
                labels.push('RT {{ $rt->rt }}');
                wargaData.push({{ $rt->warga }});
                kkData.push({{ $rt->kk_count }});
            @endforeach

            // Bar Chart
            const barCtx = document.getElementById('rtBarChart');
            if (barCtx) {
                new Chart(barCtx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Jumlah Warga',
                            data: wargaData,
                            backgroundColor: 'rgba(54, 162, 235, 0.7)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1,
                            borderRadius: 5,
                            order: 2
                        }, {
                            label: 'Jumlah KK',
                            data: kkData,
                            backgroundColor: 'rgba(255, 99, 132, 0.7)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1,
                            borderRadius: 5,
                            order: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { stepSize: 1 },
                                title: { display: true, text: 'Jumlah' }
                            },
                            x: { title: { display: true, text: 'RT' } }
                        },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) label += ': ';
                                        label += context.raw.toLocaleString('id-ID');
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Pie Chart Warga
            const pieCtx = document.getElementById('rtPieChart');
            if (pieCtx) {
                const total = wargaData.reduce((a, b) => a + b, 0);
                new Chart(pieCtx, {
                    type: 'pie',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: wargaData,
                            backgroundColor: generateColors(labels.length),
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: { position: 'right' },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.raw;
                                        const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                        return `${label}: ${value.toLocaleString('id-ID')} (${percentage}%)`;
                                    }
                                }
                            },
                            datalabels: {
                                formatter: (value, ctx) => {
                                    const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                    return percentage > 5 ? `${percentage}%` : '';
                                },
                                color: '#fff',
                                font: { weight: 'bold' }
                            }
                        }
                    },
                    plugins: [ChartDataLabels]
                });
            }

            // Pie Chart KK
            const pieKkCtx = document.getElementById('rtPieChartKK');
            if (pieKkCtx) {
                const totalKk = kkData.reduce((a, b) => a + b, 0);
                new Chart(pieKkCtx, {
                    type: 'pie',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: kkData,
                            backgroundColor: generateColors(labels.length),
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: { position: 'right' },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.raw;
                                        const percentage = totalKk > 0 ? Math.round((value / totalKk) * 100) : 0;
                                        return `${label}: ${value.toLocaleString('id-ID')} (${percentage}%)`;
                                    }
                                }
                            },
                            datalabels: {
                                formatter: (value, ctx) => {
                                    const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                    return percentage > 5 ? `${percentage}%` : '';
                                },
                                color: '#fff',
                                font: { weight: 'bold' }
                            }
                        }
                    },
                    plugins: [ChartDataLabels]
                });
            }
        @endif

        // Inisialisasi tab bootstrap
        const chartTabs = document.querySelectorAll('#chartTabs button');
        chartTabs.forEach(tab => {
            tab.addEventListener('shown.bs.tab', function (event) {
                setTimeout(() => {
                    window.dispatchEvent(new Event('resize'));
                }, 100);
            });
        });
    });
    </script>
@endsection
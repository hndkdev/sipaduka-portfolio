<?php

namespace App\Http\Controllers;

use App\Models\Rw;
use App\Models\User;
use App\Models\Keluarga;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class RwController extends Controller
{
    // ============ DASHBOARD RW METHOD ============
    public function dashboard()
    {
        $user = auth()->user();
        
        // Validasi role
        if ($user->role !== 'rw') {
            return redirect()->route('admin.dashboard');
        }
        
        // Cari data RW user
        $rw = Rw::where('userid', $user->id)->first();
        
        if (!$rw) {
            return view('rw.dashboard', [
                'error' => 'User RW tidak memiliki data RW yang terhubung.',
                'nomorRw' => '001',
                'totalKK' => 0,
                'totalJiwa' => 0,
                'totalRT' => 0,
                'keluargaTerbaru' => collect(),
                'perRtCounts' => collect(),
                'userName' => $user->name
            ]);
        }

        $nomorRw = $rw->nomor_rw;
        $rwId = $rw->id;

        // Coba ambil statistik dari Apps Script (Google Sheets)
        $appsStats = $this->calculateRwStatsFromAppsScript($nomorRw);

        if ($appsStats['success']) {
            $stats = $appsStats;
            $keluargaTerbaru = collect($appsStats['latest'] ?? []);
        } else {
            // fallback ke DB
            $stats = $this->calculateRwStatsSimple($rwId, $nomorRw);
            $keluargaTerbaru = $this->getKeluargaTerbaruSimple($rwId, $nomorRw);
        }
        
        // Hitung jumlah warga per RT - GABUNGKAN DARI DB DAN APPS SCRIPT
        $perRtCounts = $this->getPerRtCountsData($rwId, $nomorRw);

        return view('rw.dashboard', array_merge($stats, [
            'nomorRw' => $nomorRw,
            'keluargaTerbaru' => $keluargaTerbaru,
            'userName' => $user->name,
            'rwData' => $rw,
            'perRtCounts' => $perRtCounts
        ]));
    }

    /**
     * ====================================================================
     * API ENDPOINT - Ambil jumlah warga per RT (untuk AJAX)
     * ====================================================================
     */
    public function getPerRtCounts()
    {
        try {
            $user = auth()->user();

            if ($user->role !== 'rw') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $rw = Rw::where('userid', $user->id)->first();
            
            if (!$rw) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data RW tidak ditemukan'
                ], 404);
            }

            $rwId = $rw->id;
            $nomorRw = $rw->nomor_rw;

            $perRtCounts = $this->getPerRtCountsData($rwId, $nomorRw);

            return response()->json([
                'success' => true,
                'data' => $perRtCounts->map(function ($item) {
                    return [
                        'rt' => (string) $item->rt,
                        'warga_count' => (int) $item->warga,
                        'kk_count' => (int) $item->kk_count,
                    ];
                })->values()
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error getPerRtCounts API: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ====================================================================
     * FUNGSI UTAMA - Ambil dan gabungkan data RT dari DB dan Apps Script
     * ====================================================================
     */
    private function getPerRtCountsData($rwId, $nomorRw)
    {
        try {
            \Log::info('=== START getPerRtCountsData ===', [
                'rwId' => $rwId, 
                'nomorRw' => $nomorRw
            ]);

            // 1. Ambil data dari DATABASE LOKAL
            $localData = $this->getLocalRTData($rwId, $nomorRw);
            \Log::info('Local DB data:', ['count' => $localData->count(), 'data' => $localData->toArray()]);

            // 2. Ambil data dari APPS SCRIPT (Google Sheets)
            $appsData = $this->getAppsScriptRTData($nomorRw);
            \Log::info('Apps Script data:', ['count' => count($appsData), 'data' => $appsData]);

            // 3. GABUNGKAN DATA dari kedua sumber
            $mergedData = collect();

            // Proses data lokal terlebih dahulu
            foreach ($localData as $row) {
                $mergedData->push((object) [
                    'rt' => str_pad((string) $row->rt, 3, '0', STR_PAD_LEFT),
                    'warga' => (int) $row->warga,
                    'kk_count' => (int) $row->kk_count,
                ]);
            }

            // Tambahkan/merge data dari Apps Script
            foreach ($appsData as $row) {
                $rtFormatted = str_pad((string) $row['rt'], 3, '0', STR_PAD_LEFT);
                $existing = $mergedData->firstWhere('rt', $rtFormatted);
                
                if ($existing) {
                    // RT sudah ada, tambahkan datanya
                    $existing->warga += (int) $row['warga'];
                    $existing->kk_count += (int) $row['kk_count'];
                } else {
                    // RT belum ada, tambahkan sebagai data baru
                    $mergedData->push((object) [
                        'rt' => $rtFormatted,
                        'warga' => (int) $row['warga'],
                        'kk_count' => (int) $row['kk_count'],
                    ]);
                }
            }

            // Sort berdasarkan RT
            $result = $mergedData->sortBy('rt')->values();
            
            \Log::info('=== MERGED RESULT ===', ['data' => $result->toArray()]);

            return $result;

        } catch (\Exception $e) {
            \Log::error('Error getPerRtCountsData: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return collect();
        }
    }

    /**
     * ====================================================================
     * Ambil data RT dari DATABASE LOKAL
     * ====================================================================
     */
    private function getLocalRTData($rwId, $nomorRw)
    {
        try {
            // Normalisasi nomor RW untuk pencarian yang lebih fleksibel
            $normalizedRw = ltrim($nomorRw, '0'); // Hilangkan leading zero
            
            $query = Keluarga::where(function ($q) use ($rwId, $nomorRw, $normalizedRw) {
                // Cari berdasarkan rw_id
                $q->where('rw_id', $rwId)
                  // ATAU berdasarkan kolom rw (berbagai format)
                  ->orWhere('rw', $nomorRw)
                  ->orWhere('rw', $normalizedRw)
                  ->orWhere('rw', str_pad($nomorRw, 3, '0', STR_PAD_LEFT));
            })
            ->whereNotNull('rt')
            ->where('rt', '!=', '')
            ->select(
                'rt', 
                DB::raw('COALESCE(SUM(total_anggota), 0) as warga'), 
                DB::raw('COUNT(*) as kk_count')
            )
            ->groupBy('rt')
            ->orderBy('rt');

            $result = $query->get();
            
            \Log::info('Local DB Query Result:', [
                'count' => $result->count(),
                'data' => $result->toArray()
            ]);

            return $result;

        } catch (\Exception $e) {
            \Log::error('Error getLocalRTData: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * ====================================================================
     * Ambil data RT dari APPS SCRIPT (Google Sheets)
     * ====================================================================
     */
    private function getAppsScriptRTData($nomorRw)
    {
        try {
            $url = config('services.appscript.url');
            if (!$url) {
                \Log::info('Apps Script URL not configured');
                return [];
            }

            $response = Http::timeout(30)->get($url, ['action' => 'list']);

            if (!$response->successful()) {
                \Log::warning('Apps Script request failed', ['status' => $response->status()]);
                return [];
            }

            $body = $response->json();

            // Parse response
            if (isset($body['success']) && $body['success'] && isset($body['data'])) {
                $rows = $body['data'];
            } elseif (is_array($body)) {
                $rows = $body;
            } else {
                return [];
            }

            if (isset($rows['value']) && is_array($rows['value'])) {
                $rows = $rows['value'];
            }

            $normalizeKey = function($k) {
                return preg_replace('/[^a-z0-9]/', '', strtolower($k));
            };

            // Array untuk grouping per RT
            $rtGroups = [];
            $seenNomorKK = []; // Track unique KK

            foreach ($rows as $row) {
                if (!is_array($row)) continue;

                // === CARI KOLOM RW ===
                $foundRw = null;
                foreach ($row as $k => $v) {
                    $nk = $normalizeKey($k);
                    if (in_array($nk, ['rw','nomorrw','rwnumber','rtrw','rwrw'])) {
                        $foundRw = trim((string)$v);
                        break;
                    }
                }

                if ($foundRw === null) continue;

                // Normalisasi comparison
                $cleanRowRw = ltrim(str_ireplace(['rw','RW'], '', $foundRw), " 0");
                $cleanNomor = ltrim(str_ireplace(['rw','RW'], '', $nomorRw), " 0");

                // Handle format RT/RW
                if (strpos($foundRw, '/') !== false) {
                    $parts = preg_split('/\\s*\\/\\s*/', $foundRw);
                    $maybeRw = end($parts);
                    $cleanRowRw = ltrim(str_ireplace(['rw','RW'], '', $maybeRw), " 0");
                }

                // Check jika RW cocok
                if ($cleanRowRw !== $cleanNomor && $foundRw != $nomorRw) {
                    continue;
                }

                // === CARI KOLOM RT ===
                $rt = null;
                foreach ($row as $k => $v) {
                    $nk = $normalizeKey($k);
                    if ($nk === 'rt' || $nk === 'rtrw') {
                        $val = trim((string)$v);
                        if (strpos($val, '/') !== false) {
                            $parts = preg_split('/\\s*\\/\\s*/', $val);
                            $val = $parts[0] ?? $val;
                        }
                        $rt = ltrim($val, '0') ?: '0'; // Remove leading zeros
                        break;
                    }
                }

                if ($rt === null || $rt === '') continue;

                // === CARI NOMOR KK ===
                $nomorKK = null;
                foreach ($row as $k => $v) {
                    $nk = $normalizeKey($k);
                    if (in_array($nk, ['nomorkk','nomork','nokk','kk'])) {
                        $nomorKK = trim((string)$v);
                        break;
                    }
                }

                // === CARI TOTAL ANGGOTA ===
                $foundJiwa = 0;
                foreach ($row as $k => $v) {
                    $nk = $normalizeKey($k);
                    if (strpos($nk, 'totalanggota') !== false || 
                        strpos($nk, 'jumlahanggota') !== false || 
                        $nk === 'anggota' ||
                        $nk === 'jumlahjiwa') {
                        $foundJiwa = (int) filter_var($v, FILTER_SANITIZE_NUMBER_INT);
                        break;
                    }
                }

                // === GROUPING PER RT ===
                if (!isset($rtGroups[$rt])) {
                    $rtGroups[$rt] = [
                        'rt' => $rt,
                        'warga' => 0,
                        'kk_count' => 0
                    ];
                }

                // Tambahkan warga
                $rtGroups[$rt]['warga'] += $foundJiwa;

                // Tambahkan KK hanya jika belum dihitung (untuk menghindari duplikasi)
                if ($nomorKK && !in_array($nomorKK, $seenNomorKK)) {
                    $rtGroups[$rt]['kk_count']++;
                    $seenNomorKK[] = $nomorKK;
                }
            }

            // Sort by RT dan convert ke array
            ksort($rtGroups, SORT_NATURAL);
            $result = array_values($rtGroups);
            
            \Log::info('Apps Script RT Data:', ['count' => count($result), 'data' => $result]);

            return $result;

        } catch (\Exception $e) {
            \Log::error('Error getAppsScriptRTData: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * ====================================================================
     * API ENDPOINT - Dashboard Stats untuk AJAX Auto-refresh
     * ====================================================================
     */
    public function getDashboardStats(Request $request)
    {
        try {
            $user = auth()->user();
            
            if ($user->role !== 'rw') {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized'
                ], 403);
            }
            
            $rw = Rw::where('userid', $user->id)->first();

            if (!$rw) {
                return response()->json([
                    'success' => false,
                    'error' => 'Data RW tidak ditemukan'
                ]);
            }

            $nomorRw = $rw->nomor_rw;
            $rwId = $rw->id;

            // Coba dari Apps Script dulu
            $appsStats = $this->calculateRwStatsFromAppsScript($nomorRw);

            if ($appsStats['success']) {
                $perRtCounts = $this->getPerRtCountsData($rwId, $nomorRw);
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'totalKK' => $appsStats['totalKK'],
                        'totalJiwa' => $appsStats['totalJiwa'],
                        'totalRT' => $appsStats['totalRT'],
                        'rataAnggota' => $appsStats['rataAnggota'],
                        'nomorRw' => $nomorRw,
                        'perRtCounts' => $perRtCounts,
                        'updated_at' => now()->format('H:i:s')
                    ]
                ]);
            }

            // Fallback ke DB
            $stats = $this->calculateRwStatsSimple($rwId, $nomorRw);
            $perRtCounts = $this->getPerRtCountsData($rwId, $nomorRw);

            return response()->json([
                'success' => $stats['success'],
                'data' => [
                    'totalKK' => $stats['totalKK'],
                    'totalJiwa' => $stats['totalJiwa'],
                    'totalRT' => $stats['totalRT'],
                    'rataAnggota' => $stats['rataAnggota'],
                    'nomorRw' => $nomorRw,
                    'perRtCounts' => $perRtCounts,
                    'updated_at' => now()->format('H:i:s')
                ],
                'error' => $stats['error'] ?? null
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error getDashboardStats: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Terjadi kesalahan saat mengambil data'
            ], 500);
        }
    }
    
    // VERSI SIMPLE - Hitung statistik
    private function calculateRwStatsSimple($rwId, $nomorRw)
    {
        try {
            $normalizedRw = ltrim($nomorRw, '0');
            
            // Coba dengan berbagai format RW
            $totalKK = Keluarga::where(function($q) use ($rwId, $nomorRw, $normalizedRw) {
                $q->where('rw_id', $rwId)
                  ->orWhere('rw', $nomorRw)
                  ->orWhere('rw', $normalizedRw);
            })->count();
            
            $totalJiwa = Keluarga::where(function($q) use ($rwId, $nomorRw, $normalizedRw) {
                $q->where('rw_id', $rwId)
                  ->orWhere('rw', $nomorRw)
                  ->orWhere('rw', $normalizedRw);
            })->sum('total_anggota') ?? 0;
            
            // Hitung RT
            $totalRT = Keluarga::where(function($q) use ($rwId, $nomorRw, $normalizedRw) {
                $q->where('rw_id', $rwId)
                  ->orWhere('rw', $nomorRw)
                  ->orWhere('rw', $normalizedRw);
            })
            ->whereNotNull('rt')
            ->where('rt', '!=', '')
            ->distinct('rt')
            ->count('rt');
            
            // Hitung rata-rata
            $rataAnggota = $totalKK > 0 ? round($totalJiwa / $totalKK, 1) : 0;
            
            return [
                'totalKK' => $totalKK,
                'totalJiwa' => (int) $totalJiwa,
                'totalRT' => $totalRT,
                'rataAnggota' => $rataAnggota,
                'success' => true
            ];
            
        } catch (\Exception $e) {
            \Log::error('Error calculateRwStatsSimple: ' . $e->getMessage());
            
            return [
                'totalKK' => 0,
                'totalJiwa' => 0,
                'totalRT' => 0,
                'rataAnggota' => 0,
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function getKeluargaTerbaruSimple($rwId, $nomorRw, $limit = 10)
    {
        try {
            $normalizedRw = ltrim($nomorRw, '0');
            
            $data = Keluarga::where(function($q) use ($rwId, $nomorRw, $normalizedRw) {
                $q->where('rw_id', $rwId)
                  ->orWhere('rw', $nomorRw)
                  ->orWhere('rw', $normalizedRw);
            })
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get(['id', 'nomor_kk', 'kepala_keluarga', 'rt', 'rw', 'total_anggota', 'created_at']);
            
            return $data;
            
        } catch (\Exception $e) {
            \Log::error('Error getKeluargaTerbaruSimple: ' . $e->getMessage());
            return collect();
        }
    }

    // Hitung statistik dari Apps Script (Google Sheets)
    private function calculateRwStatsFromAppsScript($nomorRw)
    {
        try {
            $url = config('services.appscript.url');
            if (!$url) {
                return ['success' => false, 'error' => 'Apps Script URL not configured'];
            }

            $response = Http::timeout(30)->get($url, ['action' => 'list']);

            if (!$response->successful()) {
                return ['success' => false, 'error' => 'Apps Script request failed'];
            }

            $body = $response->json();

            if (isset($body['success']) && $body['success'] && isset($body['data'])) {
                $rows = $body['data'];
            } elseif (is_array($body)) {
                $rows = $body;
            } else {
                return ['success' => false, 'error' => 'Unexpected Apps Script response'];
            }

            $totalKK = 0;
            $totalJiwa = 0;
            $rts = [];
            $latest = [];

            $normalizeKey = function($k) {
                return preg_replace('/[^a-z0-9]/', '', strtolower($k));
            };

            if (isset($rows['value']) && is_array($rows['value'])) {
                $rows = $rows['value'];
            }

            foreach ($rows as $row) {
                if (!is_array($row)) continue;

                $foundRw = null;
                foreach ($row as $k => $v) {
                    $nk = $normalizeKey($k);
                    if (in_array($nk, ['rw','nomorrw','rwnumber','rtrw','rwrw'])) {
                        $foundRw = trim((string)$v);
                        break;
                    }
                }

                if ($foundRw === null) continue;

                $cleanRowRw = ltrim(str_ireplace(['rw','RW'], '', $foundRw), " 0");
                $cleanNomor = ltrim(str_ireplace(['rw','RW'], '', $nomorRw), " 0");

                if (strpos($foundRw, '/') !== false) {
                    $parts = preg_split('/\\s*\\/\\s*/', $foundRw);
                    $maybeRw = end($parts);
                    $cleanRowRw = ltrim(str_ireplace(['rw','RW'], '', $maybeRw), " 0");
                }

                if ($cleanRowRw === $cleanNomor || $foundRw == $nomorRw) {
                    $totalKK++;

                    $foundJiwa = 0;
                    foreach ($row as $k => $v) {
                        $nk = $normalizeKey($k);
                        if (strpos($nk, 'totalanggota') !== false || strpos($nk, 'jumlahanggota') !== false || $nk === 'anggota') {
                            $foundJiwa = (int) filter_var($v, FILTER_SANITIZE_NUMBER_INT);
                            break;
                        }
                    }

                    $totalJiwa += $foundJiwa;

                    foreach ($row as $k => $v) {
                        $nk = $normalizeKey($k);
                        if ($nk === 'rt' || $nk === 'rtrw') {
                            $val = trim((string)$v);
                            if (strpos($val, '/') !== false) {
                                $parts = preg_split('/\\s*\\/\\s*/', $val);
                                $val = $parts[0] ?? $val;
                            }
                            $rts[] = $val;
                            break;
                        }
                    }

                    $latest[] = $row;
                }
            }

            $uniqueRts = collect($rts)->filter()->unique()->values()->count();
            $rata = $totalKK > 0 ? round($totalJiwa / $totalKK, 1) : 0;

            $mappedLatest = [];
            foreach (array_slice($latest, 0, 10) as $row) {
                if (!is_array($row)) continue;

                $map = [];
                foreach ($row as $k => $v) {
                    $nk = preg_replace('/[^a-z0-9]/', '', strtolower($k));
                    $map[$nk] = $v;
                }

                $nomor_kk = $map['nomorkk'] ?? $map['nomork'] ?? '';
                $kepala = $map['namalengkapkepalakeluarga'] ?? $map['namalengkapkepala'] ?? $map['kepalakeluarga'] ?? '';

                $total = 0;
                foreach (['totalanggota', 'jumlahanggota', 'totalanggotakeluarga'] as $tk) {
                    if (isset($map[$tk])) {
                        $total = (int) filter_var($map[$tk], FILTER_SANITIZE_NUMBER_INT);
                        break;
                    }
                }

                $rt = $map['rt'] ?? null;
                $rwVal = $map['rw'] ?? null;
                if (isset($map['rtrw'])) {
                    $parts = preg_split('/\\s*\\/\\s*/', $map['rtrw']);
                    $rt = $parts[0] ?? $rt;
                    $rwVal = $parts[1] ?? $rwVal;
                }

                $created_at = isset($map['timestamp']) ? Carbon::parse($map['timestamp']) : now();

                $mappedLatest[] = (object) [
                    'id' => null,
                    'nomor_kk' => $nomor_kk,
                    'kepala_keluarga' => $kepala,
                    'rt' => $rt,
                    'rw' => $rwVal,
                    'total_anggota' => $total,
                    'created_at' => $created_at,
                ];
            }

            return [
                'success' => true,
                'totalKK' => $totalKK,
                'totalJiwa' => $totalJiwa,
                'totalRT' => $uniqueRts,
                'rataAnggota' => $rata,
                'latest' => $mappedLatest
            ];

        } catch (\Exception $e) {
            \Log::error('Error calculateRwStatsFromAppsScript: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // ============ CRUD RW METHODS ============
    public function index()
    {
        return view('rw.index', [
            'rw' => Rw::with('user')->get()
        ]);
    }

    public function create()
    {
        return view('rw.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required',
            'nomor_rw' => 'required',
            'username' => 'required|unique:users,username',
            'password' => 'required|min:6',
        ]);

        $user = User::create([
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role' => 'rw',
        ]);

        Rw::create([
            'nomor_rw' => $request->nomor_rw,
            'nama' => $request->nama,
            'userid' => $user->id,
        ]);

        return redirect()->route('rw.index')->with('success', 'RW berhasil ditambahkan');
    }

    public function edit(Rw $rw)
    {
        return view('rw.edit', compact('rw'));
    }

    public function update(Request $request, Rw $rw)
    {
        $request->validate([
            'nama' => 'required',
        ]);

        $rw->update([
            'nama' => $request->nama,
        ]);

        return redirect()->route('rw.index')->with('success', 'RW berhasil diupdate');
    }

    public function destroy(Rw $rw)
    {
        $rw->user()->delete();
        return back()->with('success', 'RW berhasil dihapus');
    }

    /**
     * ====================================================================
     * FIX TOTAL ANGGOTA - Update kolom total_anggota dari tabel warga
     * ====================================================================
     */
    public function fixTotalAnggota()
    {
        try {
            $user = auth()->user();
            
            if ($user->role !== 'admin') {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            
            \Log::info('Starting fixTotalAnggota...');
            
            $fixed = 0;
            $keluargaList = Keluarga::all();
            
            foreach ($keluargaList as $keluarga) {
                $actualCount = DB::table('warga')
                    ->where('keluarga_id', $keluarga->id)
                    ->count();
                
                if ($keluarga->total_anggota != $actualCount) {
                    $keluarga->update(['total_anggota' => $actualCount]);
                    $fixed++;
                    
                    \Log::info("Fixed KK {$keluarga->nomor_kk}: {$keluarga->total_anggota} -> {$actualCount}");
                }
            }
            
            \Log::info("fixTotalAnggota completed. Fixed: {$fixed}");
            
            return response()->json([
                'success' => true,
                'message' => "Berhasil memperbaiki {$fixed} data dari total {$keluargaList->count()} keluarga",
                'fixed' => $fixed,
                'total_checked' => $keluargaList->count()
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error fixTotalAnggota: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

     /**
     * ====================================================================
     * GET RT DETAIL - Ambil detail keluarga di RT tertentu
     * ====================================================================
     */
    public function getRtDetail($rtNumber)
    {
        try {
            $user = auth()->user();
            
            if ($user->role !== 'rw') {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized'
                ], 403);
            }
            
            $rw = Rw::where('userid', $user->id)->first();
            
            if (!$rw) {
                return response()->json([
                    'success' => false,
                    'error' => 'Data RW tidak ditemukan'
                ]);
            }

            $nomorRw = $rw->nomor_rw;
            $rwId = $rw->id;
            $normalizedRw = ltrim($nomorRw, '0');

            // Ambil data keluarga di RT tertentu
            $keluargaList = Keluarga::where(function($q) use ($rwId, $nomorRw, $normalizedRw) {
                $q->where('rw_id', $rwId)
                  ->orWhere('rw', $nomorRw)
                  ->orWhere('rw', $normalizedRw);
            })
            ->where('rt', $rtNumber)
            ->orderBy('created_at', 'desc')
            ->get(['id', 'nomor_kk', 'kepala_keluarga', 'rt', 'rw', 'total_anggota', 'alamat', 'created_at']);

            $totalKK = $keluargaList->count();
            
            // Hitung total warga dari total_anggota atau dari tabel warga
            $totalWarga = $keluargaList->sum('total_anggota');
            
            if ($totalWarga == 0) {
                $totalWarga = DB::table('warga')
                    ->join('keluarga', 'warga.keluarga_id', '=', 'keluarga.id')
                    ->where(function($q) use ($rwId, $nomorRw, $normalizedRw) {
                        $q->where('keluarga.rw_id', $rwId)
                          ->orWhere('keluarga.rw', $nomorRw)
                          ->orWhere('keluarga.rw', $normalizedRw);
                    })
                    ->where('keluarga.rt', $rtNumber)
                    ->count();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'rt' => $rtNumber,
                    'rw' => $nomorRw,
                    'totalKK' => $totalKK,
                    'totalWarga' => $totalWarga,
                    'keluarga' => $keluargaList
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error getRtDetail: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}
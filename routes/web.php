<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RwController;
use App\Http\Controllers\KeluargaController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\KeluargaAppscriptController;
use App\Http\Controllers\ApiKeluarga;
use App\Http\Controllers\GoogleSheetsController;
use App\Http\Controllers\PasswordController;

// ============ PUBLIC ROUTES ============
Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ============ PROTECTED ROUTES (AUTH REQUIRED) ============
Route::middleware('auth')->group(function () {
    // Tambahkan di dalam group middleware('auth'):
    Route::get('/rw/per-rt-counts', [RwController::class, 'getPerRtCounts'])->name('rw.per.rt.counts');

// Route khusus untuk data keluarga berdasarkan RW
Route::get('/rw/keluarga', [RwController::class, 'dataKeluarga'])->name('rw.keluarga');

// Atau gunakan route resource yang sudah ada dengan filter
Route::get('/keluarga/rw', [KeluargaController::class, 'indexByRw'])->name('keluarga.rw');
    
    // ============ DASHBOARD ROUTES ============
    Route::get('/admin', fn() => view('dashboard'))->name('admin.dashboard');
    
    // ✅ PERBAIKAN: Hapus closure dan gunakan controller method untuk RW Dashboard
    // Route ini akan di-handle oleh RwController::dashboard()
    Route::get('/rw-dashboard', [RwController::class, 'dashboard'])->name('rw.dashboard');
    
    Route::post('/keluarga', [KeluargaController::class, 'store'])->name('keluarga.store');
    // ============ API FOR REAL-TIME DATA ============
    Route::get('/api/rw-dashboard/stats', [RwController::class, 'getDashboardStats'])->name('rw.dashboard.stats');
    
    // ============ RESOURCE ROUTES ============
    Route::resource('rw', RwController::class);
    Route::resource('keluarga', KeluargaController::class);
    Route::resource('users', UserController::class);
    
    // ============ APPSCRIPT ROUTES ============
    Route::prefix('keluarga-appscript')->group(function () {
        Route::get('/', [KeluargaAppscriptController::class, 'index'])->name('keluarga-appscript.index');
        Route::post('/', [KeluargaAppscriptController::class, 'store'])->name('keluarga-appscript.create');
        Route::put('/{nomor_kk}', [KeluargaAppscriptController::class, 'update']);
        Route::delete('/{nomor_kk}', [KeluargaAppscriptController::class, 'destroy']);
    });
    
    Route::prefix('api-keluarga-appscript')->group(function () {
        Route::get('/', [ApiKeluarga::class, 'index'])->name('api-keluarga-appscript.index');
        Route::post('/', [ApiKeluarga::class, 'store'])->name('api-keluarga-appscript.create');
        Route::put('/{nomor_kk}', [ApiKeluarga::class, 'update']);
        Route::delete('/{nomor_kk}', [ApiKeluarga::class, 'destroy']);
    });
    
    // ============ GOOGLE SHEETS ROUTES ============
    Route::prefix('google-sheets')->group(function () {
        Route::get('/', [GoogleSheetsController::class, 'index'])->name('google-sheets.index');
        Route::get('/data', [GoogleSheetsController::class, 'getData'])->name('google-sheets.data');
        Route::get('/test', [GoogleSheetsController::class, 'testConnection'])->name('google-sheets.test');
        Route::delete('/delete-by-nomor-kk/{nomorKK}', [GoogleSheetsController::class, 'deleteByNomorKK'])->name('google-sheets.delete-by-nomor-kk');
        Route::delete('/delete-by-row/{rowId}', [GoogleSheetsController::class, 'deleteByRowId'])->name('google-sheets.delete-by-row');
    });
    
    // ============ PASSWORD CHANGE ROUTES ============
    Route::get('/change-password', [PasswordController::class, 'showChangeForm'])
        ->name('password.change');
    Route::post('/change-password', [PasswordController::class, 'changePassword'])
        ->name('password.update');
    
    // ============ DEBUG ROUTE (HAPUS DI PRODUCTION) ============
    Route::get('/debug-rw', function() {
        $user = auth()->user();
        $rw = App\Models\Rw::where('userid', $user->id)->first();
        
        if (!$rw) {
            return "User tidak memiliki data RW!";
        }
        
        // Hitung data secara manual
        $totalKK = App\Models\Keluarga::where('rw_id', $rw->id)
            ->orWhere('rw', $rw->nomor_rw)
            ->count();
            
        $totalJiwa = App\Models\Keluarga::where('rw_id', $rw->id)
            ->orWhere('rw', $rw->nomor_rw)
            ->sum('total_anggota');
            
        $totalRT = App\Models\Keluarga::where('rw_id', $rw->id)
            ->orWhere('rw', $rw->nomor_rw)
            ->whereNotNull('rt')
            ->select('rt')
            ->distinct()
            ->count('rt');
        
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
                'rw_id' => $user->rw_id ?? 'null'
            ],
            'rw_data' => $rw ? $rw->toArray() : null,
            'stats' => [
                'totalKK' => $totalKK,
                'totalJiwa' => $totalJiwa,
                'totalRT' => $totalRT,
                'rataAnggota' => $totalKK > 0 ? round($totalJiwa / $totalKK, 1) : 0
            ],
            'keluarga_count' => App\Models\Keluarga::count(),
            'keluarga_in_rw' => App\Models\Keluarga::where('rw_id', $rw->id)
                ->orWhere('rw', $rw->nomor_rw)
                ->get(['id', 'nomor_kk', 'kepala_keluarga', 'rt', 'rw', 'rw_id'])
        ]);
    })->name('debug.rw');
    
    // ============ REDIRECT BASED ON ROLE ============
    Route::get('/dashboard', function () {
        $user = auth()->user();
        
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        } elseif ($user->role === 'rw') {
            return redirect()->route('rw.dashboard');
        }
        
        return redirect()->route('login');
    })->name('dashboard');
});


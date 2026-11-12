<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class KeluargaAppscriptController extends Controller
{
    private $webAppUrl;
    
    public function __construct()
    {
        $this->webAppUrl = config('services.appscript.url');
    }
    
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('keluarga-appscript.index');
    }
    
    /**
     * Get all data from Google Sheets
     */
    public function apiIndex(\Illuminate\Http\Request $request)
    {
        try {
            $url = $this->webAppUrl;
            if (!$url) {
                Log::warning('[AppscriptProxy] No URL configured');
                return response()->json(['error' => 'Apps Script URL not configured'], 400);
            }

            $ttl = intval(env('APPSCRIPT_CACHE_TTL', 15)); // seconds
            $cacheKey = 'appscript:' . md5($url);

            if (Cache::has($cacheKey)) {
                $cached = Cache::get($cacheKey);
                Log::info('[AppscriptProxy] Cache hit (items=' . (is_array($cached) ? count($cached) : 0) . ')');
                return response()->json($cached);
            }

            Log::info('[AppscriptProxy] Cache miss. Fetching configured endpoint');
            $response = Http::timeout(30)->get($url, [
                'action' => 'list'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                // Normalize response shapes
                if (is_array($data)) {
                    Cache::put($cacheKey, $data, $ttl);
                    Log::info('[AppscriptProxy] Fetched and cached data (count=' . count($data) . ', ttl=' . $ttl . 's)');
                    return response()->json($data);
                }

                if (isset($data['success']) && $data['success']) {
                    $payload = $data['data'] ?? [];
                    Cache::put($cacheKey, $payload, $ttl);
                    Log::info('[AppscriptProxy] Fetched and cached wrapped data (count=' . (is_array($payload) ? count($payload) : 0) . ', ttl=' . $ttl . 's)');
                    return response()->json($payload);
                }

                Log::warning('[AppscriptProxy] Unexpected Apps Script response shape', ['status' => $response->status(), 'body' => $response->body()]);
                return response()->json([]);
            }

            Log::error('[AppscriptProxy] Failed to fetch data from Apps Script. HTTP status: ' . $response->status());
            return response()->json([], 500);

        } catch (\Exception $e) {
            Log::error('[AppscriptProxy] Exception when fetching data: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Remove the specified resource from storage.
     */
    public function destroy($nomorKK)
    {
        try {
            $nomorKK = trim((string) $nomorKK);
            Log::info('Request to delete Nomor KK: ' . $nomorKK);

            if ($nomorKK === '' || strlen(preg_replace('/\D/', '', $nomorKK)) < 4) {
                Log::warning('Refusing to delete: invalid or empty Nomor KK: ' . $nomorKK);
                return response()->json([
                    'success' => false,
                    'message' => 'Nomor KK tidak valid atau kosong. Operasi dibatalkan.'
                ], 400);
            }

            // Panggil Apps Script untuk menghapus data (kirim kedua format param untuk kompatibilitas)
            $response = Http::timeout(30)->get($this->webAppUrl, [
                'action' => 'delete',
                'nomorKK' => $nomorKK,
                'nomor_kk' => $nomorKK
            ]);
            
            $result = $response->json();
            
            if (isset($result['success']) && $result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Gagal menghapus data'
                ], 400);
            }
            
        } catch (\Exception $e) {
            Log::error('Error deleting data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Soft delete - hanya clear content
     */
    public function softDelete($nomorKK)
    {
        try {
            $nomorKK = trim((string) $nomorKK);
            Log::info('Request to soft-delete Nomor KK: ' . $nomorKK);

            if ($nomorKK === '' || strlen(preg_replace('/\D/', '', $nomorKK)) < 4) {
                Log::warning('Refusing to soft-delete: invalid or empty Nomor KK: ' . $nomorKK);
                return response()->json([
                    'success' => false,
                    'message' => 'Nomor KK tidak valid atau kosong. Operasi dibatalkan.'
                ], 400);
            }

            $response = Http::timeout(30)->get($this->webAppUrl, [
                'action' => 'soft-delete',
                'nomorKK' => $nomorKK,
                'nomor_kk' => $nomorKK
            ]);
            
            $result = $response->json();
            
            if (isset($result['success']) && $result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Gagal menghapus data'
                ], 400);
            }
            
        } catch (\Exception $e) {
            Log::error('Error soft deleting: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Force delete - hapus baris
     */
    public function forceDelete($nomorKK)
    {
        try {
            $nomorKK = trim((string) $nomorKK);
            Log::info('Request to force-delete Nomor KK: ' . $nomorKK);

            if ($nomorKK === '' || strlen(preg_replace('/\D/', '', $nomorKK)) < 4) {
                Log::warning('Refusing to force-delete: invalid or empty Nomor KK: ' . $nomorKK);
                return response()->json([
                    'success' => false,
                    'message' => 'Nomor KK tidak valid atau kosong. Operasi dibatalkan.'
                ], 400);
            }

            $response = Http::timeout(30)->get($this->webAppUrl, [
                'action' => 'force-delete',
                'nomorKK' => $nomorKK,
                'nomor_kk' => $nomorKK
            ]);
            
            $result = $response->json();
            
            if (isset($result['success']) && $result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Gagal menghapus data'
                ], 400);
            }
            
        } catch (\Exception $e) {
            Log::error('Error force deleting: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Force refresh the cached Apps Script data (admin-only).
     */
    public function forceRefresh(\Illuminate\Http\Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user || !method_exists($user, 'isAdmin') || !$user->isAdmin()) {
                Log::warning('[AppscriptProxy] Unauthorized force refresh attempt', ['user' => $user ? $user->id : null]);
                return response()->json(['error' => 'Forbidden'], 403);
            }

            $url = $this->webAppUrl;
            if (!$url) {
                Log::warning('[AppscriptProxy] No URL configured for force refresh');
                return response()->json(['error' => 'Apps Script URL not configured'], 400);
            }

            $cacheKey = 'appscript:' . md5($url);
            Log::info('[AppscriptProxy] Force refresh requested by user ' . $user->id);

            // Clear existing cache
            Cache::forget($cacheKey);

            // Fetch fresh data
            $response = Http::timeout(30)->get($url, [
                'action' => 'list'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $payload = [];

                if (is_array($data)) {
                    $payload = $data;
                } elseif (isset($data['success']) && $data['success']) {
                    $payload = $data['data'] ?? [];
                } else {
                    Log::warning('[AppscriptProxy] Unexpected Apps Script response during force refresh', ['status' => $response->status(), 'body' => $response->body()]);
                    return response()->json(['error' => 'Unexpected response from Apps Script'], 500);
                }

                $ttl = intval(env('APPSCRIPT_CACHE_TTL', 15));
                Cache::put($cacheKey, $payload, $ttl);

                // store audit meta for this URL and append to history
                try {
                    $metaKey = 'appscript:force_refresh_meta:' . md5($url);
                    $historyKey = 'appscript:force_refresh_history:' . md5($url);
                    $meta = [
                        'user_id' => $user->id ?? null,
                        'user_name' => $user->name ?? null,
                        'time' => now()->toDateTimeString(),
                        'count' => is_array($payload) ? count($payload) : 0
                    ];

                    // cache single latest meta for 7 days
                    Cache::put($metaKey, $meta, 3600 * 24 * 7);

                    // append to history (keep latest first), limit to 50 entries
                    $history = Cache::get($historyKey, []);
                    array_unshift($history, $meta);
                    if (count($history) > 50) {
                        $history = array_slice($history, 0, 50);
                    }
                    Cache::put($historyKey, $history, 3600 * 24 * 30); // keep history for 30 days
                } catch (\Exception $e) {
                    Log::warning('[AppscriptProxy] Could not store force-refresh meta/history: ' . $e->getMessage());
                }

                Log::info('[AppscriptProxy] Force refreshed cache (count=' . (is_array($payload) ? count($payload) : 0) . ', ttl=' . $ttl . 's)');

                // include recent history in response if available
                $history = [];
                try {
                    $historyKey = 'appscript:force_refresh_history:' . md5($url);
                    $history = Cache::get($historyKey, []);
                } catch (\Exception $e) {
                    Log::debug('[AppscriptProxy] Could not read history for response: ' . $e->getMessage());
                }

                $responsePayload = [
                    'success' => true,
                    'cached' => is_array($payload) ? count($payload) : 0,
                    'data' => $payload,
                    'meta' => $meta ?? null,
                    'history' => $history
                ];

                return response()->json($responsePayload);
            }

            Log::error('[AppscriptProxy] Failed to fetch data during force refresh. HTTP status: ' . $response->status());
            return response()->json(['error' => 'Failed to fetch Apps Script data'], 500);

        } catch (\Exception $e) {
            Log::error('[AppscriptProxy] Exception during force refresh: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Return the last force-refresh metadata for the configured Apps Script URL.
     */
    public function getForceRefreshMeta(\Illuminate\Http\Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user || !method_exists($user, 'isAdmin') || !$user->isAdmin()) {
                Log::warning('[AppscriptProxy] Unauthorized meta request', ['user' => $user ? $user->id : null]);
                return response()->json(['error' => 'Forbidden'], 403);
            }

            $url = $this->webAppUrl;
            if (!$url) {
                return response()->json(['error' => 'Apps Script URL not configured'], 400);
            }

            $metaKey = 'appscript:force_refresh_meta:' . md5($url);
            $historyKey = 'appscript:force_refresh_history:' . md5($url);

            $meta = Cache::get($metaKey, null);
            $history = Cache::get($historyKey, []);
            return response()->json(['meta' => $meta, 'history' => $history]);
        } catch (\Exception $e) {
            Log::error('[AppscriptProxy] Exception getting force-refresh meta: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
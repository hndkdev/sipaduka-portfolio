<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GoogleSheetsController extends Controller
{
    // URL Web Apps Script
    private $webAppUrl;
    public function __construct()
    {
        $this->webAppUrl = config('services.appscript.url');
    }
    
    /**
     * Display a listing of the resource from Google Sheets.
     */
    public function index()
    {
        // Return view untuk menampilkan data dari Google Sheets
        return view('keluarga.google-sheets-index');
    }
    
    /**
     * API endpoint to get data from Google Sheets.
     */
    public function getData(Request $request)
    {
        try {
            $cacheKey = 'google_sheets_data';
            $cacheTime = 60; // Cache selama 1 menit
            
            // Jika ada parameter nocache, hapus cache
            if ($request->query('nocache')) {
                Cache::forget($cacheKey);
            }
            
            $data = Cache::remember($cacheKey, $cacheTime, function () {
                Log::info('Fetching data from Google Sheets via Apps Script');
                
                $response = Http::timeout(30)
                    ->withHeaders([
                        'Accept' => 'application/json',
                    ])
                    ->get($this->webAppUrl, [
                        'action' => 'list'
                    ]);
                
                if ($response->failed()) {
                    Log::error('Failed to fetch data from Apps Script: ' . $response->status());
                    throw new \Exception('Failed to fetch data: HTTP ' . $response->status());
                }
                
                $result = $response->json();
                
                // Cek jika response sukses dan ada data
                if (isset($result['success']) && $result['success'] && isset($result['data'])) {
                    Log::info('Data fetched successfully: ' . ($result['count'] ?? 0) . ' records');
                    return $result['data'];
                } else {
                    Log::warning('Invalid response format from Apps Script');
                    return [];
                }
            });
            
            return response()->json($data);
            
        } catch (\Exception $e) {
            Log::error('Error fetching data from Google Sheets: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Gagal mengambil data dari Google Sheets: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }
    
    /**
     * Delete data from Google Sheets by Nomor KK.
     */
    public function deleteByNomorKK(Request $request, $nomorKK)
    {
        try {
            Log::info('Attempting to delete data with Nomor KK: ' . $nomorKK);
            
            $response = Http::timeout(30)
                ->get($this->webAppUrl, [
                    'action' => 'delete',
                    'nomorKK' => $nomorKK
                ]);
            
            $result = $response->json();
            
            // Clear cache setelah delete
            Cache::forget('google_sheets_data');
            
            if (isset($result['success']) && $result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message']
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Gagal menghapus data'
            ], 400);
            
        } catch (\Exception $e) {
            Log::error('Delete error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Delete data from Google Sheets by Row ID.
     */
    public function deleteByRowId(Request $request, $rowId)
    {
        try {
            Log::info('Attempting to delete data with Row ID: ' . $rowId);
            
            $response = Http::timeout(30)
                ->get($this->webAppUrl, [
                    'action' => 'delete',
                    'rowId' => $rowId
                ]);
            
            $result = $response->json();
            
            // Clear cache setelah delete
            Cache::forget('google_sheets_data');
            
            if (isset($result['success']) && $result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message']
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Gagal menghapus data'
            ], 400);
            
        } catch (\Exception $e) {
            Log::error('Delete error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Test connection to Google Sheets.
     */
    public function testConnection()
    {
        try {
            Log::info('Testing connection to Google Sheets...');
            
            $response = Http::timeout(15)->get($this->webAppUrl, [
                'action' => 'list'
            ]);
            
            Log::info('Response status: ' . $response->status());
            
            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'success' => true,
                    'message' => 'Connection successful',
                    'data_count' => $data['count'] ?? 0
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'HTTP Error: ' . $response->status()
            ], 500);
            
        } catch (\Exception $e) {
            Log::error('Connection test failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Sync data from Google Sheets to local database (optional).
     */
    public function syncToLocal()
    {
        // Implementasi jika diperlukan untuk menyimpan data dari Google Sheets ke database lokal
        // Hati-hati dengan duplikasi data, pastikan ada pengecekan berdasarkan ID atau Nomor KK
    }
}
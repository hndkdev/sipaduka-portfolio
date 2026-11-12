<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class KeluargaController extends Controller
{
     protected string $endpoint;

    public function __construct()
    {
        $this->endpoint = config('services.appscript.url');
    }

    /**
     * CREATE (kirim data ke Apps Script)
     */
    public function store(Request $request)
    {
        $payload = [
            'action'  => 'create',
            'message' => $this->formatToWhatsappStyle($request->all()),
            'sender'  => auth()->user()->username ?? 'sipaduka-system',
        ];

        $response = Http::post($this->endpoint, $payload);

        return response()->json([
            'success' => true,
            'result'  => $response->body()
        ]);
    }

    /**
     * READ (ambil data spreadsheet)
     * ⚠️ butuh tambahan doGet di Apps Script
     */
   public function index()
    {
        $response = Http::get($this->endpoint, [
            'action' => 'list'
        ]);

        $data = $response->json();

        if (!is_array($data)) {
            return response()->json([]);
        }

        $user = auth()->user();

        // 🔐 ADMIN → lihat semua
        if ($user->role === 'admin') {
            return response()->json($data);
        }

        // 🔐 RW → filter berdasarkan RT/RW
        $rw = $user->rw->nomor_rw; // contoh: "002"

        $filtered = array_values(array_filter($data, function ($row) use ($rw) {
            if (!isset($row['RT/RW'])) return false;

            // format spreadsheet: 001/002
            $parts = explode('/', $row['RT/RW']);

            return isset($parts[1]) && trim($parts[1]) === $rw;
        }));

        return response()->json($filtered);
    }


    /**
     * UPDATE (berdasarkan Nomor KK)
     */
    public function update(Request $request, string $nomorKk)
    {
        $payload = [
            'action'   => 'update',
            'nomor_kk'=> $nomorKk,
            'data'     => $request->all()
        ];

        $response = Http::post($this->endpoint, $payload);

        return response()->json($response->json());
    }

    /**
     * DELETE (berdasarkan Nomor KK)
     */
    public function destroy(string $nomorKk)
    {
        $response = Http::post($this->endpoint, [
            'action'    => 'delete',
            'nomor_kk'  => $nomorKk
        ]);

        return response()->json($response->json());
    }

    /**
     * Ubah request Laravel → format WhatsApp (key: value)
     */
    private function formatToWhatsappStyle(array $data): string
    {
        $text = "";

        foreach ($data as $key => $value) {
            if ($value !== null && $value !== '') {
                $label = ucwords(str_replace('_', ' ', $key));
                $text .= "{$label}: {$value}\n";
            }
        }

        return trim($text);
    }
}

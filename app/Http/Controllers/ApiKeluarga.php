<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ApiKeluarga extends Controller
{
    protected string $endpoint;

    public function __construct()
    {
        $this->endpoint = config('services.appscript.url');
    }

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
    public function destroy(Request $request, string $nomorKk)
    {
        // Prevent accidental full-house deletion by validating nomorKk
        if (! $nomorKk || strlen($nomorKk) < 3) {
            return response()->json(['success' => false, 'message' => 'Nomor KK tidak valid'], 400);
        }

        $memberRow = $request->input('memberRow'); // optional full-row JSON object

        if ($memberRow) {
            $payload = [
                'action'    => 'delete_member',
                'nomor_kk'  => $nomorKk,
                'member_row'=> is_array($memberRow) ? $memberRow : json_decode($memberRow, true)
            ];

            $response = Http::post($this->endpoint, $payload);
            return response()->json($response->json());
        }

        // Fallback: original behavior — delete by nomorKK (may remove whole household)
        $response = Http::get($this->endpoint, [
            'action'    => 'delete',
            'nomorKK'   => $nomorKk
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

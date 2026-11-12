<?php

namespace App\Http\Controllers;

use App\Models\AnggotaKeluarga;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AnggotaKeluargaController extends Controller
{
    /**
     * Hapus satu anggota keluarga (mis. karena meninggal).
     * Hanya `admin` atau `rw` yang memiliki keluarga tersebut boleh menghapus.
     */
    public function destroy($id, Request $request)
    {
        try {
            $anggota = AnggotaKeluarga::findOrFail($id);
            $keluarga = $anggota->keluarga;

            // Authorization: admin can, rw only if keluarga belongs to their RW
            $user = auth()->user();
            if ($user->role !== 'admin') {
                if (!$user->rw) {
                    return $request->expectsJson()
                        ? response()->json(['success' => false, 'message' => 'User tidak terdaftar di RW'], 403)
                        : back()->with('error', 'User tidak terdaftar di RW');
                }

                $userRwId = $user->rw->id;
                $userRwNomor = $user->rw->nomor_rw;

                if (($keluarga->rw_id && $keluarga->rw_id != $userRwId) && ($keluarga->rw && $keluarga->rw != $userRwNomor)) {
                    return $request->expectsJson()
                        ? response()->json(['success' => false, 'message' => 'Tidak diizinkan'], 403)
                        : back()->with('error', 'Anda tidak memiliki izin untuk menghapus anggota keluarga ini');
                }
            }

            // Jangan hapus jika anggota adalah kepala keluarga — minta ubah kepala dulu
            if ($anggota->hubungan === 'kepala') {
                return $request->expectsJson()
                    ? response()->json(['success' => false, 'message' => 'Tidak boleh menghapus kepala keluarga. Ubah kepala keluarga terlebih dahulu.'], 400)
                    : back()->with('error', 'Tidak boleh menghapus kepala keluarga. Ubah kepala keluarga terlebih dahulu.');
            }

            // Hapus anggota
            $anggota->delete();

            // Update total_anggota pada tabel keluarga (safe decrement)
            if ($keluarga) {
                $keluarga->total_anggota = max(0, ($keluarga->total_anggota ?? 0) - 1);
                $keluarga->save();
            }

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => 'Anggota keluarga dihapus']);
            }

            return back()->with('success', 'Anggota keluarga berhasil dihapus');

        } catch (\Exception $e) {
            Log::error('Error deleting anggota keluarga: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Gagal menghapus anggota: ' . $e->getMessage()], 500);
            }

            return back()->with('error', 'Gagal menghapus anggota: ' . $e->getMessage());
        }
    }
}

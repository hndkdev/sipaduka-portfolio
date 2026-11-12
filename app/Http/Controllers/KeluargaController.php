<?php

namespace App\Http\Controllers;

use App\Models\Keluarga;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class KeluargaController extends Controller
{
    public function index()
    {
        try {
            if (auth()->user()->role === 'admin') {
                $keluarga = Keluarga::all();
            } else {
                // Cek apakah user punya relasi rw
                if (!auth()->user()->rw) {
                    return view('keluarga.index', [
                        'keluarga' => collect([]),
                        'error' => 'User belum terdaftar di RW manapun'
                    ]);
                }
                
                $rwId = auth()->user()->rw->id;
                $keluarga = Keluarga::where('rw_id', $rwId)->get();
            }
            
            return view('keluarga.index', compact('keluarga'));
            
        } catch (\Exception $e) {
            Log::error('Error loading keluarga: ' . $e->getMessage());
            
            return view('keluarga.index', [
                'keluarga' => collect([]),
                'error' => 'Gagal memuat data keluarga'
            ]);
        }
    }

    public function create()
    {
        return view('keluarga.create');
    }

    public function store(Request $request)
{
    $request->validate([
        'nomor_kk' => 'required|unique:keluarga',
        'alamat' => 'required',
        'rt' => 'required',
        'rw' => 'required',
        'kepala_keluarga' => 'required',
        'nik_kepala' => 'required',
    ]);

    try {
        // Cek apakah user punya relasi rw untuk non-admin
        if (auth()->user()->role !== 'admin' && !auth()->user()->rw) {
            return back()->with('error', 'User belum terdaftar di RW manapun');
        }

        $keluarga = Keluarga::create([
            'nomor_kk' => $request->nomor_kk,
            'alamat' => $request->alamat,
            'rt' => $request->rt,
            'rw' => $request->rw,
            'rw_id' => auth()->user()->role === 'admin' ? $request->rw_id : auth()->user()->rw->id,
            'kepala_keluarga' => $request->kepala_keluarga,
            'nik_kepala' => $request->nik_kepala,
            'nama_istri' => $request->nama_istri,
            'nik_istri' => $request->nik_istri,
            'total_anggota' => $request->total_anggota ?? 0,
            'total_pendapatan' => $request->total_pendapatan ?? 0,
            'data_tambahan' => $request->data_tambahan,
        ]);

        return redirect()->route('keluarga.index')->with('success', 'Data keluarga berhasil disimpan');

    } catch (\Exception $e) {
        \Log::error('Error storing keluarga: ' . $e->getMessage());

        return back()->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
    }
}

    public function edit(Keluarga $keluarga)
    {
        return view('keluarga.edit', compact('keluarga'));
    }

    public function update(Request $request, Keluarga $keluarga)
    {
        $request->validate([
            'alamat' => 'required',
            'kepala_keluarga' => 'required',
        ]);

        $keluarga->update($request->all());

        return redirect()->route('keluarga.index')->with('success', 'Data keluarga berhasil diperbarui');
    }

    public function destroy(Keluarga $keluarga)
    {
        $keluarga->delete();
        return back()->with('success', 'Data keluarga dihapus');
    }
    
    /**
     * Check if Nomor KK already exists
     */
    public function checkNomorKK(Request $request)
    {
        $exists = Keluarga::where('nomor_kk', $request->nomor_kk)->exists();
        
        return response()->json([
            'exists' => $exists,
            'message' => $exists ? 'Nomor KK sudah terdaftar' : 'Nomor KK tersedia'
        ]);
    }
    
}


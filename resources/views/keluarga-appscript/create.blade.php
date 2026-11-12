@extends('layout.app')

@section('title', 'Tambah Data Keluarga')

@section('content')
<link rel="icon" type="image/jpeg" href="{{ asset('images/logo.jpg') }}">
<div class="container">

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Tambah Data Keluarga</h5>
        </div>

        <div class="card-body">
            <form action="{{ route('keluarga-appscript.store') }}" method="POST">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nomor KK</label>
                        <input type="text" name="nomor_kk" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Alamat Sesuai KK</label>
                        <input type="text" name="alamat_sesuai_kk" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">RT / RW</label>
                        <input type="text" name="rt_rw" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Nama Kepala Keluarga</label>
                        <input type="text" name="nama_lengkap_kepala_keluarga" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">NIK Kepala Keluarga</label>
                        <input type="text" name="nik_kepala_keluarga" class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Total Pendapatan Keluarga</label>
                        <input type="number" name="total_pendapatan_keluarga" class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Total Anggota Keluarga</label>
                        <input type="number" name="total_anggota_keluarga" class="form-control">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Data Tambahan</label>
                        <textarea name="data_tambahan" rows="3" class="form-control"></textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex justify-content-between">
                    <a href="{{ route('keluarga-appscript.index') }}" class="btn btn-secondary">
                        Kembali
                    </a>
                    <button class="btn btn-primary">
                        <i class="bi bi-save"></i> Simpan
                    </button>
                </div>

            </form>
        </div>
    </div>

</div>
@endsection

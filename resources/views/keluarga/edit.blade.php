@extends('layout.app')

@section('title', 'Edit Keluarga')
<link rel="icon" type="image/jpeg" href="{{ asset('images/logo.jpg') }}">
@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header">
            <h5>Edit Data Keluarga</h5>
        </div>

        <div class="card-body">
            <form action="{{ route('keluarga.update', $keluarga->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Nama Kepala Keluarga</label>
                    <input type="text" name="kepala_keluarga"
                           class="form-control @error('kepala_keluarga') is-invalid @enderror"
                           value="{{ old('kepala_keluarga', $keluarga->kepala_keluarga) }}">
                    @error('kepala_keluarga')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Alamat</label>
                    <textarea name="alamat"
                              class="form-control @error('alamat') is-invalid @enderror"
                              rows="2">{{ old('alamat', $keluarga->alamat) }}</textarea>
                    @error('alamat')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">RT</label>
                        <input type="text" name="rt"
                               class="form-control"
                               value="{{ old('rt', $keluarga->rt) }}">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">RW</label>
                        <input type="text" name="rw"
                               class="form-control"
                               value="{{ old('rw', $keluarga->rw) }}">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Total Anggota</label>
                        <input type="number" name="total_anggota"
                               class="form-control"
                               value="{{ old('total_anggota', $keluarga->total_anggota) }}">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Total Pendapatan</label>
                        <input type="number" name="total_pendapatan"
                               class="form-control"
                               value="{{ old('total_pendapatan', $keluarga->total_pendapatan) }}">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Data Tambahan</label>
                    <textarea name="data_tambahan"
                              class="form-control"
                              rows="2">{{ old('data_tambahan', $keluarga->data_tambahan) }}</textarea>
                </div>

                <div class="d-flex justify-content-end">
                    <a href="{{ route('keluarga.index') }}" class="btn btn-secondary me-2">
                        Kembali
                    </a>
                    <button class="btn btn-primary">
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

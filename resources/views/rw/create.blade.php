@extends('layout.app')

@section('title', 'Tambah RW')

@section('content')
<link rel="icon" type="image/jpeg" href="{{ asset('images/logo.jpg') }}">
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header">
            <h5>Tambah RW</h5>
        </div>

        <div class="card-body">
            <form action="{{ route('rw.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Nama RW</label>
                    <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror"
                           value="{{ old('nama') }}">
                    @error('nama')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                <div class="mb-3">
                    <label class="form-label">Nomor Rw</label>
                    <input type="text" name="nomor_rw" class="form-control @error('nomor_rw') is-invalid @enderror"
                           value="{{ old('nomor_rw') }}">
                    @error('nomor_rw')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control @error('username') is-invalid @enderror"
                           value="{{ old('username') }}">
                    @error('username')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password"
                           class="form-control @error('password') is-invalid @enderror">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-end">
                    <a href="{{ route('rw.index') }}" class="btn btn-secondary me-2">Kembali</a>
                    <button class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

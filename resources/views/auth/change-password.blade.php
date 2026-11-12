@extends('layout.app')

@section('title', 'Ganti Password')
<link rel="icon" type="image/jpeg" href="{{ asset('images/logo.jpg') }}">
@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            {{-- Header --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Ganti Password</h2>
                    <p class="text-muted mb-0">Ubah password akun Anda</p>
                </div>
            </div>

            {{-- Success Message --}}
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- Error Messages --}}
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Terjadi kesalahan!</strong>
                    <ul class="mb-0 mt-2">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- Form Card --}}
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form action="{{ route('password.update') }}" method="POST">
                        @csrf

                        {{-- Current Password --}}
                        <div class="mb-4">
                            <label for="current_password" class="form-label fw-semibold">
                                <i class="fas fa-lock text-primary me-2"></i>
                                Password Lama
                            </label>
                            <input 
                                type="password" 
                                class="form-control @error('current_password') is-invalid @enderror" 
                                id="current_password" 
                                name="current_password"
                                placeholder="Masukkan password lama"
                                required
                                autofocus
                            >
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- New Password --}}
                        <div class="mb-4">
                            <label for="new_password" class="form-label fw-semibold">
                                <i class="fas fa-key text-success me-2"></i>
                                Password Baru
                            </label>
                            <input 
                                type="password" 
                                class="form-control @error('new_password') is-invalid @enderror" 
                                id="new_password" 
                                name="new_password"
                                placeholder="Masukkan password baru (minimal 8 karakter)"
                                required
                            >
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Password minimal 8 karakter
                            </small>
                            @error('new_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Confirm New Password --}}
                        <div class="mb-4">
                            <label for="new_password_confirmation" class="form-label fw-semibold">
                                <i class="fas fa-check-double text-info me-2"></i>
                                Konfirmasi Password Baru
                            </label>
                            <input 
                                type="password" 
                                class="form-control" 
                                id="new_password_confirmation" 
                                name="new_password_confirmation"
                                placeholder="Ketik ulang password baru"
                                required
                            >
                        </div>

                        {{-- Buttons --}}
                        <div class="d-flex gap-2 justify-content-end">
                            <a href="{{ url()->previous() }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Kembali
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Info Card --}}
            <div class="card border-info mt-4">
                <div class="card-body">
                    <h6 class="card-title text-info">
                        <i class="fas fa-shield-alt me-2"></i>
                        Tips Keamanan Password
                    </h6>
                    <ul class="small mb-0">
                        <li>Gunakan kombinasi huruf besar, huruf kecil, angka, dan simbol</li>
                        <li>Jangan gunakan password yang mudah ditebak</li>
                        <li>Ganti password secara berkala</li>
                        <li>Jangan bagikan password Anda kepada siapapun</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
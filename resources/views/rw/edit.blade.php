@extends('layout.app')

@section('title', 'Edit RW')
<link rel="icon" type="image/jpeg" href="{{ asset('images/logo.jpg') }}">
@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header">
            <h5>Edit RW</h5>
        </div>

        <div class="card-body">
            <form action="{{ route('rw.update', $rw->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Nama RW</label>
                    <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror"
                           value="{{ old('nama', $rw->nama) }}">
                    @error('nama')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-end">
                    <a href="{{ route('rw.index') }}" class="btn btn-secondary me-2">Kembali</a>
                    <button class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@extends('layout.app')

@section('title', 'Data RW')
<link rel="icon" type="image/jpeg" href="{{ asset('images/logo.jpg') }}">
@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Data RW</h4>
        <a href="{{ route('rw.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Tambah RW
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="5%">No</th>
                        <th>Nama RW</th>
                        <th>Nomor RW</th>

                        <th>Username</th>
                        <th width="20%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rw as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->nama }}</td>
                            <td>{{ $item->nomor_rw }}</td>

                            <td>{{ $item->user->username }}</td>
                            <td>
                                <a href="{{ route('rw.edit', $item->id) }}" class="btn btn-warning btn-sm">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </a>

                                <form action="{{ route('rw.destroy', $item->id) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Yakin hapus RW ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm">
                                        <i class="bi bi-trash"></i> Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">
                                Data RW belum tersedia
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

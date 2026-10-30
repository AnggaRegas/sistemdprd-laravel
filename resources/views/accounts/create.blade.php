@extends('layouts.admin', [
    'role' => 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route('dashboard')],
        ['label' => 'Daftar Akun Pengguna', 'url' => route('akun.index')],
        ['label' => 'Tambah Akun Staf'],
    ],
])

@section('title', 'Tambah Akun Staf')

@section('content')
    <x-admin.page-header
        eyebrow="Manajemen akses"
        title="Tambah Akun Staf"
        description="Buat akun staf pendamping dan tugaskan ke satu anggota DPRD yang masih aktif."
    />

    @include('accounts._form', [
        'account' => null,
        'members' => $members,
        'canDeactivate' => true,
    ])
@endsection

@extends('layouts.admin', [
    'role' => auth()->user()->isStaff() ? 'staff' : 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route(auth()->user()->dashboardRouteName())],
        ['label' => 'Arsip Dokumen', 'url' => route('arsip.index')],
        ['label' => 'Tambah Arsip'],
    ],
])

@section('title', 'Tambah Arsip Dokumen')

@section('content')
    <x-admin.page-header
        eyebrow="Manajemen arsip"
        title="Tambah Arsip Dokumen"
        description="Simpan metadata dan berkas dokumen. Arsip dapat dihubungkan ke satu anggota DPRD."
    />

    @include('archives._form', [
        'archive' => $archive,
        'categories' => $categories,
        'members' => $members,
        'selectedMemberId' => $selectedMemberId,
    ])
@endsection

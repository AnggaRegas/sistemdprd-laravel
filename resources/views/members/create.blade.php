@extends('layouts.admin', [
    'role' => 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route('dashboard')],
        ['label' => 'Anggota DPRD', 'url' => route('anggota.index')],
        ['label' => 'Tambah Anggota Dewan'],
    ],
])

@section('title', 'Tambah Anggota Dewan')

@section('content')
    <x-admin.page-header
        eyebrow="Data kelembagaan"
        title="Tambah Anggota Dewan"
        description="Lengkapi identitas resmi anggota DPRD. Penugasan staf dilakukan terpisah melalui Kelola Akun Staf."
    />

    @include('members._form', [
        'member' => $member,
        'parties' => $parties,
        'kecamatanOptions' => $kecamatanOptions,
    ])
@endsection

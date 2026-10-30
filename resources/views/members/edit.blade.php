@extends('layouts.admin', [
    'role' => 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route('dashboard')],
        ['label' => 'Anggota DPRD', 'url' => route('anggota.index')],
        ['label' => 'Edit Anggota Dewan'],
    ],
])

@section('title', 'Edit Anggota Dewan')

@section('content')
    <x-admin.page-header
        eyebrow="Data kelembagaan"
        title="Edit Anggota Dewan"
        description="Perbarui identitas anggota DPRD. Perubahan status tidak menghapus riwayat arsip dan laporan."
    >
        <a href="{{ route('anggota.arsip', $member) }}" class="btn btn-outline-soft">
            <i class="ri-archive-2-line" aria-hidden="true"></i>
            Buka Arsip
        </a>
    </x-admin.page-header>

    @include('members._form', [
        'member' => $member,
        'parties' => $parties,
        'kecamatanOptions' => $kecamatanOptions,
    ])
@endsection

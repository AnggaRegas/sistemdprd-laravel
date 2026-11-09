@extends('layouts.admin', [
    'role' => 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route('dashboard')],
        ['label' => 'Kamus Acuan', 'url' => route('kamus.index')],
        ['label' => 'Tambah Versi'],
    ],
])

@section('title', 'Tambah Versi Kamus Acuan')

@section('content')
    <x-admin.page-header
        eyebrow="Kamus Acuan"
        title="Tambah Versi"
        description="Buat versi tahunan baru. Entri dapat disalin dari versi sebelumnya lalu disesuaikan."
    />

    @if ($activeVersion)
        <div class="demo-note mb-3">
            <i class="ri-information-line" aria-hidden="true"></i>
            <div>Versi aktif saat ini adalah {{ $activeVersion->tahun }}. Jika versi baru diaktifkan, versi lama otomatis menjadi nonaktif.</div>
        </div>
    @endif

    @include('kamus.versi._form', [
        'version' => null,
    ])
@endsection

@extends('layouts.admin', [
    'role' => 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route('dashboard')],
        ['label' => 'Dokumentasi Kegiatan', 'url' => route('landing.galleries.index')],
        ['label' => 'Buat Album'],
    ],
])

@section('title', 'Buat Album Dokumentasi')

@section('content')
    <x-admin.page-header
        eyebrow="Manajemen Landing Page"
        title="Buat Album Dokumentasi"
        description="Tambahkan album foto dokumentasi kegiatan. Foto kegiatan dapat diunggah langsung bersama album baru."
    >
        <a href="{{ route('landing.galleries.index') }}" class="btn btn-outline-soft">
            <i class="ri-arrow-left-line" aria-hidden="true"></i>
            Kembali
        </a>
    </x-admin.page-header>

    @include('landing.galleries._form', [
        'gallery' => $gallery,
        'statusOptions' => $statusOptions,
        'isEdit' => false,
    ])
@endsection

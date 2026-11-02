@extends('layouts.admin', [
    'role' => 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route('dashboard')],
        ['label' => 'Dokumentasi Kegiatan', 'url' => route('landing.galleries.index')],
        ['label' => 'Kelola Album'],
    ],
])

@section('title', 'Kelola Album Dokumentasi')

@section('content')
    <x-admin.page-header
        eyebrow="Manajemen Landing Page"
        title="Kelola Album Dokumentasi"
        description="Album: {{ $gallery->title }} — perbarui informasi album, unggah foto baru, atur caption, sampul, dan urutan foto."
    >
        @if ($gallery->status === 'published')
            <a href="{{ route('dokumentasi.show', $gallery->slug) }}" class="btn btn-outline-soft"
               target="_blank" rel="noopener noreferrer">
                <i class="ri-external-link-line" aria-hidden="true"></i>
                Lihat di situs
            </a>
        @endif

        <a href="{{ route('landing.galleries.index') }}" class="btn btn-outline-soft">
            <i class="ri-arrow-left-line" aria-hidden="true"></i>
            Kembali
        </a>
    </x-admin.page-header>

    @include('landing.galleries._form', [
        'gallery' => $gallery,
        'statusOptions' => $statusOptions,
        'isEdit' => true,
    ])

    @include('landing.galleries._photo-manager', ['gallery' => $gallery])
@endsection

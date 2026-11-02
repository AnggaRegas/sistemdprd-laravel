@extends('layouts.admin', [
    'role' => 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route('dashboard')],
        ['label' => 'Berita & Kegiatan', 'url' => route('landing.news.index')],
        ['label' => 'Tambah Berita'],
    ],
])

@section('title', 'Tambah Berita')

@section('content')
    <x-admin.page-header
        eyebrow="Manajemen Landing Page"
        title="Tambah Berita"
        description="Buat berita atau pengumuman baru untuk halaman informasi publik DPRD Kota Parepare."
    />

    @include('landing.news._form', [
        'news' => $news,
        'statusOptions' => $statusOptions,
        'categoryOptions' => $categoryOptions,
    ])
@endsection

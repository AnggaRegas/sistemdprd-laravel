@extends('layouts.admin', [
    'role' => 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route('dashboard')],
        ['label' => 'Berita & Kegiatan', 'url' => route('landing.news.index')],
        ['label' => 'Edit Berita'],
    ],
])

@section('title', 'Edit Berita')

@section('content')
    <x-admin.page-header
        eyebrow="Manajemen Landing Page"
        title="Edit Berita"
        description="Perbarui isi, status publikasi, dan gambar utama berita."
    />

    @include('landing.news._form', [
        'news' => $news,
        'statusOptions' => $statusOptions,
        'categoryOptions' => $categoryOptions,
    ])
@endsection

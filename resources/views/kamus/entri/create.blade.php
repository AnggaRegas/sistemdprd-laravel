@extends('layouts.admin', [
    'role' => 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route('dashboard')],
        ['label' => 'Kamus Acuan', 'url' => route('kamus.index', ['versi' => $version->id])],
        ['label' => 'Tambah Entri'],
    ],
])

@section('title', 'Tambah Entri Kamus '.$version->tahun)

@section('content')
    <x-admin.page-header
        eyebrow="Kamus Acuan {{ $version->tahun }}"
        title="Tambah Entri"
        description="Lengkapi kode, klasifikasi, istilah pencocokan, dan tindak lanjut untuk versi ini."
    />

    @include('kamus.entri._form', [
        'entry' => null,
    ])
@endsection

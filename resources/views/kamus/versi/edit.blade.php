@extends('layouts.admin', [
    'role' => 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route('dashboard')],
        ['label' => 'Kamus Acuan', 'url' => route('kamus.index', ['versi' => $version->id])],
        ['label' => 'Edit Versi '.$version->tahun],
    ],
])

@section('title', 'Edit Versi Kamus Acuan '.$version->tahun)

@section('content')
    <x-admin.page-header
        eyebrow="Kamus Acuan"
        title="Edit Versi {{ $version->tahun }}"
        description="Perbarui nama, keterangan, atau status versi. Perubahan tidak mengubah riwayat hasil pemeriksaan lama."
    />

    @include('kamus.versi._form', [
        'version' => $version,
    ])
@endsection

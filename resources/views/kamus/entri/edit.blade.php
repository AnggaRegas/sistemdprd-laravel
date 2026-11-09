@extends('layouts.admin', [
    'role' => 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route('dashboard')],
        ['label' => 'Kamus Acuan', 'url' => route('kamus.index', ['versi' => $version->id])],
        ['label' => 'Edit Entri '.$entry->kode],
    ],
])

@section('title', 'Edit Entri '.$entry->kode)

@section('content')
    <x-admin.page-header
        eyebrow="Kamus Acuan {{ $version->tahun }}"
        title="Edit Entri {{ $entry->kode }}"
        description="Perubahan berlaku pada versi ini. Hasil pemeriksaan lama tetap memakai snapshot saat pemeriksaan berlangsung."
    >
        <a href="{{ route('kamus.index', ['versi' => $version->id]) }}" class="btn btn-outline-soft">
            <i class="ri-arrow-left-line" aria-hidden="true"></i>
            Kembali
        </a>
    </x-admin.page-header>

    @include('kamus.entri._form')
@endsection

@extends('layouts.admin', [
    'role' => 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route('dashboard')],
        ['label' => 'Arsip Komisi & RDP', 'url' => route('komisi.index')],
        ['label' => 'Tambah Komisi'],
    ],
])

@section('title', 'Tambah Komisi')

@section('content')
    <x-admin.page-header
        eyebrow="Arsip kelembagaan"
        title="Tambah Komisi"
        description="Buat folder komisi baru untuk menyimpan arsip Rapat Dengar Pendapat."
    />

    @include('commissions._form', [
        'commission' => null,
    ])
@endsection

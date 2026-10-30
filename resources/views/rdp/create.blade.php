@extends('layouts.admin', [
    'role' => 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route('dashboard')],
        ['label' => 'Arsip Komisi & RDP', 'url' => route('komisi.index')],
        ['label' => $commission->name, 'url' => route('komisi.rdp.index', $commission)],
        ['label' => 'Tambah RDP'],
    ],
])

@section('title', 'Tambah RDP')

@section('content')
    <x-admin.page-header
        eyebrow="Folder RDP"
        title="Tambah RDP"
        :description="'Tambahkan Rapat Dengar Pendapat baru ke folder '.$commission->name.'.'"
    >
        <a href="{{ route('komisi.rdp.index', $commission) }}" class="btn btn-outline-soft">
            <i class="ri-arrow-left-line" aria-hidden="true"></i>
            Kembali ke Folder
        </a>
    </x-admin.page-header>

    @include('rdp._form', [
        'meeting' => $meeting,
        'commission' => $commission,
        'commissions' => $commissions,
        'statuses' => $statuses,
    ])
@endsection

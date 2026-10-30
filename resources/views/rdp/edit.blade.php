@extends('layouts.admin', [
    'role' => 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route('dashboard')],
        ['label' => 'Arsip Komisi & RDP', 'url' => route('komisi.index')],
        ['label' => $commission->name, 'url' => route('komisi.rdp.index', $commission)],
        ['label' => $meeting->title, 'url' => route('komisi.rdp.show', [$commission, $meeting])],
        ['label' => 'Edit RDP'],
    ],
])

@section('title', 'Edit RDP')

@section('content')
    <x-admin.page-header
        eyebrow="Folder RDP"
        title="Edit RDP"
        :description="'Perbarui informasi RDP '.$meeting->title.'.'"
    >
        <a href="{{ route('komisi.rdp.show', [$commission, $meeting]) }}" class="btn btn-outline-soft">
            <i class="ri-eye-line" aria-hidden="true"></i>
            Lihat Detail
        </a>
    </x-admin.page-header>

    @include('rdp._form', [
        'meeting' => $meeting,
        'commission' => $commission,
        'commissions' => $commissions,
        'statuses' => $statuses,
    ])
@endsection

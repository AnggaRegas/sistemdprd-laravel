@extends('layouts.admin', [
    'role' => 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route('dashboard')],
        ['label' => 'Agenda Kegiatan', 'url' => route('landing.agendas.index')],
        ['label' => 'Tambah Agenda'],
    ],
])

@section('title', 'Tambah Agenda')

@section('content')
    <x-admin.page-header
        eyebrow="Manajemen Landing Page"
        title="Tambah Agenda"
        description="Buat jadwal kegiatan baru yang akan tampil pada halaman informasi publik."
    />

    @include('landing.agendas._form', [
        'agenda' => $agenda,
        'statusOptions' => $statusOptions,
        'eventStatusOptions' => $eventStatusOptions,
    ])
@endsection

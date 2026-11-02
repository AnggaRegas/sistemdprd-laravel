@extends('layouts.admin', [
    'role' => 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route('dashboard')],
        ['label' => 'Agenda Kegiatan', 'url' => route('landing.agendas.index')],
        ['label' => 'Edit Agenda'],
    ],
])

@section('title', 'Edit Agenda')

@section('content')
    <x-admin.page-header
        eyebrow="Manajemen Landing Page"
        title="Edit Agenda"
        description="Perbarui jadwal, lokasi, status, dan gambar kegiatan."
    />

    @include('landing.agendas._form', [
        'agenda' => $agenda,
        'statusOptions' => $statusOptions,
        'eventStatusOptions' => $eventStatusOptions,
    ])
@endsection

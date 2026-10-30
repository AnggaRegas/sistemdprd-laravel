@extends('layouts.admin', [
    'role' => auth()->user()->isStaff() ? 'staff' : 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route(auth()->user()->dashboardRouteName())],
        ['label' => 'Arsip Dokumen', 'url' => route('arsip.index')],
        ['label' => 'Edit Arsip'],
    ],
])

@section('title', 'Edit Arsip Dokumen')

@section('content')
    <x-admin.page-header
        eyebrow="Manajemen arsip"
        title="Edit Arsip Dokumen"
        description="Perbarui metadata atau unggah berkas pengganti. Berkas lama tetap disimpan untuk keperluan audit."
    >
        @if ($archive->dprd_member_id)
            <a href="{{ route('anggota.arsip', $archive->dprd_member_id) }}" class="btn btn-outline-soft">
                <i class="ri-archive-2-line" aria-hidden="true"></i>
                Buka Arsip Anggota
            </a>
        @endif
    </x-admin.page-header>

    @include('archives._form', [
        'archive' => $archive,
        'categories' => $categories,
        'members' => $members,
        'selectedMemberId' => $selectedMemberId,
    ])
@endsection

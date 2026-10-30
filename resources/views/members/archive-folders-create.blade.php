@extends('layouts.admin', [
    'role' => auth()->user()->isStaff() ? 'staff' : 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route(auth()->user()->dashboardRouteName())],
        ['label' => 'Arsip Dokumen', 'url' => route('arsip.index')],
        ['label' => $member->nama_lengkap, 'url' => route('anggota.arsip', $member)],
        ['label' => $category->nama, 'url' => route('anggota.arsip.tahun', [$member, $category])],
        ['label' => (string) $archiveYear->tahun, 'url' => route('anggota.arsip.folder', [$member, $category, $archiveYear])],
        ['label' => 'Tambah Folder'],
    ],
])

@section('title', 'Tambah Folder Dokumen')

@section('content')
    <x-admin.page-header
        eyebrow="Arsip Dokumen"
        title="Tambah Folder Dokumen"
        :description="'Buat folder untuk mengelompokkan dokumen pada tahun anggaran '.$archiveYear->tahun.'.'"
    >
        <a href="{{ route('anggota.arsip.folder', [$member, $category, $archiveYear]) }}" class="btn btn-outline-soft">
            <i class="ri-arrow-left-line" aria-hidden="true"></i>
            Kembali ke Folder
        </a>
    </x-admin.page-header>

    <x-admin.archive-member-header :member="$member" />

    <x-admin.archive-context-strip :items="[
        ['icon' => 'ri-folder-2-line', 'label' => 'Kategori', 'value' => $category->nama],
        ['icon' => 'ri-calendar-2-line', 'label' => 'Tahun anggaran', 'value' => $archiveYear->tahun],
    ]" />

    <x-admin.archive-stepper :steps="$stepper" :current="$currentStep" />

    @include('members._archive-folder-form', [
        'member' => $member,
        'category' => $category,
        'archiveYear' => $archiveYear,
        'folder' => $folder,
    ])
@endsection

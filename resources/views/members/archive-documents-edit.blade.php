@extends('layouts.admin', [
    'role' => auth()->user()->isStaff() ? 'staff' : 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route(auth()->user()->dashboardRouteName())],
        ['label' => 'Arsip Dokumen', 'url' => route('arsip.index')],
        ['label' => $member->nama_lengkap, 'url' => route('anggota.arsip', $member)],
        ['label' => $category->nama, 'url' => route('anggota.arsip.tahun', [$member, $category])],
        ['label' => (string) $archiveYear->tahun, 'url' => route('anggota.arsip.folder', [$member, $category, $archiveYear])],
        ['label' => $folder->nama, 'url' => route('anggota.arsip.dokumen', [$member, $category, $archiveYear, $folder])],
        ['label' => 'Edit Dokumen'],
    ],
])

@section('title', 'Edit Metadata Dokumen')

@section('content')
    <x-admin.page-header
        eyebrow="Arsip Dokumen"
        title="Edit Metadata Dokumen"
        :description="'Perbarui metadata dokumen pada folder '.$folder->nama.'. Berkas lama tetap tersimpan jika tidak diganti.'"
    >
        <a href="{{ route('anggota.arsip.dokumen', [$member, $category, $archiveYear, $folder]) }}"
           class="btn btn-outline-soft">
            <i class="ri-arrow-left-line" aria-hidden="true"></i>
            Kembali ke Berkas
        </a>
    </x-admin.page-header>

    <x-admin.archive-member-header :member="$member" />

    <x-admin.archive-context-strip :items="[
        ['icon' => 'ri-folder-2-line', 'label' => 'Kategori', 'value' => $category->nama],
        ['icon' => 'ri-calendar-2-line', 'label' => 'Tahun anggaran', 'value' => $archiveYear->tahun],
        ['icon' => 'ri-folder-3-line', 'label' => 'Folder', 'value' => $folder->nama],
    ]" />

    <x-admin.archive-stepper :steps="$stepper" :current="$currentStep" />

    @include('members._archive-document-form', [
        'member' => $member,
        'category' => $category,
        'archiveYear' => $archiveYear,
        'folder' => $folder,
        'document' => $document,
    ])
@endsection

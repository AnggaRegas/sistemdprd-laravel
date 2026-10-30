@extends('layouts.admin', [
    'role' => auth()->user()->isStaff() ? 'staff' : 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route(auth()->user()->dashboardRouteName())],
        ['label' => 'Arsip Dokumen', 'url' => route('arsip.index')],
        ['label' => $member->nama_lengkap, 'url' => route('anggota.arsip', $member)],
        ['label' => $category->nama, 'url' => route('anggota.arsip.tahun', [$member, $category])],
        ['label' => 'Edit Tahun'],
    ],
])

@section('title', 'Edit Tahun Anggaran')

@section('content')
    <x-admin.page-header
        eyebrow="Arsip Dokumen"
        title="Edit Tahun Anggaran"
        :description="'Perbarui tahun anggaran kategori '.$category->nama.' milik '.$member->nama_lengkap.'.'"
    >
        <a href="{{ route('anggota.arsip.tahun', [$member, $category]) }}" class="btn btn-outline-soft">
            <i class="ri-arrow-left-line" aria-hidden="true"></i>
            Kembali ke Tahun
        </a>
    </x-admin.page-header>

    <x-admin.archive-member-header :member="$member" />

    <x-admin.archive-context-strip :items="[
        ['icon' => 'ri-folder-2-line', 'label' => 'Kategori', 'value' => $category->nama],
    ]" />

    <x-admin.archive-stepper :steps="$stepper" :current="$currentStep" />

    @include('members._archive-year-form', [
        'member' => $member,
        'category' => $category,
        'archiveYear' => $archiveYear,
    ])
@endsection

@extends('layouts.admin', [
    'role' => auth()->user()->isStaff() ? 'staff' : 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route(auth()->user()->dashboardRouteName())],
        ['label' => 'Arsip Dokumen', 'url' => route('arsip.index')],
        ['label' => $member->nama_lengkap, 'url' => route('anggota.arsip', $member)],
        ['label' => 'Tambah Kategori'],
    ],
])

@section('title', 'Tambah Kategori Arsip')

@section('content')
    <x-admin.page-header
        eyebrow="Arsip Dokumen"
        title="Tambah Kategori Arsip"
        description="Kategori arsip berlaku untuk seluruh anggota DPRD. Pastikan nama kategori tidak duplikat."
    >
        <a href="{{ route('anggota.arsip', $member) }}" class="btn btn-outline-soft">
            <i class="ri-arrow-left-line" aria-hidden="true"></i>
            Kembali ke Kategori
        </a>
    </x-admin.page-header>

    @include('members._archive-category-form', [
        'member' => $member,
        'category' => $category,
    ])
@endsection

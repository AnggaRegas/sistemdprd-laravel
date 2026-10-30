@extends('layouts.admin', [
    'role' => auth()->user()->isStaff() ? 'staff' : 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route(auth()->user()->dashboardRouteName())],
        ['label' => 'Arsip Dokumen', 'url' => route('arsip.index')],
        ['label' => $member->nama_lengkap, 'url' => route('anggota.arsip', $member)],
        ['label' => 'Edit Kategori'],
    ],
])

@section('title', 'Edit Kategori Arsip')

@section('content')
    <x-admin.page-header
        eyebrow="Arsip Dokumen"
        title="Edit Kategori Arsip"
        description="Perbarui nama, kode, deskripsi, atau status kategori. Nonaktifkan kategori agar tidak dipakai untuk arsip baru."
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

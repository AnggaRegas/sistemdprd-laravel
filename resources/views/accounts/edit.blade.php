@extends('layouts.admin', [
    'role' => 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route('dashboard')],
        ['label' => 'Daftar Akun Pengguna', 'url' => route('akun.index')],
        ['label' => 'Edit Akun'],
    ],
])

@section('title', 'Edit Akun Staf')

@section('content')
    <x-admin.page-header
        eyebrow="Manajemen akses"
        title="Edit Akun Staf"
        :description="'Perbarui identitas, penugasan dewan, dan status akun '.$account->name.'.'"
    />

    @if ($account->isAdministrator())
        <div class="demo-note">
            <i class="ri-shield-user-line" aria-hidden="true"></i>
            <div>
                <strong>Akun administrator.</strong>
                Role administrator tidak dapat diubah dari halaman ini. Perlindungan akun aktif terakhir dan akun
                yang sedang digunakan tetap berlaku di backend.
            </div>
        </div>
    @endif

    @include('accounts._form', [
        'account' => $account,
        'members' => $members,
        'canDeactivate' => $canDeactivate,
    ])
@endsection

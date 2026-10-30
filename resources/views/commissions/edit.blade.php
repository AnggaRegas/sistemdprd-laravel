@extends('layouts.admin', [
    'role' => 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route('dashboard')],
        ['label' => 'Arsip Komisi & RDP', 'url' => route('komisi.index')],
        ['label' => 'Edit Komisi'],
    ],
])

@section('title', 'Edit Komisi')

@section('content')
    <x-admin.page-header
        eyebrow="Arsip kelembagaan"
        :title="'Edit '.$commission->name"
        description="Perbarui nama, bidang, deskripsi, dan status folder komisi."
    >
        <a href="{{ route('komisi.rdp.index', $commission) }}" class="btn btn-outline-soft">
            <i class="ri-folder-open-line" aria-hidden="true"></i>
            Buka Folder RDP
        </a>
    </x-admin.page-header>

    @if ($commission->rdp_count > 0)
        <div class="demo-note">
            <i class="ri-lock-2-line" aria-hidden="true"></i>
            <div>
                <strong>Komisi ini memiliki {{ $commission->rdp_count }} RDP.</strong>
                Komisi tidak dapat dihapus selama RDP masih terkait. Nonaktifkan komisi bila tidak ingin menerima RDP baru.
            </div>
        </div>
    @endif

    @include('commissions._form', [
        'commission' => $commission,
    ])

    @if ($commission->rdp_count === 0)
        <div class="app-card mt-3">
            <div class="app-card-body d-flex align-items-center justify-content-between gap-3 flex-wrap">
                <div>
                    <h2 class="app-card-title">Hapus Komisi</h2>
                    <p class="app-card-subtitle mb-0">
                        Komisi ini belum memiliki RDP. Penghapusan menggunakan soft delete agar riwayat tetap tersimpan.
                    </p>
                </div>
                <button type="button" class="btn btn-soft-danger"
                        data-confirm-action
                        data-confirm-title="Hapus komisi ini?"
                        data-confirm-message="Komisi {{ $commission->name }} akan dihapus (soft delete). Tindakan ini tidak menghapus data lain."
                        data-confirm-label="Hapus komisi"
                        data-confirm-form="#deleteCommissionEdit">
                    <i class="ri-delete-bin-6-line" aria-hidden="true"></i>
                    Hapus Komisi
                </button>
            </div>
        </div>

        <form id="deleteCommissionEdit" method="POST" action="{{ route('komisi.destroy', $commission) }}" class="d-none">
            @csrf
            @method('DELETE')
        </form>
    @endif
@endsection

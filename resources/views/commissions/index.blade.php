@extends('layouts.admin', [
    'role' => 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route('dashboard')],
        ['label' => 'Arsip Komisi & RDP'],
    ],
])

@section('title', 'Arsip Komisi & RDP')

@section('content')
    <x-admin.page-header
        eyebrow="Arsip kelembagaan"
        title="Arsip Komisi & RDP"
        description="Kelola folder komisi dan arsip Rapat Dengar Pendapat DPRD Kota Parepare. Setiap RDP hanya tersimpan pada komisi asalnya."
    >
        <a href="{{ route('komisi.create') }}" class="btn btn-emerald">
            <i class="ri-add-line" aria-hidden="true"></i>
            Tambah Komisi
        </a>
    </x-admin.page-header>

    <div class="app-card mb-3">
        <div class="app-card-body-tight">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #eef6f1; color: #155043;">
                            <i class="ri-government-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ $commissions->count() }}</strong>
                            <span>Komisi aktif dan nonaktif</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #fbf3e2; color: #a9853f;">
                            <i class="ri-calendar-event-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ $totalRdp }}</strong>
                            <span>Rapat Dengar Pendapat</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #eaf2fb; color: #2c6aa0;">
                            <i class="ri-file-3-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ $totalDocuments }}</strong>
                            <span>Dokumen RDP</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($commissions->isEmpty())
        <div class="app-card">
            <x-admin.empty-state
                icon="ri-government-line"
                title="Belum ada komisi"
                description="Belum ada folder komisi yang tercatat. Tambahkan komisi untuk mulai menyimpan arsip RDP."
            >
                <a href="{{ route('komisi.create') }}" class="btn btn-emerald btn-sm">
                    <i class="ri-add-line" aria-hidden="true"></i>
                    Tambah Komisi
                </a>
            </x-admin.empty-state>
        </div>
    @else
        <div class="commission-grid">
            @foreach ($commissions as $commission)
                <article class="commission-card {{ $commission->is_active ? '' : 'is-inactive' }}">
                    <div class="commission-card-top">
                        <span class="commission-icon" aria-hidden="true">
                            <i class="{{ $commission->icon }}"></i>
                        </span>

                        <div class="commission-card-actions">
                            @if ($commission->is_active)
                                <span class="status-badge status-success">Aktif</span>
                            @else
                                <span class="status-badge status-danger">Nonaktif</span>
                            @endif

                            <div class="dropdown action-dropdown">
                                <button type="button" class="action-menu-btn" data-bs-toggle="dropdown"
                                        data-bs-boundary="viewport"
                                        data-bs-popper-config='{"strategy":"fixed"}'
                                        aria-expanded="false" aria-label="Tindakan untuk {{ $commission->name }}">
                                    <i class="ri-more-2-fill" aria-hidden="true"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('komisi.edit', $commission) }}">
                                            <i class="ri-edit-line" aria-hidden="true"></i>
                                            Edit Komisi
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        @if ($commission->rdp_count > 0)
                                            <span class="dropdown-item-text text-muted-soft fs-10 d-flex align-items-start gap-2">
                                                <i class="ri-lock-2-line mt-1" aria-hidden="true"></i>
                                                Tidak dapat dihapus karena masih memiliki {{ $commission->rdp_count }} RDP
                                            </span>
                                        @else
                                            <button type="button" class="dropdown-item text-danger"
                                                    data-confirm-action
                                                    data-confirm-title="Hapus komisi ini?"
                                                    data-confirm-message="Komisi {{ $commission->name }} akan dihapus (soft delete). Komisi tanpa RDP dapat dihapus, tetapi riwayatnya tetap tersimpan untuk audit."
                                                    data-confirm-label="Hapus komisi"
                                                    data-confirm-form="#deleteCommission{{ $commission->id }}">
                                                <i class="ri-delete-bin-6-line" aria-hidden="true"></i>
                                                Hapus Komisi
                                            </button>
                                        @endif
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="commission-card-body">
                        <div class="commission-field">{{ $commission->field ?: 'Bidang komisi belum diisi' }}</div>
                        <h2 class="commission-name">{{ $commission->name }}</h2>
                        <p class="commission-description">
                            {{ $commission->description ?: 'Belum ada deskripsi untuk komisi ini.' }}
                        </p>
                    </div>

                    <div class="commission-card-meta">
                        <span class="commission-meta-item">
                            <i class="ri-calendar-event-line" aria-hidden="true"></i>
                            <strong>{{ $commission->rdp_count }}</strong> RDP
                        </span>
                        <span class="commission-meta-item">
                            <i class="ri-file-3-line" aria-hidden="true"></i>
                            <strong>{{ $commission->document_count }}</strong> dokumen
                        </span>
                    </div>

                    <div class="commission-card-footer">
                        <a href="{{ route('komisi.rdp.index', $commission) }}" class="btn btn-emerald btn-sm">
                            <i class="ri-folder-open-line" aria-hidden="true"></i>
                            Buka Folder RDP
                        </a>
                        <a href="{{ route('komisi.edit', $commission) }}" class="app-card-link">
                            Detail komisi
                            <i class="ri-arrow-right-line" aria-hidden="true"></i>
                        </a>
                    </div>

                    <form id="deleteCommission{{ $commission->id }}" method="POST"
                          action="{{ route('komisi.destroy', $commission) }}" class="d-none">
                        @csrf
                        @method('DELETE')
                    </form>
                </article>
            @endforeach
        </div>
    @endif
@endsection

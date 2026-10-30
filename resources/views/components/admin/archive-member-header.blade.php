@props([
    'member',
    'eyebrow' => 'Anggota DPRD Kota Parepare',
])

<div class="app-card archive-member-card mb-3">
    <div class="app-card-body">
        <div class="archive-member-grid">
            <div class="archive-member-identity">
                <x-admin.avatar :name="$member->nama_lengkap" :tone="$member->avatar_tone" size="lg" />

                <div class="archive-member-copy">
                    <div class="page-eyebrow mb-1">{{ $eyebrow }}</div>
                    <h2 class="archive-member-name">{{ $member->nama_lengkap }}</h2>

                    <div class="archive-member-meta">
                        <span class="member-code">{{ $member->kode_label }}</span>
                        <span>{{ $member->jabatan ?: 'Anggota DPRD' }}</span>
                        <span>{{ $member->partai ?: 'Partai belum diatur' }}</span>
                    </div>
                </div>
            </div>

            <div class="archive-member-side">
                <x-admin.status-badge :status="$member->is_active ? 'Aktif' : 'Nonaktif'" />

                <dl class="archive-member-facts">
                    <div>
                        <dt>Kecamatan asal</dt>
                        <dd>{{ $member->kecamatan ?: 'Belum diatur' }}</dd>
                    </div>
                    <div>
                        <dt>Staf pendamping</dt>
                        <dd>{{ $member->staff?->name ?? 'Belum ditugaskan' }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</div>

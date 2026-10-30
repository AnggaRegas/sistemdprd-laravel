@php
    $initials = \App\Support\PortalData::initials($displayName);
    $dashboardRoute = $role === 'staff' ? route('dashboard.staf') : route('dashboard');
    $dashboardActive = $role === 'staff'
        ? request()->routeIs('dashboard.staf')
        : request()->routeIs('dashboard');
    $reportBadge = $pendingReports;
@endphp

<aside class="app-sidebar" id="appSidebar" aria-label="Navigasi utama">
    <div class="sidebar-inner">
        <a href="{{ $dashboardRoute }}" class="sidebar-brand" aria-label="Sistem Pengarsipan DPRD Kota Parepare">
            <span class="brand-mark" aria-hidden="true">
                <i class="ri-building-4-line"></i>
            </span>
            <span class="brand-text">
                <strong>Sistem Pengarsipan</strong>
                <span>DPRD Kota Parepare</span>
            </span>
        </a>

        <div class="sidebar-scroll">
            <div class="sidebar-section-label">Menu utama</div>

            <ul class="sidebar-nav">
                <li>
                    <a href="{{ $dashboardRoute }}"
                       class="sidebar-link {{ $dashboardActive ? 'active' : '' }}"
                       title="{{ $role === 'staff' ? 'Dashboard Staf' : 'Dashboard' }}"
                       @if ($dashboardActive) aria-current="page" @endif>
                        <span class="nav-icon"><i class="ri-dashboard-3-line" aria-hidden="true"></i></span>
                        <span class="sidebar-label">{{ $role === 'staff' ? 'Dashboard Staf' : 'Dashboard' }}</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('arsip.index') }}"
                       class="sidebar-link {{ request()->routeIs('arsip.*') ? 'active' : '' }}"
                       title="Arsip Dokumen"
                       @if (request()->routeIs('arsip.*')) aria-current="page" @endif>
                        <span class="nav-icon"><i class="ri-archive-2-line" aria-hidden="true"></i></span>
                        <span class="sidebar-label">Arsip Dokumen</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('laporan.index') }}"
                       class="sidebar-link {{ request()->routeIs('laporan.*') ? 'active' : '' }}"
                       title="Laporan Masyarakat"
                       @if (request()->routeIs('laporan.*')) aria-current="page" @endif>
                        <span class="nav-icon"><i class="ri-chat-3-line" aria-hidden="true"></i></span>
                        <span class="sidebar-label">Laporan Masyarakat</span>
                        @if ($reportBadge > 0)
                            <span class="sidebar-badge">{{ $reportBadge }}</span>
                        @endif
                    </a>
                </li>

                <li>
                    <a href="{{ route('anggota.index') }}"
                       class="sidebar-link {{ request()->routeIs('anggota.*') ? 'active' : '' }}"
                       title="Anggota DPRD"
                       @if (request()->routeIs('anggota.*')) aria-current="page" @endif>
                        <span class="nav-icon"><i class="ri-team-line" aria-hidden="true"></i></span>
                        <span class="sidebar-label">Anggota DPRD</span>
                    </a>
                </li>
            </ul>

            @if ($role === 'admin')
                <div class="sidebar-section-label">Administrasi</div>

                <ul class="sidebar-nav">
                    <li>
                        <a href="{{ route('komisi.index') }}"
                           class="sidebar-link {{ request()->routeIs('komisi.*') ? 'active' : '' }}"
                           title="Arsip Komisi &amp; RDP"
                           @if (request()->routeIs('komisi.*')) aria-current="page" @endif>
                            <span class="nav-icon"><i class="ri-government-line" aria-hidden="true"></i></span>
                            <span class="sidebar-label">Arsip Komisi &amp; RDP</span>
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('akun.index') }}"
                           class="sidebar-link {{ request()->routeIs('akun.*') ? 'active' : '' }}"
                           title="Kelola Akun Staf"
                           @if (request()->routeIs('akun.*')) aria-current="page" @endif>
                            <span class="nav-icon"><i class="ri-user-settings-line" aria-hidden="true"></i></span>
                            <span class="sidebar-label">Kelola Akun Staf</span>
                        </a>
                    </li>
                </ul>
            @endif

            <div class="sidebar-section-label">Akun</div>

            <ul class="sidebar-nav">
                <li>
                    <a href="{{ route('profile.edit') }}"
                       class="sidebar-link {{ request()->routeIs('profile.*') ? 'active' : '' }}"
                       title="Profil Saya"
                       @if (request()->routeIs('profile.*')) aria-current="page" @endif>
                        <span class="nav-icon"><i class="ri-user-settings-line" aria-hidden="true"></i></span>
                        <span class="sidebar-label">Profil Saya</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="sidebar-user">
            <span class="avatar avatar-sm" aria-hidden="true">{{ $initials }}</span>
            <div class="sidebar-user-meta">
                <strong>{{ $displayName }}</strong>
                <span>{{ $roleLabel }}</span>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="sidebar-user-logout" title="Keluar dari akun" aria-label="Keluar dari akun">
                    <i class="ri-logout-box-r-line" aria-hidden="true"></i>
                </button>
            </form>
        </div>
    </div>
</aside>

@php
    $initials = \App\Support\PortalData::initials($displayName);
    $today = \Carbon\Carbon::now()->locale('id')->translatedFormat('l, d F Y');
    $notifications = $notifications ?? [];
    $unreadNotifications = $unreadNotifications ?? count(array_filter(
        $notifications,
        static fn (array $notification) => empty($notification['read'])
    ));
@endphp

<header class="app-topbar">
    <button type="button" class="topbar-toggle" data-sidebar-toggle aria-label="Buka atau tutup navigasi" aria-controls="appSidebar">
        <i class="ri-menu-2-line" aria-hidden="true"></i>
    </button>

    <div class="topbar-context">
        <nav aria-label="Breadcrumb">
            <ol class="breadcrumb-modern">
                @foreach ($breadcrumbs as $crumb)
                    <li>
                        @if (! empty($crumb['url']) && ! $loop->last)
                            <a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>
                        @else
                            <span class="{{ $loop->last ? 'is-current' : '' }}">{{ $crumb['label'] }}</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
    </div>

    <div class="topbar-actions">
        <span class="topbar-date">
            <i class="ri-calendar-line" aria-hidden="true"></i>
            {{ $today }}
        </span>

        <div class="dropdown">
            <button type="button" class="icon-btn" data-bs-toggle="dropdown" aria-expanded="false"
                    aria-label="Notifikasi{{ $unreadNotifications > 0 ? ', '.$unreadNotifications.' belum dibaca' : '' }}">
                <i class="ri-notification-3-line" aria-hidden="true"></i>
                @if ($unreadNotifications > 0)
                    <span class="notif-badge" data-notif-badge>{{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}</span>
                @endif
            </button>

            <div class="dropdown-menu dropdown-menu-end notif-menu">
                <div class="notif-menu-header">
                    <strong>Notifikasi</strong>
                    <span>{{ $unreadNotifications > 0 ? $unreadNotifications.' belum dibaca' : 'Semua dibaca' }}</span>
                </div>

                <div class="notif-menu-list">
                    @forelse ($notifications as $notification)
                        <a href="{{ route('notifikasi.buka', ['notificationKey' => $notification['key']]) }}"
                           class="notif-item {{ $notification['read'] ? 'is-read' : 'is-unread' }}">
                            <span class="notif-item-icon notif-tone-{{ $notification['tone'] }}">
                                <i class="{{ $notification['icon'] }}" aria-hidden="true"></i>
                            </span>
                            <span class="notif-item-meta">
                                <strong>{{ $notification['judul'] }}</strong>
                                <p>{{ $notification['isi'] }}</p>
                                <span>{{ $notification['waktu'] }}</span>
                            </span>
                            @if (! $notification['read'])
                                <span class="notif-unread" aria-label="Belum dibaca"></span>
                            @endif
                        </a>
                    @empty
                        <div class="p-4 text-center text-muted-soft fs-10">
                            Belum ada notifikasi.
                        </div>
                    @endforelse
                </div>

                @if ($unreadNotifications > 0)
                    <div class="notif-menu-footer">
                        <form method="POST" action="{{ route('notifikasi.baca-semua') }}">
                            @csrf
                            <button type="submit">Tandai semua dibaca</button>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        <span class="topbar-divider" aria-hidden="true"></span>

        <div class="dropdown">
            <button type="button" class="topbar-profile" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menu profil {{ $displayName }}">
                <span class="avatar avatar-sm avatar-emerald" aria-hidden="true">{{ $initials }}</span>
                <span class="topbar-profile-meta">
                    <strong>{{ $displayName }}</strong>
                    <span>{{ $roleLabel }}</span>
                </span>
                <i class="ri-arrow-down-s-line" aria-hidden="true"></i>
            </button>

            <ul class="dropdown-menu dropdown-menu-end profile-menu">
                <li class="profile-menu-head">
                    <strong>{{ $displayName }}</strong>
                    <span>{{ $roleLabel }} &middot; Sekretariat DPRD</span>
                </li>
                <li>
                    <a class="dropdown-item" href="{{ route('profile.edit') }}">
                        <i class="ri-user-settings-line" aria-hidden="true"></i>
                        Profil Saya
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger w-100 text-start border-0 bg-transparent">
                            <i class="ri-logout-box-r-line" aria-hidden="true"></i>
                            Keluar
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>

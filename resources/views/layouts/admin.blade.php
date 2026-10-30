@php
    $currentUser = auth()->user();
    $role = $currentUser?->isStaff() ? 'staff' : 'admin';
    $breadcrumbs = $breadcrumbs ?? [['label' => 'Dashboard']];
    $displayName = $currentUser->name ?? 'Petugas DPRD';
    $roleLabel = $currentUser?->role_label ?? ($role === 'staff' ? 'Staf Pendamping' : 'Administrator');
    $pendingReports = $currentUser
        ? \App\Models\LaporanMasyarakat::badgeCountFor($currentUser)
        : 0;
    $notifications = \App\Support\AdminNotification::for($currentUser);
    $unreadNotifications = count(array_filter(
        $notifications,
        static fn (array $notification) => ! $notification['read']
    ));
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0e3b31">
    <meta name="robots" content="noindex, nofollow">

    <title>@yield('title', 'Dashboard') | Sistem Pengarsipan DPRD</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet">

    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
    @stack('styles')
</head>

<body class="admin-body">
    <script>
        try {
            if (localStorage.getItem('dprd-sidebar-collapsed') === '1' && window.matchMedia('(min-width: 992px)').matches) {
                document.body.classList.add('sidebar-collapsed');
            }
        } catch (error) {
            /* Ignore unavailable storage. */
        }
    </script>

    @include('partials.admin.sidebar', [
        'role' => $role,
        'displayName' => $displayName,
        'roleLabel' => $roleLabel,
        'pendingReports' => $pendingReports,
    ])

    <div class="sidebar-backdrop" data-sidebar-close aria-hidden="true"></div>

    <div class="app-main">
        @include('partials.admin.topbar', [
            'role' => $role,
            'roleLabel' => $roleLabel,
            'displayName' => $displayName,
            'breadcrumbs' => $breadcrumbs,
            'notifications' => $notifications,
            'unreadNotifications' => $unreadNotifications,
        ])

        <main class="app-content">
            @include('partials.admin.flash')
            @yield('content')
        </main>

        @include('partials.admin.footer')
    </div>

    @include('partials.admin.confirm-modal')
    @include('partials.admin.toast')

    @stack('scripts')
</body>
</html>

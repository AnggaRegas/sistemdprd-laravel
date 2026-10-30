<?php

namespace App\Providers;

use App\Models\Arsip;
use App\Models\DprdMember;
use App\Models\KategoriArsip;
use App\Models\User;
use App\Policies\ArchivePolicy;
use App\Policies\KategoriArsipPolicy;
use App\Policies\MemberPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        DprdMember::class => MemberPolicy::class,
        Arsip::class => ArchivePolicy::class,
        KategoriArsip::class => KategoriArsipPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::define('manage-rdp-archives', function (User $user) {
            return $user->isAdministrator();
        });

        /*
         * Administrator boleh mengelola hierarki arsip semua anggota.
         * Staf pendamping hanya untuk anggota yang ditugaskan secara aktif.
         */
        Gate::define('manage-member-archives', function (User $user, DprdMember $member) {
            if ($user->isAdministrator()) {
                return true;
            }

            return $user->isStaff()
                && $user->dprd_member_id
                && (int) $user->dprd_member_id === (int) $member->id;
        });
    }
}

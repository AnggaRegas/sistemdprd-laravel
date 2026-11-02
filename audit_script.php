<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "USERS:\n";
foreach (App\Models\User::query()->orderBy('id')->get() as $u) {
    echo "ID: {$u->id} | User: {$u->username} | Email: {$u->email} | Role: {$u->role} | MemberID: {$u->dprd_member_id} | Active: {$u->is_active}\n";
}

echo "\nACTIVITIES:\n";
echo "Count: " . App\Models\AktivitasSistem::count() . "\n";
foreach (App\Models\AktivitasSistem::latest()->take(5)->get() as $act) {
    echo "{$act->created_at} | {$act->event} | {$act->subject_type}#{$act->subject_id} | user={$act->user_id}\n";
}

echo "\nDISK & STORAGE:\n";
echo "Public disk root: " . config('filesystems.disks.public.root') . "\n";
echo "Public disk url: " . config('filesystems.disks.public.url') . "\n";
echo "Storage link exists: " . (is_link(public_path('storage')) || is_dir(public_path('storage')) ? 'YES' : 'NO') . "\n";

<?php

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\DprdMember;
use App\Models\DprdRegion;
use App\Models\LaporanMasyarakat;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

echo "MEMBERS: ".DprdMember::withTrashed()->count()." total, ".DprdMember::count()." active rows\n";
foreach (DprdMember::withTrashed()->orderBy('kode')->get() as $m) {
    echo $m->id.' | '.$m->kode.' | '.$m->nama_lengkap.' | dapil='.$m->dapil.' | kec='.$m->kecamatan.' | region='.$m->dprd_region_id.' | active='.($m->is_active ? 1 : 0).' | deleted='.($m->deleted_at ? 'yes' : 'no').PHP_EOL;
}

echo "\nUSERS:\n";
foreach (User::withTrashed()->get() as $u) {
    echo $u->id.' | '.$u->name.' | '.$u->username.' | '.$u->role.' | member_id='.($u->dprd_member_id ?? '-').' | active='.($u->is_active ? 1 : 0).' | mustchange='.($u->must_change_password ? 1 : 0).PHP_EOL;
}

echo "\nREGIONS:\n";
foreach (DprdRegion::orderBy('sort_order')->get() as $r) {
    echo $r->id.' | '.$r->name.' | '.$r->slug.' | '.$r->type.' | active='.($r->is_active ? 1 : 0).' | members='.$r->members()->count().PHP_EOL;
}

echo "\nREPORTS: ".LaporanMasyarakat::withTrashed()->count().PHP_EOL;
foreach (LaporanMasyarakat::withTrashed()->latest('id')->limit(10)->get() as $l) {
    echo $l->id.' | '.$l->nomor_tiket.' | '.$l->kecamatan.' | member='.($l->dprd_member_id ?? '-').' | assigned='.($l->assigned_to ?? '-').' | '.$l->status.PHP_EOL;
}

echo "\nTABLES:\n";
foreach (Schema::getTableListing() as $table) {
    echo $table.PHP_EOL;
}

echo "\nUSERS COLUMNS:\n";
foreach (Schema::getColumnListing('users') as $column) {
    echo $column.' ';
}
echo PHP_EOL;

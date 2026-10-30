<?php
$p = new PDO('mysql:host=127.0.0.1;dbname=dprd_sistem', 'root', '');
foreach (['users', 'dprd_members', 'laporan_masyarakat'] as $t) {
    echo "== {$t} ==\n";
    foreach ($p->query('SHOW CREATE TABLE '.$t)->fetchAll(PDO::FETCH_ASSOC) as $r) {
        echo $r['Create Table']."\n";
    }
}

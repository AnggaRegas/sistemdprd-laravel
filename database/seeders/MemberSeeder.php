<?php

namespace Database\Seeders;

use App\Models\DprdMember;
use App\Models\DprdRegion;
use Illuminate\Database\Seeder;

class MemberSeeder extends Seeder
{
    /**
     * Data resmi 25 anggota DPRD Kota Parepare periode 2024-2029.
     *
     * Seeder idempoten: anggota dicocokkan berdasarkan kode stabil (DWN-01,
     * DWN-02, ...). Nama, dapil, dan wilayah aspirasi diperbarui sesuai daftar
     * referensi tanpa menghapus arsip, laporan, atau akun yang sudah ada.
     *
     * Nama staf pendamping tidak dibuat sebagai akun login di sini. Data
     * referensi staf ditangani StaffSeeder dan akun tetap dibuat administrator.
     */
    public function run(): void
    {
        $members = [
            ['kode' => 'DWN-01', 'nama_lengkap' => 'Ir. H. KAHARUDDIN KADIR, M.Si', 'jabatan' => 'Ketua DPRD Kota Parepare', 'partai' => 'Partai Golongan Karya', 'dapil' => 'Soreang'],
            ['kode' => 'DWN-02', 'nama_lengkap' => 'SUYUTI, S.E', 'jabatan' => 'Wakil Ketua I', 'partai' => 'Partai Nasional Demokrat', 'dapil' => 'Bacukiki Barat'],
            ['kode' => 'DWN-03', 'nama_lengkap' => 'MUHAMMAD YUSUF LAPANNA, SH.I', 'jabatan' => 'Wakil Ketua II', 'partai' => 'Partai Gerakan Indonesia Raya', 'dapil' => 'Bacukiki'],
            ['kode' => 'DWN-04', 'nama_lengkap' => 'ACHMAD ARIADY, S.E', 'jabatan' => 'Anggota', 'partai' => 'Partai Nasional Demokrat', 'dapil' => 'Bacukiki Barat'],
            ['kode' => 'DWN-05', 'nama_lengkap' => 'ANDI MUH. FUDAIL, S.E', 'jabatan' => 'Anggota', 'partai' => 'Partai Kebangkitan Bangsa', 'dapil' => 'Soreang'],
            ['kode' => 'DWN-06', 'nama_lengkap' => "ASY'ARI ABDULLAH, S.T.", 'jabatan' => 'Anggota', 'partai' => 'Partai Gelombang Rakyat Indonesia', 'dapil' => 'Bacukiki'],
            ['kode' => 'DWN-07', 'nama_lengkap' => 'Dr. KAMALUDDIN KADIR, S.Sos., M.M', 'jabatan' => 'Anggota', 'partai' => 'Partai Gerakan Indonesia Raya', 'dapil' => 'Ujung'],
            ['kode' => 'DWN-08', 'nama_lengkap' => 'Dra. Hj. ASMAWATI', 'jabatan' => 'Anggota', 'partai' => 'Partai Nasional Demokrat', 'dapil' => 'Soreang'],
            ['kode' => 'DWN-09', 'nama_lengkap' => 'H. AHMAD ZULFIKAR ZUHDY, S.T.', 'jabatan' => 'Anggota', 'partai' => 'Partai Demokrat', 'dapil' => 'Soreang'],
            ['kode' => 'DWN-10', 'nama_lengkap' => 'H. HASIB HASYIM, S.T., S.E.', 'jabatan' => 'Anggota', 'partai' => 'Partai Kebangkitan Bangsa', 'dapil' => 'Bacukiki Barat'],
            ['kode' => 'DWN-11', 'nama_lengkap' => 'H. KADARUSMAN MANGURUSI, S.T.', 'jabatan' => 'Anggota', 'partai' => 'Partai Golongan Karya', 'dapil' => 'Ujung'],
            ['kode' => 'DWN-12', 'nama_lengkap' => 'HAMRAN HAMDANI, S.E.', 'jabatan' => 'Anggota', 'partai' => 'Partai Golongan Karya', 'dapil' => 'Bacukiki Barat'],
            ['kode' => 'DWN-13', 'nama_lengkap' => 'Hj. INDRIASARI HUSNI, S.Kom', 'jabatan' => 'Anggota', 'partai' => 'Partai Golongan Karya', 'dapil' => 'Soreang'],
            ['kode' => 'DWN-14', 'nama_lengkap' => 'Hj. APRIANI DJAMALUDDIN', 'jabatan' => 'Anggota', 'partai' => 'Partai Demokrasi Indonesia Perjuangan', 'dapil' => 'Soreang'],
            ['kode' => 'DWN-15', 'nama_lengkap' => 'Hj. FARIDAH, HS, S.E.', 'jabatan' => 'Anggota', 'partai' => 'Partai Demokrat', 'dapil' => 'Ujung'],
            ['kode' => 'DWN-16', 'nama_lengkap' => 'Hj. SRI TANTY MARIANI NASRAH', 'jabatan' => 'Anggota', 'partai' => 'Partai Hati Nurani Rakyat', 'dapil' => 'Ujung'],
            ['kode' => 'DWN-17', 'nama_lengkap' => 'HUSAIN MUHAMMAD SAUD, S.E.', 'jabatan' => 'Anggota', 'partai' => 'Partai Gerakan Indonesia Raya', 'dapil' => 'Soreang'],
            ['kode' => 'DWN-18', 'nama_lengkap' => 'Ir. IBRAHIM SUANDA', 'jabatan' => 'Anggota', 'partai' => 'Partai Amanat Nasional', 'dapil' => 'Ujung'],
            ['kode' => 'DWN-19', 'nama_lengkap' => 'JUSVARI GENDA', 'jabatan' => 'Anggota', 'partai' => 'Partai Keadilan Sejahtera', 'dapil' => 'Bacukiki'],
            ['kode' => 'DWN-20', 'nama_lengkap' => 'MUH. ILHAMSYAH TAUFAN, S.H.', 'jabatan' => 'Anggota', 'partai' => 'Partai Golongan Karya', 'dapil' => 'Bacukiki'],
            ['kode' => 'DWN-21', 'nama_lengkap' => 'MUSDALIFAH PAWE, S.H.', 'jabatan' => 'Anggota', 'partai' => 'Partai Amanat Nasional', 'dapil' => 'Bacukiki Barat'],
            ['kode' => 'DWN-22', 'nama_lengkap' => 'NAMRI NASIR', 'jabatan' => 'Anggota', 'partai' => 'Partai Persatuan Pembangunan', 'dapil' => 'Soreang'],
            ['kode' => 'DWN-23', 'nama_lengkap' => 'RUDY NAJAMUDDIN', 'jabatan' => 'Anggota', 'partai' => 'Partai Persatuan Pembangunan', 'dapil' => 'Bacukiki Barat'],
            ['kode' => 'DWN-24', 'nama_lengkap' => 'S. PARMAN AGOES MANTE', 'jabatan' => 'Anggota', 'partai' => 'Partai Gelombang Rakyat Indonesia', 'dapil' => 'Ujung'],
            ['kode' => 'DWN-25', 'nama_lengkap' => 'SAPPE', 'jabatan' => 'Anggota', 'partai' => 'Partai Keadilan Sejahtera', 'dapil' => 'Bacukiki Barat'],
        ];

        $regions = DprdRegion::query()->pluck('id', 'name');

        /*
         * Nama lama hanya dipakai untuk mencocokkan anggota yang sudah ada
         * ketika kode belum terisi. Data resmi tetap memakai nama pada daftar.
         */
        $legacyNames = [
            'DWN-01' => ['Ir. H. KAHARUDDIN KADIR, M.Si.'],
            'DWN-02' => ['SUYUTI, S.E.'],
            'DWN-03' => ['MUHAMMAD YUSUF LAPANNA, SH.I.'],
            'DWN-04' => ['ACHMAD ARIADY, S.E.'],
            'DWN-05' => ['ANDI MUH. FUDAIL, S.E.'],
            'DWN-11' => ['H. KADARUSMAN MANGURUSI, S.T'],
            'DWN-12' => ['HAMRAN HAMDANI, S.E'],
            'DWN-13' => ['Hj INDRIASARI HUSNI, S.Kom.'],
            'DWN-14' => ['Hj. APRIYANI DJAMALUDDIN'],
        ];

        foreach ($members as $data) {
            $data['kecamatan'] = 'Kecamatan '.$data['dapil'];
            $data['dprd_region_id'] = $regions->get($data['kecamatan']);

            $member = DprdMember::query()->where('kode', $data['kode'])->first();

            if (! $member) {
                $candidates = array_merge([$data['nama_lengkap']], $legacyNames[$data['kode']] ?? []);

                $member = DprdMember::query()
                    ->whereIn('nama_lengkap', $candidates)
                    ->first();
            }

            if ($member) {
                $member->update([
                    'kode' => $member->kode ?: $data['kode'],
                    'nama_lengkap' => $data['nama_lengkap'],
                    'dapil' => $data['dapil'],
                    'kecamatan' => $data['kecamatan'],
                    'dprd_region_id' => $data['dprd_region_id'],
                ]);

                continue;
            }

            DprdMember::query()->create([
                'kode' => $data['kode'],
                'nama_lengkap' => $data['nama_lengkap'],
                'jabatan' => $data['jabatan'],
                'partai' => $data['partai'],
                'dapil' => $data['dapil'],
                'kecamatan' => $data['kecamatan'],
                'dprd_region_id' => $data['dprd_region_id'],
                'is_active' => true,
            ]);
        }
    }
}

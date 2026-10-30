<?php

namespace Tests\Feature;

use App\Models\ArchiveFolder;
use App\Models\ArchiveYear;
use App\Models\Arsip;
use App\Models\DprdMember;
use App\Models\KategoriArsip;
use App\Models\User;
use Database\Seeders\KategoriArsipSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArchiveHierarchyTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        return User::factory()->administrator()->create([
            'name' => 'Administrator Hierarki',
        ]);
    }

    /**
     * @return array{0: ArchiveYear, 1: ArchiveFolder}
     */
    private function hierarchy(
        DprdMember $member,
        KategoriArsip $category,
        int $tahun,
        string $folderName
    ): array {
        $year = ArchiveYear::query()->create([
            'dprd_member_id' => $member->id,
            'kategori_arsip_id' => $category->id,
            'tahun' => $tahun,
        ]);

        $folder = ArchiveFolder::query()->create([
            'archive_year_id' => $year->id,
            'nama' => $folderName,
            'deskripsi' => 'Keterangan '.$folderName,
        ]);

        return [$year, $folder];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function document(
        DprdMember $member,
        KategoriArsip $category,
        ArchiveFolder $folder,
        array $overrides = []
    ): Arsip {
        return Arsip::factory()->create(array_merge([
            'nomor' => 'ARS-UJI-'.fake()->unique()->numerify('#####'),
            'nama' => 'Dokumen Uji',
            'kategori_arsip_id' => $category->id,
            'archive_folder_id' => $folder->id,
            'dprd_member_id' => $member->id,
            'jenis' => Arsip::JENIS_MASUK,
            'status' => Arsip::STATUS_DRAF,
            'tanggal' => '2026-09-01',
            'file_name' => 'dokumen-uji.pdf',
            'file_path' => 'archives/2026/10/dokumen-uji.pdf',
            'file_size' => 2048,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function documentPayload(array $overrides = []): array
    {
        return array_merge([
            'nama' => 'Laporan Hasil Reses',
            'jenis' => Arsip::JENIS_MASUK,
            'tanggal' => '2026-09-15',
            'deskripsi' => 'Dokumen hasil kegiatan reses.',
            'file' => UploadedFile::fake()->create('laporan-reses.pdf', 200, 'application/pdf'),
        ], $overrides);
    }

    public function test_open_archive_button_opens_category_page_for_selected_member(): void
    {
        $this->seed(KategoriArsipSeeder::class);

        $admin = $this->administrator();
        $member = DprdMember::factory()->create([
            'nama_lengkap' => 'Ir. H. Anggota Contoh, M.Si.',
            'jabatan' => 'Anggota DPRD',
            'partai' => 'Partai Contoh',
            'kecamatan' => 'Kecamatan Ujung',
        ]);

        $reses = KategoriArsip::query()->where('nama', 'HASIL RESES')->firstOrFail();
        $pokir = KategoriArsip::query()->where('nama', 'POKOK-POKOK PIKIRAN')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('anggota.index', ['q' => $member->nama_lengkap]))
            ->assertOk()
            ->assertSee('Buka Arsip')
            ->assertSee(route('anggota.arsip', $member), false);

        $this->actingAs($admin)
            ->get(route('anggota.arsip', $member))
            ->assertOk()
            ->assertSee($member->nama_lengkap)
            ->assertSee('Kecamatan Ujung')
            ->assertSee('HASIL RESES')
            ->assertSee('POKOK-POKOK PIKIRAN')
            ->assertSee('Arsip resmi dokumen hasil kegiatan reses anggota DPRD.')
            ->assertSee('Arsip resmi dokumen pokok-pokok pikiran anggota DPRD.')
            ->assertSee('Pilih Tahun')
            ->assertSee('Tambah Kategori')
            ->assertSee(route('anggota.arsip.tahun', [$member, $reses]), false)
            ->assertSee(route('anggota.arsip.tahun', [$member, $pokir]), false)
            ->assertSee('Beranda')
            ->assertSee('Arsip Dokumen')
            ->assertSee('Pilih Anggota DPRD')
            ->assertSee('Pilih Kategori')
            ->assertSee('Tahun Anggaran')
            ->assertSee('Folder Dokumen')
            ->assertSee('Berkas Arsip')
            ->assertSee('archive-stepper', false);
    }

    public function test_category_year_folder_document_navigation_breadcrumbs_and_responsive_markup(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create([
            'nama_lengkap' => 'Anggota Navigasi Arsip',
        ]);
        $category = KategoriArsip::factory()->create([
            'nama' => 'HASIL RESES',
            'kode' => 'RESES',
        ]);

        [$year, $folder] = $this->hierarchy($member, $category, 2026, 'Reses Kelurahan A');
        $document = $this->document($member, $category, $folder, [
            'nama' => 'Laporan Reses Kelurahan A',
            'file_name' => 'laporan-reses-a.pdf',
            'uploaded_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('anggota.arsip.tahun', [$member, $category]))
            ->assertOk()
            ->assertSee('Pilih Tahun Anggaran')
            ->assertSee('2026')
            ->assertSee('Pilih Folder')
            ->assertSee('1 folder')
            ->assertSee(route('anggota.arsip.folder', [$member, $category, $year]), false)
            ->assertSee(route('anggota.arsip', $member), false);

        $this->actingAs($admin)
            ->get(route('anggota.arsip.folder', [$member, $category, $year]))
            ->assertOk()
            ->assertSee('Folder Dokumen')
            ->assertSee('Reses Kelurahan A')
            ->assertSee('Buka Folder')
            ->assertSee('1 berkas')
            ->assertSee(route('anggota.arsip.dokumen', [$member, $category, $year, $folder]), false)
            ->assertSee(route('anggota.arsip.tahun', [$member, $category]), false);

        $this->actingAs($admin)
            ->get(route('anggota.arsip.dokumen', [$member, $category, $year, $folder]))
            ->assertOk()
            ->assertSee('Berkas Arsip')
            ->assertSee('Laporan Reses Kelurahan A')
            ->assertSee('laporan-reses-a.pdf')
            ->assertSee('Administrator Hierarki')
            ->assertSee('Unggah Berkas')
            ->assertSee('No.')
            ->assertSee('Nama Dokumen')
            ->assertSee('Kategori / Jenis Dokumen')
            ->assertSee('Tanggal Dokumen')
            ->assertSee('Nama Berkas')
            ->assertSee('Tanggal Diunggah')
            ->assertSee('Pengunggah')
            ->assertSee('Aksi')
            ->assertSee(route('anggota.arsip.dokumen.create', [$member, $category, $year, $folder]), false)
            ->assertSee(route('anggota.arsip.dokumen.download', [$member, $category, $year, $folder, $document]), false)
            ->assertSee(route('anggota.arsip.folder', [$member, $category, $year]), false)
            ->assertSee('archive-stepper', false)
            ->assertSee('table-responsive', false)
            ->assertSee('name="viewport"', false);
    }

    public function test_upload_download_edit_and_delete_document_uses_private_storage(): void
    {
        Storage::fake('local');

        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $category = KategoriArsip::factory()->create(['nama' => 'HASIL RESES']);
        [$year, $folder] = $this->hierarchy($member, $category, 2026, 'Reses Kelurahan B');

        $this->actingAs($admin)
            ->post(
                route('anggota.arsip.dokumen.store', [$member, $category, $year, $folder]),
                $this->documentPayload()
            )
            ->assertRedirect(route('anggota.arsip.dokumen', [$member, $category, $year, $folder]))
            ->assertSessionHas('success');

        $document = Arsip::query()->firstOrFail();

        $this->assertSame($member->id, $document->dprd_member_id);
        $this->assertSame($category->id, $document->kategori_arsip_id);
        $this->assertSame($folder->id, $document->archive_folder_id);
        $this->assertSame($admin->id, $document->uploaded_by);
        $this->assertSame('laporan-reses.pdf', $document->file_name);
        $this->assertGreaterThan(0, $document->file_size);
        $this->assertStringStartsWith('archives/', $document->file_path);
        $this->assertStringNotContainsString('laporan-reses.pdf', $document->file_path);
        $this->assertStringEndsWith('.pdf', $document->file_path);
        Storage::disk('local')->assertExists($document->file_path);

        $this->actingAs($admin)
            ->get(route('anggota.arsip.dokumen.download', [$member, $category, $year, $folder, $document]))
            ->assertOk()
            ->assertDownload('laporan-reses.pdf');

        $this->actingAs($admin)
            ->get(route('anggota.arsip.dokumen.edit', [$member, $category, $year, $folder, $document]))
            ->assertOk()
            ->assertSee('Edit Metadata Dokumen')
            ->assertSee('Laporan Hasil Reses')
            ->assertSee('Unduh Berkas');

        $oldPath = $document->file_path;
        $oldName = $document->file_name;
        $oldSize = $document->file_size;

        $this->actingAs($admin)
            ->put(
                route('anggota.arsip.dokumen.update', [$member, $category, $year, $folder, $document]),
                $this->documentPayload([
                    'nama' => 'Laporan Reses Setelah Edit',
                    'deskripsi' => 'Metadata diperbarui.',
                    'file' => null,
                ])
            )
            ->assertRedirect(route('anggota.arsip.dokumen', [$member, $category, $year, $folder]))
            ->assertSessionHas('success');

        $document->refresh();

        $this->assertSame('Laporan Reses Setelah Edit', $document->nama);
        $this->assertSame($oldPath, $document->file_path);
        $this->assertSame($oldName, $document->file_name);
        $this->assertSame($oldSize, $document->file_size);
        Storage::disk('local')->assertExists($document->file_path);

        $this->actingAs($admin)
            ->delete(route('anggota.arsip.dokumen.destroy', [$member, $category, $year, $folder, $document]))
            ->assertRedirect(route('anggota.arsip.dokumen', [$member, $category, $year, $folder]))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('arsips', ['id' => $document->id]);
        Storage::disk('local')->assertExists($oldPath);
    }

    public function test_document_validation_rejects_invalid_data_and_files(): void
    {
        Storage::fake('local');

        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $category = KategoriArsip::factory()->create();
        [$year, $folder] = $this->hierarchy($member, $category, 2026, 'Folder Validasi');
        $route = route('anggota.arsip.dokumen.store', [$member, $category, $year, $folder]);

        $this->actingAs($admin)
            ->post($route, [])
            ->assertSessionHasErrors(['nama', 'jenis', 'file']);

        $this->actingAs($admin)
            ->post($route, $this->documentPayload([
                'file' => UploadedFile::fake()->create('program.exe', 20, 'application/x-msdownload'),
            ]))
            ->assertSessionHasErrors('file');

        $this->actingAs($admin)
            ->post($route, $this->documentPayload([
                'tanggal' => 'bukan-tanggal',
            ]))
            ->assertSessionHasErrors('tanggal');

        $this->actingAs($admin)
            ->post($route, $this->documentPayload([
                'jenis' => 'jenis-tidak-dikenal',
            ]))
            ->assertSessionHasErrors('jenis');

        $this->actingAs($admin)
            ->post($route, $this->documentPayload([
                'file' => UploadedFile::fake()->create('terlalu-besar.pdf', 10241, 'application/pdf'),
            ]))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('arsips', 0);
    }

    public function test_year_and_folder_validation_prevents_duplicates_and_invalid_values(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $category = KategoriArsip::factory()->create();

        $yearRoute = route('anggota.arsip.tahun.store', [$member, $category]);

        $this->actingAs($admin)
            ->post($yearRoute, ['tahun' => 2026])
            ->assertRedirect(route('anggota.arsip.tahun', [$member, $category]))
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->post($yearRoute, ['tahun' => 2026])
            ->assertSessionHasErrors('tahun');

        $this->actingAs($admin)
            ->post($yearRoute, ['tahun' => 1800])
            ->assertSessionHasErrors('tahun');

        $this->actingAs($admin)
            ->post($yearRoute, ['tahun' => 2027])
            ->assertSessionHas('success');

        $year2026 = ArchiveYear::query()->where('tahun', 2026)->firstOrFail();
        $year2027 = ArchiveYear::query()->where('tahun', 2027)->firstOrFail();

        $folderRoute = route('anggota.arsip.folder.store', [$member, $category, $year2026]);

        $this->actingAs($admin)
            ->post($folderRoute, ['nama' => 'Reses Kelurahan A'])
            ->assertRedirect(route('anggota.arsip.folder', [$member, $category, $year2026]))
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->post($folderRoute, ['nama' => 'Reses Kelurahan A'])
            ->assertSessionHasErrors('nama');

        $this->actingAs($admin)
            ->post($folderRoute, ['nama' => ''])
            ->assertSessionHasErrors('nama');

        $this->actingAs($admin)
            ->post(
                route('anggota.arsip.folder.store', [$member, $category, $year2027]),
                ['nama' => 'Reses Kelurahan A']
            )
            ->assertSessionHas('success');

        $this->assertDatabaseCount('archive_years', 2);
        $this->assertDatabaseCount('archive_folders', 2);
    }

    public function test_documents_are_isolated_between_members_categories_years_and_folders(): void
    {
        $admin = $this->administrator();

        $memberA = DprdMember::factory()->create(['nama_lengkap' => 'Anggota Isolasi A']);
        $memberB = DprdMember::factory()->create(['nama_lengkap' => 'Anggota Isolasi B']);

        $categoryA = KategoriArsip::factory()->create(['nama' => 'Kategori Isolasi A']);
        $categoryB = KategoriArsip::factory()->create(['nama' => 'Kategori Isolasi B']);

        [$yearA, $folderA] = $this->hierarchy($memberA, $categoryA, 2026, 'Folder Isolasi A');
        [$yearB, $folderB] = $this->hierarchy($memberB, $categoryB, 2026, 'Folder Isolasi B');

        $documentA = $this->document($memberA, $categoryA, $folderA, [
            'nomor' => 'ARS-ISOLASI-A',
            'nama' => 'Dokumen Isolasi A',
            'file_name' => 'isolasi-a.pdf',
        ]);
        $documentB = $this->document($memberB, $categoryB, $folderB, [
            'nomor' => 'ARS-ISOLASI-B',
            'nama' => 'Dokumen Isolasi B',
            'file_name' => 'isolasi-b.pdf',
        ]);

        $this->actingAs($admin)
            ->get(route('anggota.arsip.tahun', [$memberA, $categoryA]))
            ->assertOk()
            ->assertSee('2026')
            ->assertDontSee('Folder Isolasi B');

        $this->actingAs($admin)
            ->get(route('anggota.arsip.folder', [$memberA, $categoryA, $yearA]))
            ->assertOk()
            ->assertSee('Folder Isolasi A')
            ->assertDontSee('Folder Isolasi B');

        $this->actingAs($admin)
            ->get(route('anggota.arsip.dokumen', [$memberA, $categoryA, $yearA, $folderA]))
            ->assertOk()
            ->assertSee($documentA->nama)
            ->assertSee('isolasi-a.pdf')
            ->assertDontSee($documentB->nama)
            ->assertDontSee('isolasi-b.pdf');

        $this->actingAs($admin)
            ->get(route('anggota.arsip.dokumen', [$memberB, $categoryB, $yearB, $folderB]))
            ->assertOk()
            ->assertSee($documentB->nama)
            ->assertSee('isolasi-b.pdf')
            ->assertDontSee($documentA->nama)
            ->assertDontSee('isolasi-a.pdf');
    }

    public function test_staff_cannot_access_or_manage_other_members_archives_or_guess_urls(): void
    {
        Storage::fake('local');

        $memberA = DprdMember::factory()->create(['nama_lengkap' => 'Anggota Staf Hierarki A']);
        $memberB = DprdMember::factory()->create(['nama_lengkap' => 'Anggota Staf Hierarki B']);
        $staffA = User::factory()->staff($memberA)->create(['name' => 'Staf Hierarki A']);

        $categoryA = KategoriArsip::factory()->create();
        $categoryB = KategoriArsip::factory()->create();

        [$yearA, $folderA] = $this->hierarchy($memberA, $categoryA, 2026, 'Folder Staf A');
        [$yearB, $folderB] = $this->hierarchy($memberB, $categoryB, 2026, 'Folder Staf B');

        $folderA2 = ArchiveFolder::query()->create([
            'archive_year_id' => $yearA->id,
            'nama' => 'Folder Staf A Lain',
        ]);

        $documentA = $this->document($memberA, $categoryA, $folderA);
        $documentA2 = $this->document($memberA, $categoryA, $folderA2);
        $documentB = $this->document($memberB, $categoryB, $folderB);

        $this->actingAs($staffA)->get(route('anggota.arsip', $memberA))->assertOk();
        $this->actingAs($staffA)->get(route('anggota.arsip', $memberB))->assertForbidden();
        $this->actingAs($staffA)->get(route('anggota.arsip.tahun', [$memberA, $categoryA]))->assertOk();
        $this->actingAs($staffA)->get(route('anggota.arsip.tahun', [$memberB, $categoryB]))->assertForbidden();
        $this->actingAs($staffA)->get(route('anggota.arsip.folder', [$memberA, $categoryA, $yearA]))->assertOk();
        $this->actingAs($staffA)->get(route('anggota.arsip.folder', [$memberB, $categoryB, $yearB]))->assertForbidden();
        $this->actingAs($staffA)
            ->get(route('anggota.arsip.dokumen', [$memberA, $categoryA, $yearA, $folderA]))
            ->assertOk();
        $this->actingAs($staffA)
            ->get(route('anggota.arsip.dokumen', [$memberB, $categoryB, $yearB, $folderB]))
            ->assertForbidden();

        $this->actingAs($staffA)
            ->post(route('anggota.arsip.tahun.store', [$memberB, $categoryB]), ['tahun' => 2028])
            ->assertForbidden();
        $this->actingAs($staffA)
            ->post(route('anggota.arsip.folder.store', [$memberB, $categoryB, $yearB]), ['nama' => 'Folder Baru'])
            ->assertForbidden();
        $this->actingAs($staffA)
            ->post(
                route('anggota.arsip.dokumen.store', [$memberB, $categoryB, $yearB, $folderB]),
                $this->documentPayload()
            )
            ->assertForbidden();
        $this->actingAs($staffA)
            ->put(
                route('anggota.arsip.dokumen.update', [$memberB, $categoryB, $yearB, $folderB, $documentB]),
                $this->documentPayload(['file' => null])
            )
            ->assertForbidden();
        $this->actingAs($staffA)
            ->delete(route('anggota.arsip.dokumen.destroy', [$memberB, $categoryB, $yearB, $folderB, $documentB]))
            ->assertForbidden();
        $this->actingAs($staffA)
            ->get(route('anggota.arsip.dokumen.download', [$memberB, $categoryB, $yearB, $folderB, $documentB]))
            ->assertForbidden();

        $this->actingAs($staffA)
            ->get(route('anggota.arsip.kategori.create', $memberA))
            ->assertForbidden();
        $this->actingAs($staffA)
            ->post(route('anggota.arsip.kategori.store', $memberA), ['nama' => 'Kategori Staf'])
            ->assertForbidden();
        $this->actingAs($staffA)
            ->get(route('anggota.arsip.kategori.edit', [$memberA, $categoryA]))
            ->assertForbidden();

        // Menebak rantai relasi milik anggota lain harus menghasilkan 404.
        $this->actingAs($staffA)
            ->get(route('anggota.arsip.folder', [$memberA, $categoryA, $yearB]))
            ->assertNotFound();
        $this->actingAs($staffA)
            ->get(route('anggota.arsip.dokumen', [$memberA, $categoryA, $yearA, $folderB]))
            ->assertNotFound();
        $this->actingAs($staffA)
            ->get(route('anggota.arsip.dokumen.edit', [$memberA, $categoryA, $yearA, $folderA, $documentB]))
            ->assertForbidden();
        $this->actingAs($staffA)
            ->get(route('anggota.arsip.dokumen.download', [$memberA, $categoryA, $yearA, $folderA, $documentB]))
            ->assertForbidden();
        $this->actingAs($staffA)
            ->get(route('anggota.arsip.dokumen.edit', [$memberA, $categoryA, $yearA, $folderA, $documentA2]))
            ->assertNotFound();
        $this->actingAs($staffA)
            ->get(route('anggota.arsip.dokumen.download', [$memberA, $categoryA, $yearA, $folderA, $documentA2]))
            ->assertNotFound();

        $this->assertNotSoftDeleted('arsips', ['id' => $documentA->id]);
        $this->assertNotSoftDeleted('arsips', ['id' => $documentA2->id]);
        $this->assertNotSoftDeleted('arsips', ['id' => $documentB->id]);
    }

    public function test_staff_can_manage_and_download_own_members_hierarchy(): void
    {
        Storage::fake('local');

        $member = DprdMember::factory()->create();
        $staff = User::factory()->staff($member)->create();
        $category = KategoriArsip::factory()->create();

        $this->actingAs($staff)
            ->post(route('anggota.arsip.tahun.store', [$member, $category]), ['tahun' => 2028])
            ->assertRedirect(route('anggota.arsip.tahun', [$member, $category]))
            ->assertSessionHas('success');

        $year = ArchiveYear::query()->firstOrFail();

        $this->actingAs($staff)
            ->post(route('anggota.arsip.folder.store', [$member, $category, $year]), ['nama' => 'Folder Staf Sendiri'])
            ->assertSessionHas('success');

        $folder = ArchiveFolder::query()->firstOrFail();

        $this->actingAs($staff)
            ->post(
                route('anggota.arsip.dokumen.store', [$member, $category, $year, $folder]),
                $this->documentPayload(['nama' => 'Dokumen Staf Sendiri'])
            )
            ->assertSessionHas('success');

        $document = Arsip::query()->firstOrFail();

        $this->assertSame($staff->id, $document->uploaded_by);

        $this->actingAs($staff)
            ->get(route('anggota.arsip.dokumen.download', [$member, $category, $year, $folder, $document]))
            ->assertOk()
            ->assertDownload('laporan-reses.pdf');

        $this->actingAs($staff)
            ->put(
                route('anggota.arsip.dokumen.update', [$member, $category, $year, $folder, $document]),
                $this->documentPayload(['nama' => 'Dokumen Staf Diperbarui', 'file' => null])
            )
            ->assertSessionHas('success');

        $this->assertDatabaseHas('arsips', [
            'id' => $document->id,
            'nama' => 'Dokumen Staf Diperbarui',
        ]);
    }

    public function test_administrator_can_crud_categories_and_protected_deletes_keep_data_safe(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();

        $this->actingAs($admin)
            ->get(route('anggota.arsip.kategori.create', $member))
            ->assertOk()
            ->assertSee('Tambah Kategori Arsip');

        $this->actingAs($admin)
            ->post(route('anggota.arsip.kategori.store', $member), [
                'nama' => 'Kategori Baru Uji',
                'kode' => 'KBU',
                'deskripsi' => 'Kategori untuk pengujian.',
                'is_active' => '1',
            ])
            ->assertRedirect(route('anggota.arsip', $member))
            ->assertSessionHas('success');

        $category = KategoriArsip::query()->where('nama', 'Kategori Baru Uji')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('anggota.arsip.kategori.update', [$member, $category]), [
                'nama' => 'Kategori Baru Diperbarui',
                'kode' => 'KBD',
                'deskripsi' => 'Deskripsi baru.',
                'is_active' => '1',
            ])
            ->assertRedirect(route('anggota.arsip', $member))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('kategori_arsips', [
            'id' => $category->id,
            'nama' => 'Kategori Baru Diperbarui',
            'kode' => 'KBD',
        ]);

        $unused = KategoriArsip::factory()->create(['nama' => 'Kategori Tanpa Isi']);

        $this->actingAs($admin)
            ->delete(route('anggota.arsip.kategori.destroy', [$member, $unused]))
            ->assertRedirect(route('anggota.arsip', $member))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('kategori_arsips', ['id' => $unused->id]);

        $usedCategory = KategoriArsip::factory()->create(['nama' => 'Kategori Terpakai']);
        [$year, $folder] = $this->hierarchy($member, $usedCategory, 2026, 'Folder Terpakai');
        $document = $this->document($member, $usedCategory, $folder);

        $this->actingAs($admin)
            ->from(route('anggota.arsip', $member))
            ->delete(route('anggota.arsip.kategori.destroy', [$member, $usedCategory]))
            ->assertRedirect(route('anggota.arsip', $member))
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted('kategori_arsips', ['id' => $usedCategory->id]);

        $this->actingAs($admin)
            ->from(route('anggota.arsip.tahun', [$member, $usedCategory]))
            ->delete(route('anggota.arsip.tahun.destroy', [$member, $usedCategory, $year]))
            ->assertRedirect(route('anggota.arsip.tahun', [$member, $usedCategory]))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('archive_years', ['id' => $year->id]);

        $this->actingAs($admin)
            ->from(route('anggota.arsip.folder', [$member, $usedCategory, $year]))
            ->delete(route('anggota.arsip.folder.destroy', [$member, $usedCategory, $year, $folder]))
            ->assertRedirect(route('anggota.arsip.folder', [$member, $usedCategory, $year]))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('archive_folders', ['id' => $folder->id]);

        $this->actingAs($admin)
            ->delete(route('anggota.arsip.dokumen.destroy', [$member, $usedCategory, $year, $folder, $document]))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('arsips', ['id' => $document->id]);

        $this->actingAs($admin)
            ->from(route('anggota.arsip.folder', [$member, $usedCategory, $year]))
            ->delete(route('anggota.arsip.folder.destroy', [$member, $usedCategory, $year, $folder]))
            ->assertRedirect(route('anggota.arsip.folder', [$member, $usedCategory, $year]))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('archive_folders', ['id' => $folder->id]);

        $emptyYear = ArchiveYear::query()->create([
            'dprd_member_id' => $member->id,
            'kategori_arsip_id' => $usedCategory->id,
            'tahun' => 2029,
        ]);

        $this->actingAs($admin)
            ->delete(route('anggota.arsip.tahun.destroy', [$member, $usedCategory, $emptyYear]))
            ->assertRedirect(route('anggota.arsip.tahun', [$member, $usedCategory]))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('archive_years', ['id' => $emptyYear->id]);
    }

    public function test_inactive_category_blocks_new_years_folders_and_documents(): void
    {
        Storage::fake('local');

        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $category = KategoriArsip::factory()->create([
            'nama' => 'Kategori Nonaktif',
            'is_active' => false,
        ]);

        [$year, $folder] = $this->hierarchy($member, $category, 2026, 'Folder Lama');
        $document = $this->document($member, $category, $folder);

        $this->actingAs($admin)
            ->get(route('anggota.arsip.tahun', [$member, $category]))
            ->assertOk()
            ->assertDontSee(route('anggota.arsip.tahun.create', [$member, $category]), false);

        $this->actingAs($admin)
            ->get(route('anggota.arsip.tahun.create', [$member, $category]))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('anggota.arsip.tahun.store', [$member, $category]), ['tahun' => 2028])
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('anggota.arsip.folder', [$member, $category, $year]))
            ->assertOk()
            ->assertSee('Kategori ini sedang nonaktif')
            ->assertDontSee(route('anggota.arsip.folder.create', [$member, $category, $year]), false);

        $this->actingAs($admin)
            ->post(route('anggota.arsip.folder.store', [$member, $category, $year]), ['nama' => 'Folder Baru'])
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('anggota.arsip.dokumen', [$member, $category, $year, $folder]))
            ->assertOk()
            ->assertSee('unggahan baru dinonaktifkan')
            ->assertDontSee(route('anggota.arsip.dokumen.create', [$member, $category, $year, $folder]), false);

        $this->actingAs($admin)
            ->post(
                route('anggota.arsip.dokumen.store', [$member, $category, $year, $folder]),
                $this->documentPayload()
            )
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('anggota.arsip.dokumen.edit', [$member, $category, $year, $folder, $document]))
            ->assertOk();

        $this->assertDatabaseMissing('archive_years', ['tahun' => 2028]);
        $this->assertDatabaseMissing('archive_folders', ['nama' => 'Folder Baru']);
        $this->assertDatabaseCount('arsips', 1);
    }

    public function test_guests_cannot_access_member_archives(): void
    {
        $member = DprdMember::factory()->create();
        $category = KategoriArsip::factory()->create();
        [$year, $folder] = $this->hierarchy($member, $category, 2026, 'Folder Terbatas');

        $this->get(route('anggota.arsip', $member))->assertRedirect(route('login'));
        $this->post(route('anggota.arsip.tahun.store', [$member, $category]), ['tahun' => 2028])
            ->assertRedirect(route('login'));
        $this->get(route('anggota.arsip.dokumen', [$member, $category, $year, $folder]))
            ->assertRedirect(route('login'));

        $this->assertDatabaseMissing('archive_years', ['tahun' => 2028]);
    }

    public function test_soft_deleted_category_keeps_historical_archive_label(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $category = KategoriArsip::factory()->create(['nama' => 'Notulen Historis']);
        $archive = Arsip::factory()->create([
            'nama' => 'Arsip Notulen Lama',
            'kategori_arsip_id' => $category->id,
            'dprd_member_id' => $member->id,
            'archive_folder_id' => null,
        ]);

        $category->delete();

        $this->assertSoftDeleted('kategori_arsips', ['id' => $category->id]);
        $this->assertSame('Notulen Historis', $archive->fresh()->kategoriArsip?->nama);

        $this->actingAs($admin)
            ->get(route('arsip.index'))
            ->assertOk()
            ->assertSee('Arsip Notulen Lama')
            ->assertSee('Notulen Historis');
    }

    public function test_category_seeder_is_idempotent_and_does_not_create_fake_hierarchy(): void
    {
        $this->seed(KategoriArsipSeeder::class);
        $this->seed(KategoriArsipSeeder::class);

        $this->assertDatabaseCount('kategori_arsips', 2);
        $this->assertSame(1, KategoriArsip::withTrashed()->where('nama', 'HASIL RESES')->count());
        $this->assertSame(1, KategoriArsip::withTrashed()->where('nama', 'POKOK-POKOK PIKIRAN')->count());

        $reses = KategoriArsip::query()->where('nama', 'HASIL RESES')->firstOrFail();
        $pokir = KategoriArsip::query()->where('nama', 'POKOK-POKOK PIKIRAN')->firstOrFail();

        $this->assertSame('Arsip resmi dokumen hasil kegiatan reses anggota DPRD.', $reses->deskripsi);
        $this->assertSame('Arsip resmi dokumen pokok-pokok pikiran anggota DPRD.', $pokir->deskripsi);
        $this->assertTrue($reses->is_active);
        $this->assertTrue($pokir->is_active);

        $this->assertDatabaseCount('archive_years', 0);
        $this->assertDatabaseCount('archive_folders', 0);
        $this->assertDatabaseCount('arsips', 0);
    }
}

<?php

namespace Tests\Feature;

use App\Models\ArchiveFolder;
use App\Models\ArchiveYear;
use App\Models\Arsip;
use App\Models\DprdMember;
use App\Models\KategoriArsip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArchiveManagementTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        return User::factory()->administrator()->create([
            'name' => 'Administrator Arsip',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(DprdMember $member, KategoriArsip $category, array $overrides = []): array
    {
        return array_merge([
            'nomor' => 'ARS-UJI-001',
            'nama' => 'Notulen Rapat Uji',
            'kategori_arsip_id' => $category->id,
            'dprd_member_id' => $member->id,
            'jenis' => Arsip::JENIS_MASUK,
            'status' => Arsip::STATUS_DRAF,
            'tanggal' => '2026-10-20',
            'deskripsi' => 'Keterangan arsip uji.',
        ], $overrides);
    }

    public function test_administrator_archive_list_supports_search_and_filters(): void
    {
        $admin = $this->administrator();
        $memberA = DprdMember::factory()->create(['nama_lengkap' => 'Anggota Arsip Alpha']);
        $memberB = DprdMember::factory()->create(['nama_lengkap' => 'Anggota Arsip Beta']);
        $categoryA = KategoriArsip::factory()->create(['nama' => 'Kategori Alpha']);
        $categoryB = KategoriArsip::factory()->create(['nama' => 'Kategori Beta']);

        $archiveA = Arsip::factory()->create([
            'nomor' => 'ARS-ALPHA',
            'nama' => 'Arsip Alpha Pendidikan',
            'kategori_arsip_id' => $categoryA->id,
            'dprd_member_id' => $memberA->id,
            'status' => Arsip::STATUS_DRAF,
            'tanggal' => '2026-09-01',
        ]);
        $archiveB = Arsip::factory()->create([
            'nomor' => 'ARS-BETA',
            'nama' => 'Arsip Beta Infrastruktur',
            'kategori_arsip_id' => $categoryB->id,
            'dprd_member_id' => $memberB->id,
            'status' => Arsip::STATUS_MENUNGGU_VERIFIKASI,
            'tanggal' => '2026-10-01',
        ]);

        $this->actingAs($admin)
            ->get(route('arsip.index'))
            ->assertOk()
            ->assertSee($archiveA->nama)
            ->assertSee($archiveB->nama)
            ->assertSee('Tambah Arsip');

        $this->actingAs($admin)
            ->get(route('arsip.index', ['q' => 'Alpha Pendidikan']))
            ->assertOk()
            ->assertSee($archiveA->nama)
            ->assertDontSee($archiveB->nama);

        $this->actingAs($admin)
            ->get(route('arsip.index', ['kategori' => $categoryB->id]))
            ->assertOk()
            ->assertSee($archiveB->nama)
            ->assertDontSee($archiveA->nama);

        $this->actingAs($admin)
            ->get(route('arsip.index', ['status' => Arsip::STATUS_DRAF]))
            ->assertOk()
            ->assertSee($archiveA->nama)
            ->assertDontSee($archiveB->nama);

        $this->actingAs($admin)
            ->get(route('arsip.index', ['tanggal' => '2026-09-01']))
            ->assertOk()
            ->assertSee($archiveA->nama)
            ->assertDontSee($archiveB->nama);

        $this->actingAs($admin)
            ->get(route('arsip.index', ['member' => $memberA->id]))
            ->assertOk()
            ->assertSee($archiveA->nama)
            ->assertDontSee($archiveB->nama);
    }

    public function test_administrator_can_create_archive_with_private_file(): void
    {
        Storage::fake('local');

        $admin = $this->administrator();
        $member = DprdMember::factory()->create([
            'nama_lengkap' => 'Anggota Pemilik Arsip',
        ]);
        $category = KategoriArsip::factory()->create();

        $this->actingAs($admin)
            ->post(route('arsip.store'), $this->payload($member, $category, [
                'file' => UploadedFile::fake()->create('notulen-rapat.pdf', 100, 'application/pdf'),
            ]))
            ->assertRedirect(route('arsip.index'))
            ->assertSessionHas('success');

        $archive = Arsip::query()->firstOrFail();

        $this->assertSame($member->id, $archive->dprd_member_id);
        $this->assertSame($admin->id, $archive->uploaded_by);
        $this->assertNotNull($archive->file_path);
        $this->assertSame('notulen-rapat.pdf', $archive->file_name);
        $this->assertGreaterThan(0, $archive->file_size);
        $this->assertStringStartsWith('archives/', $archive->file_path);
        Storage::disk('local')->assertExists($archive->file_path);

        $this->actingAs($admin)
            ->get(route('arsip.index'))
            ->assertOk()
            ->assertSee('Notulen Rapat Uji')
            ->assertSee('ARS-UJI-001');
    }

    public function test_administrator_can_edit_update_and_soft_delete_archive(): void
    {
        Storage::fake('local');

        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $category = KategoriArsip::factory()->create();
        $archive = Arsip::factory()->create([
            'nomor' => 'ARS-EDIT-001',
            'nama' => 'Arsip Sebelum Edit',
            'kategori_arsip_id' => $category->id,
            'dprd_member_id' => $member->id,
        ]);

        $this->actingAs($admin)
            ->get(route('arsip.edit', $archive))
            ->assertOk()
            ->assertSee('Edit Arsip Dokumen')
            ->assertSee('Arsip Sebelum Edit');

        $this->actingAs($admin)
            ->put(route('arsip.update', $archive), $this->payload($member, $category, [
                'nomor' => 'ARS-EDIT-002',
                'nama' => 'Arsip Setelah Edit',
                'status' => Arsip::STATUS_TERVERIFIKASI,
            ]))
            ->assertRedirect(route('arsip.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('arsips', [
            'id' => $archive->id,
            'nomor' => 'ARS-EDIT-002',
            'nama' => 'Arsip Setelah Edit',
            'status' => Arsip::STATUS_TERVERIFIKASI,
        ]);

        $this->actingAs($admin)
            ->delete(route('arsip.destroy', $archive))
            ->assertRedirect(route('arsip.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('arsips', ['id' => $archive->id]);
    }

    public function test_archive_number_must_be_unique_and_file_type_is_validated(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $category = KategoriArsip::factory()->create();
        Arsip::factory()->create([
            'nomor' => 'ARS-SAMA',
            'kategori_arsip_id' => $category->id,
            'dprd_member_id' => $member->id,
        ]);

        $this->actingAs($admin)
            ->post(route('arsip.store'), $this->payload($member, $category, [
                'nomor' => 'ARS-SAMA',
            ]))
            ->assertSessionHasErrors('nomor');

        $this->actingAs($admin)
            ->post(route('arsip.store'), $this->payload($member, $category, [
                'nomor' => 'ARS-FILE-INVALID',
                'file' => UploadedFile::fake()->create('program.exe', 20, 'application/x-msdownload'),
            ]))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseMissing('arsips', ['nomor' => 'ARS-FILE-INVALID']);
    }

    public function test_member_archive_page_shows_categories_and_member_identity(): void
    {
        $admin = $this->administrator();
        $memberA = DprdMember::factory()->create([
            'nama_lengkap' => 'Anggota Halaman Arsip A',
            'kecamatan' => 'Kecamatan Ujung',
        ]);
        $memberB = DprdMember::factory()->create([
            'nama_lengkap' => 'Anggota Halaman Arsip B',
        ]);
        $categoryA = KategoriArsip::factory()->create(['nama' => 'Kategori Halaman A']);
        $categoryB = KategoriArsip::factory()->create(['nama' => 'Kategori Halaman B']);

        $yearA = ArchiveYear::query()->create([
            'dprd_member_id' => $memberA->id,
            'kategori_arsip_id' => $categoryA->id,
            'tahun' => 2026,
        ]);
        $folderA = ArchiveFolder::query()->create([
            'archive_year_id' => $yearA->id,
            'nama' => 'Folder Halaman A',
        ]);
        $yearB = ArchiveYear::query()->create([
            'dprd_member_id' => $memberB->id,
            'kategori_arsip_id' => $categoryB->id,
            'tahun' => 2026,
        ]);
        $folderB = ArchiveFolder::query()->create([
            'archive_year_id' => $yearB->id,
            'nama' => 'Folder Halaman B',
        ]);

        Arsip::factory()->create([
            'nomor' => 'ARS-HALAMAN-A',
            'nama' => 'Dokumen Milik Anggota A',
            'kategori_arsip_id' => $categoryA->id,
            'archive_folder_id' => $folderA->id,
            'dprd_member_id' => $memberA->id,
            'status' => Arsip::STATUS_DRAF,
            'tanggal' => '2026-09-10',
        ]);
        Arsip::factory()->create([
            'nomor' => 'ARS-HALAMAN-B',
            'nama' => 'Dokumen Milik Anggota B',
            'kategori_arsip_id' => $categoryB->id,
            'archive_folder_id' => $folderB->id,
            'dprd_member_id' => $memberB->id,
            'status' => Arsip::STATUS_MENUNGGU_VERIFIKASI,
            'tanggal' => '2026-10-10',
        ]);

        $this->actingAs($admin)
            ->get(route('anggota.arsip', $memberA))
            ->assertOk()
            ->assertSee($memberA->nama_lengkap)
            ->assertDontSee($memberB->nama_lengkap)
            ->assertSee('Kategori Halaman A')
            ->assertSee('Kategori Halaman B')
            ->assertSee('Pilih Tahun')
            ->assertSee('Tahun Anggaran')
            ->assertSee('Kecamatan Ujung')
            ->assertSee('1 dokumen');

        $this->actingAs($admin)
            ->get(route('anggota.arsip', $memberB))
            ->assertOk()
            ->assertSee($memberB->nama_lengkap)
            ->assertDontSee($memberA->nama_lengkap);
    }

    public function test_staff_can_only_manage_archives_of_assigned_member(): void
    {
        Storage::fake('local');

        $memberA = DprdMember::factory()->create([
            'nama_lengkap' => 'Anggota Staf A',
        ]);
        $memberB = DprdMember::factory()->create([
            'nama_lengkap' => 'Anggota Staf B',
        ]);
        $staffA = User::factory()->staff($memberA)->create([
            'name' => 'Staf A',
        ]);
        $staffB = User::factory()->staff($memberB)->create([
            'name' => 'Staf B',
        ]);
        $category = KategoriArsip::factory()->create();

        $archiveA = Arsip::factory()->create([
            'nomor' => 'ARS-STAF-A',
            'nama' => 'Arsip Milik Staf A',
            'kategori_arsip_id' => $category->id,
            'dprd_member_id' => $memberA->id,
        ]);
        $archiveB = Arsip::factory()->create([
            'nomor' => 'ARS-STAF-B',
            'nama' => 'Arsip Milik Staf B',
            'kategori_arsip_id' => $category->id,
            'dprd_member_id' => $memberB->id,
        ]);

        $this->actingAs($staffA)
            ->get(route('arsip.index'))
            ->assertOk()
            ->assertSee($archiveA->nama)
            ->assertDontSee($archiveB->nama);

        $this->actingAs($staffA)
            ->get(route('anggota.arsip', $memberA))
            ->assertOk()
            ->assertSee($memberA->nama_lengkap)
            ->assertDontSee($memberB->nama_lengkap);

        $this->actingAs($staffA)
            ->get(route('anggota.arsip', $memberB))
            ->assertForbidden();

        $this->actingAs($staffA)
            ->get(route('arsip.edit', $archiveB))
            ->assertForbidden();

        $this->actingAs($staffA)
            ->put(route('arsip.update', $archiveB), $this->payload($memberB, $category))
            ->assertForbidden();

        $this->actingAs($staffA)
            ->delete(route('arsip.destroy', $archiveB))
            ->assertForbidden();

        $this->actingAs($staffA)
            ->get(route('arsip.download', $archiveB))
            ->assertForbidden();

        $this->actingAs($staffA)
            ->get(route('arsip.create'))
            ->assertOk()
            ->assertSee($memberA->nama_lengkap)
            ->assertDontSee($memberB->nama_lengkap);

        $this->actingAs($staffA)
            ->get(route('arsip.create', ['member' => $memberB->id]))
            ->assertNotFound();

        $this->actingAs($staffA)
            ->post(route('arsip.store'), $this->payload($memberB, $category, [
                'nomor' => 'ARS-STAF-LAIN',
            ]))
            ->assertForbidden();

        $this->assertDatabaseMissing('arsips', ['nomor' => 'ARS-STAF-LAIN']);

        $this->actingAs($staffA)
            ->post(route('arsip.store'), $this->payload($memberA, $category, [
                'nomor' => 'ARS-STAF-SENDIRI',
                'nama' => 'Arsip Baru Staf A',
            ]))
            ->assertRedirect(route('arsip.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('arsips', [
            'nomor' => 'ARS-STAF-SENDIRI',
            'dprd_member_id' => $memberA->id,
            'uploaded_by' => $staffA->id,
        ]);

        $this->actingAs($staffA)
            ->get(route('arsip.edit', $archiveA))
            ->assertOk();

        $this->actingAs($staffB)
            ->get(route('arsip.edit', $archiveA))
            ->assertForbidden();
    }

    public function test_staff_archive_creation_without_member_is_forced_to_assigned_member(): void
    {
        $member = DprdMember::factory()->create();
        $staff = User::factory()->staff($member)->create();
        $category = KategoriArsip::factory()->create();

        $payload = $this->payload($member, $category, [
            'nomor' => 'ARS-TANPA-MEMBER',
            'nama' => 'Arsip Tanpa Pilihan Anggota',
        ]);
        unset($payload['dprd_member_id']);

        $this->actingAs($staff)
            ->post(route('arsip.store'), $payload)
            ->assertRedirect(route('arsip.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('arsips', [
            'nomor' => 'ARS-TANPA-MEMBER',
            'dprd_member_id' => $member->id,
            'uploaded_by' => $staff->id,
        ]);
    }

    public function test_archive_download_is_authorized_and_scoped(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('archives/2026/10/dokumen-uji.pdf', 'isi dokumen');

        $memberA = DprdMember::factory()->create();
        $memberB = DprdMember::factory()->create();
        $staffA = User::factory()->staff($memberA)->create();
        $staffB = User::factory()->staff($memberB)->create();
        $category = KategoriArsip::factory()->create();

        $archiveA = Arsip::factory()->create([
            'nomor' => 'ARS-UNDUH-A',
            'kategori_arsip_id' => $category->id,
            'dprd_member_id' => $memberA->id,
            'file_path' => 'archives/2026/10/dokumen-uji.pdf',
            'file_name' => 'dokumen-uji.pdf',
        ]);

        $this->actingAs($staffA)
            ->get(route('arsip.download', $archiveA))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=dokumen-uji.pdf');

        $this->actingAs($staffB)
            ->get(route('arsip.download', $archiveA))
            ->assertForbidden();
    }

    public function test_archive_download_returns_error_when_file_is_missing(): void
    {
        Storage::fake('local');

        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $category = KategoriArsip::factory()->create();
        $archive = Arsip::factory()->create([
            'kategori_arsip_id' => $category->id,
            'dprd_member_id' => $member->id,
            'file_path' => 'archives/tidak-ada.pdf',
            'file_name' => 'tidak-ada.pdf',
        ]);

        $this->actingAs($admin)
            ->from(route('anggota.arsip', $member))
            ->get(route('arsip.download', $archive))
            ->assertRedirect(route('anggota.arsip', $member))
            ->assertSessionHas('error');
    }
}

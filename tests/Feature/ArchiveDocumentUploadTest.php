<?php

namespace Tests\Feature;

use App\Models\ArchiveFolder;
use App\Models\ArchiveYear;
use App\Models\Arsip;
use App\Models\DprdMember;
use App\Models\KategoriArsip;
use App\Models\User;
use App\Support\ArchiveFileStorage;
use Database\Seeders\KategoriArsipSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArchiveDocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        return User::factory()->administrator()->create([
            'name' => 'Super Administrator Arsip',
        ]);
    }

    private function setupHierarchy(): array
    {
        $this->seed(KategoriArsipSeeder::class);

        $admin = $this->administrator();
        $member = DprdMember::factory()->create([
            'nama_lengkap' => 'Ir. H. Syahrul, M.T.',
            'kode' => 'SYH',
        ]);
        $category = KategoriArsip::query()->where('is_active', true)->firstOrFail();
        $year = ArchiveYear::query()->create([
            'dprd_member_id' => $member->id,
            'kategori_arsip_id' => $category->id,
            'tahun' => 2026,
            'keterangan' => 'Tahun anggaran 2026',
        ]);
        $folder = ArchiveFolder::query()->create([
            'archive_year_id' => $year->id,
            'nama' => 'Hasil Reses Masa Sidang I',
            'deskripsi' => 'Folder dokumen reses.',
        ]);

        return [$admin, $member, $category, $year, $folder];
    }

    public function test_can_upload_small_pdf_and_download_it(): void
    {
        Storage::fake('local');
        [$admin, $member, $category, $year, $folder] = $this->setupHierarchy();

        $file = UploadedFile::fake()->create('dokumen-kecil.pdf', 500, 'application/pdf');

        $response = $this->actingAs($admin)
            ->post(route('anggota.arsip.dokumen.store', [$member, $category, $year, $folder]), [
                'nama' => 'Laporan Hasil Reses Kecil',
                'jenis' => Arsip::JENIS_MASUK,
                'tanggal' => '2026-03-15',
                'deskripsi' => 'Dokumen uji PDF berukuran kecil.',
                'file' => $file,
            ]);

        $response->assertRedirect(route('anggota.arsip.dokumen', [$member, $category, $year, $folder]))
            ->assertSessionHas('success');

        $document = Arsip::query()->where('nama', 'Laporan Hasil Reses Kecil')->firstOrFail();
        $this->assertSame($member->id, $document->dprd_member_id);
        $this->assertSame($category->id, $document->kategori_arsip_id);
        $this->assertSame($folder->id, $document->archive_folder_id);
        $this->assertSame('dokumen-kecil.pdf', $document->file_name);
        $this->assertStringStartsWith('archives/', $document->file_path);
        Storage::disk('local')->assertExists($document->file_path);

        // Unduh berkas
        $downloadResponse = $this->actingAs($admin)
            ->get(route('anggota.arsip.dokumen.download', [$member, $category, $year, $folder, $document]));

        $downloadResponse->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=dokumen-kecil.pdf');
    }

    public function test_can_upload_pdf_larger_than_10_mb(): void
    {
        Storage::fake('local');
        [$admin, $member, $category, $year, $folder] = $this->setupHierarchy();

        // 15 MB PDF (15 * 1024 = 15360 KB)
        $file = UploadedFile::fake()->create('dokumen-besar-15mb.pdf', 15360, 'application/pdf');

        $response = $this->actingAs($admin)
            ->post(route('anggota.arsip.dokumen.store', [$member, $category, $year, $folder]), [
                'nama' => 'Laporan Dokumen 15 MB',
                'jenis' => Arsip::JENIS_MASUK,
                'tanggal' => '2026-03-20',
                'deskripsi' => 'Dokumen PDF yang ukurannya melebihi batas lama 10 MB.',
                'file' => $file,
            ]);

        $response->assertRedirect(route('anggota.arsip.dokumen', [$member, $category, $year, $folder]))
            ->assertSessionHas('success');

        $document = Arsip::query()->where('nama', 'Laporan Dokumen 15 MB')->firstOrFail();
        $this->assertGreaterThan(10 * 1024 * 1024, $document->file_size);
        $this->assertStringContainsString('MB', $document->file_size_label);
        Storage::disk('local')->assertExists($document->file_path);
    }

    public function test_can_upload_docx_file(): void
    {
        Storage::fake('local');
        [$admin, $member, $category, $year, $folder] = $this->setupHierarchy();

        $file = UploadedFile::fake()->create(
            'notulen-sidang.docx',
            2048,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        );

        $response = $this->actingAs($admin)
            ->post(route('anggota.arsip.dokumen.store', [$member, $category, $year, $folder]), [
                'nama' => 'Notulen Sidang DOCX',
                'jenis' => Arsip::JENIS_INTERNAL,
                'tanggal' => '2026-04-01',
                'file' => $file,
            ]);

        $response->assertRedirect(route('anggota.arsip.dokumen', [$member, $category, $year, $folder]))
            ->assertSessionHas('success');

        $document = Arsip::query()->where('nama', 'Notulen Sidang DOCX')->firstOrFail();
        $this->assertSame('notulen-sidang.docx', $document->file_name);
        $this->assertStringEndsWith('.docx', $document->file_path);
        Storage::disk('local')->assertExists($document->file_path);
    }

    public function test_can_upload_xlsx_file(): void
    {
        Storage::fake('local');
        [$admin, $member, $category, $year, $folder] = $this->setupHierarchy();

        $file = UploadedFile::fake()->create(
            'anggaran-reses.xlsx',
            3500,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

        $response = $this->actingAs($admin)
            ->post(route('anggota.arsip.dokumen.store', [$member, $category, $year, $folder]), [
                'nama' => 'Data Anggaran Reses XLSX',
                'jenis' => Arsip::JENIS_KELUAR,
                'tanggal' => '2026-04-02',
                'file' => $file,
            ]);

        $response->assertRedirect(route('anggota.arsip.dokumen', [$member, $category, $year, $folder]))
            ->assertSessionHas('success');

        $document = Arsip::query()->where('nama', 'Data Anggaran Reses XLSX')->firstOrFail();
        $this->assertSame('anggaran-reses.xlsx', $document->file_name);
        $this->assertStringEndsWith('.xlsx', $document->file_path);
        Storage::disk('local')->assertExists($document->file_path);
    }

    public function test_can_upload_pptx_file(): void
    {
        Storage::fake('local');
        [$admin, $member, $category, $year, $folder] = $this->setupHierarchy();

        $file = UploadedFile::fake()->create(
            'paparan-pokir.pptx',
            8000,
            'application/vnd.openxmlformats-officedocument.presentationml.presentation'
        );

        $response = $this->actingAs($admin)
            ->post(route('anggota.arsip.dokumen.store', [$member, $category, $year, $folder]), [
                'nama' => 'Paparan Pokir Anggota PPTX',
                'jenis' => Arsip::JENIS_INTERNAL,
                'tanggal' => '2026-04-05',
                'file' => $file,
            ]);

        $response->assertRedirect(route('anggota.arsip.dokumen', [$member, $category, $year, $folder]))
            ->assertSessionHas('success');

        $document = Arsip::query()->where('nama', 'Paparan Pokir Anggota PPTX')->firstOrFail();
        $this->assertSame('paparan-pokir.pptx', $document->file_name);
        $this->assertStringEndsWith('.pptx', $document->file_path);
        Storage::disk('local')->assertExists($document->file_path);
    }

    public function test_can_upload_images_and_legacy_office_documents(): void
    {
        Storage::fake('local');
        [$admin, $member, $category, $year, $folder] = $this->setupHierarchy();

        $formats = [
            ['nama' => 'Dokumen DOC', 'file' => 'surat.doc', 'mime' => 'application/msword'],
            ['nama' => 'Dokumen XLS', 'file' => 'tabel.xls', 'mime' => 'application/vnd.ms-excel'],
            ['nama' => 'Dokumen PPT', 'file' => 'slide.ppt', 'mime' => 'application/vnd.ms-powerpoint'],
            ['nama' => 'Foto Kegiatan JPG', 'file' => 'reses.jpg', 'mime' => 'image/jpeg'],
            ['nama' => 'Bagan PNG', 'file' => 'bagan.png', 'mime' => 'image/png'],
        ];

        foreach ($formats as $item) {
            $file = UploadedFile::fake()->create($item['file'], 600, $item['mime']);

            $response = $this->actingAs($admin)
                ->post(route('anggota.arsip.dokumen.store', [$member, $category, $year, $folder]), [
                    'nama' => $item['nama'],
                    'jenis' => Arsip::JENIS_MASUK,
                    'file' => $file,
                ]);

            $response->assertRedirect(route('anggota.arsip.dokumen', [$member, $category, $year, $folder]))
                ->assertSessionHas('success');

            $doc = Arsip::query()->where('nama', $item['nama'])->firstOrFail();
            Storage::disk('local')->assertExists($doc->file_path);
        }
    }

    public function test_unsupported_format_is_rejected_with_clear_error(): void
    {
        Storage::fake('local');
        [$admin, $member, $category, $year, $folder] = $this->setupHierarchy();

        $unsupported = [
            'program.exe' => 'application/x-msdownload',
            'script.sh' => 'application/x-sh',
            'installer.msi' => 'application/octet-stream',
            'archive.zip' => 'application/zip',
        ];

        foreach ($unsupported as $filename => $mime) {
            $file = UploadedFile::fake()->create($filename, 200, $mime);

            $response = $this->actingAs($admin)
                ->from(route('anggota.arsip.dokumen.create', [$member, $category, $year, $folder]))
                ->post(route('anggota.arsip.dokumen.store', [$member, $category, $year, $folder]), [
                    'nama' => 'Dokumen Uji '.$filename,
                    'jenis' => Arsip::JENIS_MASUK,
                    'file' => $file,
                ]);

            $response->assertSessionHasErrors('file');
        }
    }

    public function test_double_extension_attack_is_rejected(): void
    {
        Storage::fake('local');
        [$admin, $member, $category, $year, $folder] = $this->setupHierarchy();

        $file = UploadedFile::fake()->create('dokumen.php.pdf', 100, 'application/pdf');

        $response = $this->actingAs($admin)
            ->post(route('anggota.arsip.dokumen.store', [$member, $category, $year, $folder]), [
                'nama' => 'Dokumen Ekstensi Ganda',
                'jenis' => Arsip::JENIS_MASUK,
                'file' => $file,
            ]);

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseCount('arsips', 0);
    }

    public function test_file_exceeding_configured_limit_is_rejected(): void
    {
        Storage::fake('local');
        [$admin, $member, $category, $year, $folder] = $this->setupHierarchy();

        // Melebihi kapasitas maksimal saat ini
        $maxKb = ArchiveFileStorage::maxUploadKilobytes();
        $file = UploadedFile::fake()->create('terlalu-besar.pdf', $maxKb + 10, 'application/pdf');

        $response = $this->actingAs($admin)
            ->post(route('anggota.arsip.dokumen.store', [$member, $category, $year, $folder]), [
                'nama' => 'Dokumen Melebihi Batas',
                'jenis' => Arsip::JENIS_MASUK,
                'file' => $file,
            ]);

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseCount('arsips', 0);
    }

    public function test_capacity_is_dynamically_configurable_via_config(): void
    {
        // Ubah kapasitas ke 50 MB secara dinamis
        config(['archives.max_upload_mb' => 50]);

        $this->assertSame(50, ArchiveFileStorage::maxUploadMegabytes());
        $this->assertSame(51200, ArchiveFileStorage::maxUploadKilobytes());
        $this->assertSame(50 * 1024 * 1024, ArchiveFileStorage::maxUploadBytes());

        // Kembalikan ke 100 MB
        config(['archives.max_upload_mb' => 100]);
        $this->assertSame(100, ArchiveFileStorage::maxUploadMegabytes());
    }

    public function test_general_archive_can_upload_pdf_over_10_mb(): void
    {
        Storage::fake('local');
        $this->seed(KategoriArsipSeeder::class);
        $admin = $this->administrator();
        $category = KategoriArsip::query()->where('is_active', true)->firstOrFail();

        $file = UploadedFile::fake()->create('perda-dokumen-18mb.pdf', 18432, 'application/pdf');

        $response = $this->actingAs($admin)
            ->post(route('arsip.store'), [
                'nomor' => 'ARS/DPRD/2026/001',
                'nama' => 'Arsip Perda 18 MB',
                'kategori_arsip_id' => $category->id,
                'jenis' => Arsip::JENIS_MASUK,
                'status' => Arsip::STATUS_TERVERIFIKASI,
                'tanggal' => '2026-04-10',
                'file' => $file,
            ]);

        $response->assertRedirect(route('arsip.index'))
            ->assertSessionHas('success');

        $archive = Arsip::query()->where('nomor', 'ARS/DPRD/2026/001')->firstOrFail();
        $this->assertSame('perda-dokumen-18mb.pdf', $archive->file_name);
        $this->assertGreaterThan(10 * 1024 * 1024, $archive->file_size);
        Storage::disk('local')->assertExists($archive->file_path);
    }

    public function test_upload_page_displays_dynamic_upload_size_and_formats(): void
    {
        [$admin, $member, $category, $year, $folder] = $this->setupHierarchy();

        $response = $this->actingAs($admin)
            ->get(route('anggota.arsip.dokumen.create', [$member, $category, $year, $folder]));

        $response->assertOk()
            ->assertDontSee('Maksimal 10 MB')
            ->assertSee('Maksimal '.ArchiveFileStorage::maxUploadMegabytes().' MB')
            ->assertSee('Pilih Berkas')
            ->assertSee('Tarik &amp; letakkan berkas di sini', false)
            ->assertSee('PDF')
            ->assertSee('DOC / DOCX')
            ->assertSee('XLS / XLSX')
            ->assertSee('PPT / PPTX');
    }
}

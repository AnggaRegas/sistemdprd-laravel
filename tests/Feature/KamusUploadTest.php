<?php

namespace Tests\Feature;

use App\Models\KamusAcuanEntri;
use App\Models\KamusAcuanRiwayat;
use App\Models\KamusAcuanVersi;
use App\Models\User;
use App\Services\KamusAcuanService;
use App\Services\KamusUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use ZipArchive;

class KamusUploadTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        return User::factory()->administrator()->create(['name' => 'Super Admin Unggah']);
    }

    private function version(User $admin): KamusAcuanVersi
    {
        return app(KamusAcuanService::class)->createVersion([
            'tahun' => 2026,
            'nama' => 'Kamus Acuan 2026',
            'is_active' => true,
        ], $admin);
    }

    private function csv(string $content, string $name = 'kamus-usulan.csv'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $content);
    }

    /**
     * Bangun XLSX minimal (inline string) tanpa pustaka tambahan.
     */
    private function xlsx(array $rows, string $name = 'kamus-usulan.xlsx'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'kamus_xlsx_').'.xlsx';

        $sheetRows = '';

        foreach ($rows as $rowIndex => $row) {
            $cells = '';

            foreach (array_values($row) as $columnIndex => $value) {
                $reference = chr(65 + $columnIndex).($rowIndex + 1);
                $cells .= '<c r="'.$reference.'" t="inlineStr"><is><t>'
                    .htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8')
                    .'</t></is></c>';
            }

            $sheetRows .= '<row r="'.($rowIndex + 1).'">'.$cells.'</row>';
        }

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true);

        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            .' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="Kamus" sheetId="1" r:id="rId1"/></sheets></workbook>');

        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet"'
            .' Target="worksheets/sheet1.xml"/></Relationships>');

        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetData>'.$sheetRows.'</sheetData></worksheet>');

        $zip->close();

        return new UploadedFile(
            $path,
            $name,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }

    private function previewFor(KamusAcuanVersi $version): array
    {
        $preview = $this->app['session']->get(app(KamusUploadService::class)->sessionKey($version->id));

        $this->assertIsArray($preview, 'Sesi pratinjau unggahan tidak ditemukan.');

        return $preview;
    }

    public function test_upload_page_shows_real_limits_and_format_support(): void
    {
        $admin = $this->administrator();
        $version = $this->version($admin);

        $this->actingAs($admin)
            ->get(route('kamus.versi.unggah', $version))
            ->assertOk()
            ->assertSee('Unggah Kamus Acuan')
            ->assertSee('Batas Unggah Aktual')
            ->assertSee('Batas efektif')
            ->assertSee('upload_max_filesize')
            ->assertSee('post_max_size')
            ->assertSee('memory_limit')
            ->assertSee('max_execution_time')
            ->assertSee('naikkan nilai PHP/web server');
    }

    public function test_csv_upload_is_previewed_without_changing_data_then_applied(): void
    {
        $admin = $this->administrator();
        $version = $this->version($admin);

        $csv = "kode,klasifikasi,uraian,kata kunci,prioritas,komisi\n"
            ."IMP-0001,Infrastruktur Jalan,Perbaikan jalan rusak,\"jalan rusak|3; drainase|2\",Tinggi,\n"
            ."IMP-0002,Pendidikan,Peningkatan mutu sekolah negeri,\"sekolah|3\",Sedang,\n";

        // Pratinjau tidak mengubah data.
        $this->actingAs($admin)
            ->post(route('kamus.versi.unggah.preview', $version), ['file' => $this->csv($csv)])
            ->assertRedirect(route('kamus.versi.unggah.pratinjau', $version))
            ->assertSessionHasNoErrors();

        $this->assertSame(0, KamusAcuanEntri::query()->count());

        $preview = $this->previewFor($version);
        $this->assertSame(2, $preview['total']);
        $this->assertTrue($preview['valid']);
        $this->assertSame('csv', $preview['format']);

        $this->actingAs($admin)
            ->get(route('kamus.versi.unggah.pratinjau', $version))
            ->assertOk()
            ->assertSee('Pratinjau Unggahan')
            ->assertSee('Pemetaan Kolom')
            ->assertSee('IMP-0001')
            ->assertSee('IMP-0002')
            ->assertSee('Siap diterapkan');

        $this->actingAs($admin)
            ->post(route('kamus.versi.unggah.apply', $version), [
                'mode' => 'gabung',
                'token' => $preview['token'],
            ])
            ->assertRedirect(route('kamus.index', ['versi' => $version->id]))
            ->assertSessionHas('success');

        $this->assertSame(2, KamusAcuanEntri::query()->where('kamus_acuan_versi_id', $version->id)->count());

        $entry = KamusAcuanEntri::query()->where('kode', 'IMP-0001')->firstOrFail();
        $this->assertSame('Infrastruktur Jalan', $entry->klasifikasi);
        $this->assertNotEmpty($entry->kata_kunci);
        $this->assertSame('Tinggi', $entry->prioritas);

        $this->assertSame(
            1,
            KamusAcuanRiwayat::query()
                ->where('kamus_acuan_versi_id', $version->id)
                ->where('aksi', 'impor')
                ->count()
        );

        // Sesi pratinjau dibersihkan setelah diterapkan.
        $this->assertNull($this->app['session']->get(app(KamusUploadService::class)->sessionKey($version->id)));
    }

    public function test_xlsx_upload_is_read_mapped_and_applied(): void
    {
        $admin = $this->administrator();
        $version = $this->version($admin);

        $file = $this->xlsx([
            ['kode', 'klasifikasi', 'uraian', 'kata kunci'],
            ['IMP-1001', 'Kesehatan', 'Peningkatan layanan puskesmas', 'puskesmas|3'],
            ['IMP-1002', 'Lingkungan', 'Penanganan sampah pasar', 'sampah|3'],
        ]);

        $this->actingAs($admin)
            ->post(route('kamus.versi.unggah.preview', $version), ['file' => $file])
            ->assertRedirect(route('kamus.versi.unggah.pratinjau', $version))
            ->assertSessionHasNoErrors();

        $preview = $this->previewFor($version);
        $this->assertSame('xlsx', $preview['format']);
        $this->assertSame(2, $preview['total']);
        $this->assertTrue($preview['valid']);

        $this->actingAs($admin)
            ->post(route('kamus.versi.unggah.apply', $version), [
                'mode' => 'gabung',
                'token' => $preview['token'],
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('kamus_acuan_entri', [
            'kamus_acuan_versi_id' => $version->id,
            'kode' => 'IMP-1001',
            'klasifikasi' => 'Kesehatan',
        ]);
    }

    public function test_invalid_file_does_not_change_existing_kamus_data(): void
    {
        $admin = $this->administrator();
        $version = $this->version($admin);

        $existing = KamusAcuanEntri::factory()->create([
            'kamus_acuan_versi_id' => $version->id,
            'kode' => 'KOM-LAMA',
            'klasifikasi' => 'Data lama yang harus tetap utuh',
        ]);

        $this->actingAs($admin)
            ->post(route('kamus.versi.unggah.preview', $version), [
                'file' => $this->csv("foo,bar\n1,2\n"),
            ])
            ->assertSessionHasErrors('file');

        $this->assertSame(1, KamusAcuanEntri::query()->where('kamus_acuan_versi_id', $version->id)->count());
        $this->assertDatabaseHas('kamus_acuan_entri', [
            'id' => $existing->id,
            'kode' => 'KOM-LAMA',
        ]);
        $this->assertSame(0, KamusAcuanRiwayat::query()->where('aksi', 'impor')->count());
    }

    public function test_preview_with_duplicate_codes_cannot_be_applied(): void
    {
        $admin = $this->administrator();
        $version = $this->version($admin);

        $csv = "kode,klasifikasi,kata kunci\n"
            ."DUP-01,Infrastruktur Jalan,jalan|3\n"
            ."DUP-01,Pendidikan,sekolah|3\n";

        $this->actingAs($admin)
            ->post(route('kamus.versi.unggah.preview', $version), ['file' => $this->csv($csv)])
            ->assertRedirect(route('kamus.versi.unggah.pratinjau', $version));

        $preview = $this->previewFor($version);
        $this->assertFalse($preview['valid']);
        $this->assertNotEmpty($preview['errors']);

        $this->actingAs($admin)
            ->post(route('kamus.versi.unggah.apply', $version), [
                'mode' => 'gabung',
                'token' => $preview['token'],
            ])
            ->assertSessionHasErrors('file');

        $this->assertSame(0, KamusAcuanEntri::query()->count());
    }

    public function test_stale_preview_token_is_rejected(): void
    {
        $admin = $this->administrator();
        $version = $this->version($admin);

        $this->actingAs($admin)
            ->post(route('kamus.versi.unggah.apply', $version), [
                'mode' => 'gabung',
                'token' => 'token-palsu',
            ])
            ->assertRedirect(route('kamus.versi.unggah', $version))
            ->assertSessionHas('error');

        $this->assertSame(0, KamusAcuanEntri::query()->count());
    }

    public function test_replace_mode_soft_deletes_entries_missing_from_new_file(): void
    {
        $admin = $this->administrator();
        $version = $this->version($admin);

        $old = KamusAcuanEntri::factory()->create([
            'kamus_acuan_versi_id' => $version->id,
            'kode' => 'OLD-01',
            'klasifikasi' => 'Entri lama',
        ]);

        $csv = "kode,klasifikasi,kata kunci\nNEW-01,Infrastruktur Baru,jalan baru|3\n";

        $this->actingAs($admin)
            ->post(route('kamus.versi.unggah.preview', $version), ['file' => $this->csv($csv)]);

        $preview = $this->previewFor($version);

        $this->actingAs($admin)
            ->post(route('kamus.versi.unggah.apply', $version), [
                'mode' => 'ganti',
                'token' => $preview['token'],
            ])
            ->assertSessionHas('success');

        $this->assertSoftDeleted('kamus_acuan_entri', ['id' => $old->id]);
        $this->assertDatabaseHas('kamus_acuan_entri', [
            'kamus_acuan_versi_id' => $version->id,
            'kode' => 'NEW-01',
        ]);
    }

    public function test_binary_xls_without_reader_gives_clear_message_and_no_change(): void
    {
        $admin = $this->administrator();
        $version = $this->version($admin);

        $file = UploadedFile::fake()->createWithContent('kamus-lama.xls', "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1 bukan xls valid");

        $this->actingAs($admin)
            ->post(route('kamus.versi.unggah.preview', $version), ['file' => $file])
            ->assertSessionHasErrors('file');

        $this->assertSame(0, KamusAcuanEntri::query()->count());
    }

    public function test_text_based_pdf_is_read_and_previewed(): void
    {
        $admin = $this->administrator();
        $version = $this->version($admin);

        $streamOne = "BT (kode\tklasifikasi\turaian) Tj ET";
        $streamTwo = "BT (IMP-2001\tKesehatan\tPeningkatan layanan puskesmas) Tj ET";

        $pdf = "%PDF-1.4\n"
            ."1 0 obj\n<< /Length ".strlen($streamOne)." >>\nstream\n".$streamOne."\nendstream\nendobj\n"
            ."2 0 obj\n<< /Length ".strlen($streamTwo)." >>\nstream\n".$streamTwo."\nendstream\nendobj\n"
            ."trailer\n<< /Root 1 0 R >>\n%%EOF";

        $path = tempnam(sys_get_temp_dir(), 'kamus_pdf_').'.pdf';
        file_put_contents($path, $pdf);

        $file = new UploadedFile($path, 'kamus-usulan.pdf', 'application/pdf', null, true);

        $response = $this->actingAs($admin)
            ->from(route('kamus.versi.unggah', $version))
            ->post(route('kamus.versi.unggah.preview', $version), ['file' => $file]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('kamus.versi.unggah.pratinjau', $version));

        $preview = $this->previewFor($version);

        $this->assertSame('pdf', $preview['format']);
        $this->assertSame(1, $preview['total']);
        $this->assertSame('IMP-2001', $preview['rows'][0]['kode']);
        $this->assertNotEmpty($preview['warnings']);
        $this->assertSame(0, KamusAcuanEntri::query()->count());
    }

    public function test_staff_cannot_upload_kamus_file(): void
    {
        $admin = $this->administrator();
        $version = $this->version($admin);
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)
            ->get(route('kamus.versi.unggah', $version))
            ->assertForbidden();

        $this->actingAs($staff)
            ->post(route('kamus.versi.unggah.preview', $version), [
                'file' => $this->csv("kode,klasifikasi\nX-1,Uji\n"),
            ])
            ->assertForbidden();

        $this->assertSame(0, KamusAcuanEntri::query()->count());
    }
}

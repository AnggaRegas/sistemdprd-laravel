<?php

namespace Tests\Feature;

use App\Models\DprdCommission;
use App\Models\RdpDocument;
use App\Models\RdpMeeting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RdpDocumentTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        return User::factory()->administrator()->create();
    }

    private function documentPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Risalah RDP APBD',
            'category' => RdpDocument::CATEGORY_RISALAH,
            'document_date' => '2026-10-20',
            'description' => 'Risalah resmi rapat.',
        ], $overrides);
    }

    public function test_administrator_can_upload_document_to_private_storage(): void
    {
        Storage::fake('local');

        $admin = $this->administrator();
        $commission = DprdCommission::factory()->create();
        $meeting = RdpMeeting::factory()->forCommission($commission)->create();

        $file = UploadedFile::fake()->create('risalah rapat.pdf', 120, 'application/pdf');

        $this->actingAs($admin)
            ->post(route('komisi.rdp.documents.store', [$commission, $meeting]), $this->documentPayload([
                'file' => $file,
            ]))
            ->assertRedirect(route('komisi.rdp.show', [$commission, $meeting]))
            ->assertSessionHas('success');

        $document = RdpDocument::query()->firstOrFail();

        $this->assertSame($meeting->id, $document->rdp_meeting_id);
        $this->assertSame('Risalah RDP APBD', $document->title);
        $this->assertSame(RdpDocument::CATEGORY_RISALAH, $document->category);
        $this->assertSame($admin->id, $document->uploaded_by);
        $this->assertSame('risalah rapat.pdf', $document->original_name);
        $this->assertStringStartsWith('rdp-documents/'.$meeting->id.'/', $document->file_path);
        $this->assertStringNotContainsString('public', $document->file_path);
        Storage::disk('local')->assertExists($document->file_path);
    }

    public function test_document_upload_rejects_invalid_mime_type(): void
    {
        Storage::fake('local');

        $admin = $this->administrator();
        $commission = DprdCommission::factory()->create();
        $meeting = RdpMeeting::factory()->forCommission($commission)->create();

        $file = UploadedFile::fake()->create('berbahaya.exe', 10, 'application/x-msdownload');

        $this->actingAs($admin)
            ->post(route('komisi.rdp.documents.store', [$commission, $meeting]), $this->documentPayload([
                'file' => $file,
            ]))
            ->assertSessionHasErrors('file', null, 'documentUpload');

        $this->assertDatabaseCount('rdp_documents', 0);
    }

    public function test_document_upload_rejects_file_larger_than_limit(): void
    {
        Storage::fake('local');

        $admin = $this->administrator();
        $commission = DprdCommission::factory()->create();
        $meeting = RdpMeeting::factory()->forCommission($commission)->create();

        $file = UploadedFile::fake()->create('dokumen-besar.pdf', 10241, 'application/pdf');

        $this->actingAs($admin)
            ->post(route('komisi.rdp.documents.store', [$commission, $meeting]), $this->documentPayload([
                'file' => $file,
            ]))
            ->assertSessionHasErrors('file', null, 'documentUpload');

        $this->assertDatabaseCount('rdp_documents', 0);
    }

    public function test_document_upload_validates_category_and_title(): void
    {
        Storage::fake('local');

        $admin = $this->administrator();
        $commission = DprdCommission::factory()->create();
        $meeting = RdpMeeting::factory()->forCommission($commission)->create();

        $file = UploadedFile::fake()->create('dokumen.pdf', 10, 'application/pdf');

        $this->actingAs($admin)
            ->post(route('komisi.rdp.documents.store', [$commission, $meeting]), [
                'title' => '',
                'category' => 'kategori-tidak-ada',
                'file' => $file,
            ])
            ->assertSessionHasErrors(['title', 'category'], null, 'documentUpload');
    }

    public function test_administrator_can_download_document_through_authorized_route(): void
    {
        Storage::fake('local');

        $admin = $this->administrator();
        $commission = DprdCommission::factory()->create();
        $meeting = RdpMeeting::factory()->forCommission($commission)->create();
        $document = RdpDocument::factory()->forMeeting($meeting)->create([
            'file_path' => 'rdp-documents/'.$meeting->id.'/risalah.pdf',
            'original_name' => 'risalah.pdf',
            'mime_type' => 'application/pdf',
        ]);

        Storage::disk('local')->put($document->file_path, 'isi dokumen rahasia');

        $this->actingAs($admin)
            ->get(route('komisi.rdp.documents.download', [$commission, $meeting, $document]))
            ->assertOk()
            ->assertDownload('risalah.pdf');
    }

    public function test_document_download_is_scoped_to_meeting_and_commission(): void
    {
        Storage::fake('local');

        $admin = $this->administrator();
        $commissionA = DprdCommission::factory()->create();
        $commissionB = DprdCommission::factory()->create();
        $meetingA = RdpMeeting::factory()->forCommission($commissionA)->create();
        $meetingB = RdpMeeting::factory()->forCommission($commissionB)->create();
        $document = RdpDocument::factory()->forMeeting($meetingB)->create();

        Storage::disk('local')->put($document->file_path, 'isi dokumen');

        $this->actingAs($admin)
            ->get(route('komisi.rdp.documents.download', [$commissionA, $meetingA, $document]))
            ->assertNotFound();

        $this->actingAs($admin)
            ->get(route('komisi.rdp.documents.download', [$commissionA, $meetingB, $document]))
            ->assertNotFound();
    }

    public function test_staff_cannot_upload_download_or_delete_documents(): void
    {
        Storage::fake('local');

        $staff = User::factory()->staff()->create();
        $commission = DprdCommission::factory()->create();
        $meeting = RdpMeeting::factory()->forCommission($commission)->create();
        $document = RdpDocument::factory()->forMeeting($meeting)->create();

        $file = UploadedFile::fake()->create('dokumen.pdf', 10, 'application/pdf');

        $this->actingAs($staff)
            ->post(route('komisi.rdp.documents.store', [$commission, $meeting]), $this->documentPayload([
                'file' => $file,
            ]))
            ->assertForbidden();

        $this->actingAs($staff)
            ->get(route('komisi.rdp.documents.download', [$commission, $meeting, $document]))
            ->assertForbidden();

        $this->actingAs($staff)
            ->delete(route('komisi.rdp.documents.destroy', [$commission, $meeting, $document]))
            ->assertForbidden();

        $this->assertDatabaseCount('rdp_documents', 1);
    }

    public function test_document_is_soft_deleted_but_physical_file_is_kept_for_audit(): void
    {
        Storage::fake('local');

        $admin = $this->administrator();
        $commission = DprdCommission::factory()->create();
        $meeting = RdpMeeting::factory()->forCommission($commission)->create();
        $document = RdpDocument::factory()->forMeeting($meeting)->create([
            'file_path' => 'rdp-documents/'.$meeting->id.'/arsip.pdf',
        ]);

        Storage::disk('local')->put($document->file_path, 'isi arsip');

        $this->actingAs($admin)
            ->delete(route('komisi.rdp.documents.destroy', [$commission, $meeting, $document]))
            ->assertRedirect(route('komisi.rdp.show', [$commission, $meeting]))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('rdp_documents', ['id' => $document->id]);
        Storage::disk('local')->assertExists($document->file_path);
    }
}

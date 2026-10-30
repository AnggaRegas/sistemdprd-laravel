<?php

namespace Tests\Feature;

use App\Models\DprdCommission;
use App\Models\RdpMeeting;
use App\Models\User;
use Database\Seeders\KomisiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionArchiveTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        return User::factory()->administrator()->create();
    }

    public function test_komisi_seeder_is_idempotent_and_creates_three_commissions(): void
    {
        $this->seed(KomisiSeeder::class);
        $this->seed(KomisiSeeder::class);

        $this->assertSame(3, DprdCommission::query()->count());

        $this->assertDatabaseHas('dprd_commissions', [
            'slug' => 'komisi-1',
            'field' => 'Pemerintahan, Hukum, dan Ketertiban',
        ]);
        $this->assertDatabaseHas('dprd_commissions', [
            'slug' => 'komisi-2',
            'field' => 'Ekonomi, Keuangan, dan Pembangunan',
        ]);
        $this->assertDatabaseHas('dprd_commissions', [
            'slug' => 'komisi-3',
            'field' => 'Kesejahteraan Rakyat dan Pendidikan',
        ]);

        $this->assertSame(0, RdpMeeting::query()->count());

        $this->assertNotEmpty(
            DprdCommission::query()->where('slug', 'komisi-1')->firstOrFail()->classificationKeywords()
        );
    }

    public function test_administrator_can_view_commission_cards(): void
    {
        $admin = $this->administrator();
        $this->seed(KomisiSeeder::class);

        $this->actingAs($admin)
            ->get(route('komisi.index'))
            ->assertOk()
            ->assertSee('Arsip Komisi')
            ->assertSee('Komisi 1')
            ->assertSee('Pemerintahan, Hukum, dan Ketertiban')
            ->assertSee('Buka Folder RDP');
    }

    public function test_administrator_can_create_commission(): void
    {
        $admin = $this->administrator();

        $this->actingAs($admin)->post(route('komisi.store'), [
            'name' => 'Komisi 4',
            'field' => 'Infrastruktur dan Lingkungan',
            'description' => 'Komisi baru untuk pengujian.',
            'is_active' => '1',
        ])->assertRedirect(route('komisi.index'))
            ->assertSessionHas('success');

        $commission = DprdCommission::query()->where('name', 'Komisi 4')->firstOrFail();

        $this->assertSame('komisi-4', $commission->slug);
        $this->assertTrue($commission->is_active);
    }

    public function test_commission_name_must_be_unique(): void
    {
        $admin = $this->administrator();
        DprdCommission::factory()->create(['name' => 'Komisi Unik']);

        $this->actingAs($admin)->post(route('komisi.store'), [
            'name' => 'Komisi Unik',
            'field' => 'Bidang lain',
            'is_active' => '1',
        ])->assertSessionHasErrors('name');
    }

    public function test_administrator_can_update_commission(): void
    {
        $admin = $this->administrator();
        $commission = DprdCommission::factory()->create([
            'name' => 'Komisi Lama',
            'slug' => 'komisi-lama',
        ]);

        $this->actingAs($admin)->put(route('komisi.update', $commission), [
            'name' => 'Komisi Baru',
            'field' => 'Bidang Baru',
            'description' => 'Deskripsi baru.',
            'is_active' => '0',
        ])->assertRedirect(route('komisi.index'));

        $commission->refresh();

        $this->assertSame('Komisi Baru', $commission->name);
        $this->assertSame('komisi-baru', $commission->slug);
        $this->assertFalse($commission->is_active);
    }

    public function test_administrator_can_manage_weighted_classification_keywords(): void
    {
        $admin = $this->administrator();
        $commission = DprdCommission::factory()->create([
            'name' => 'Komisi Kata Kunci',
            'slug' => 'komisi-kata-kunci',
        ]);

        $this->actingAs($admin)
            ->get(route('komisi.edit', $commission))
            ->assertOk()
            ->assertSee('Kata Kunci Klasifikasi')
            ->assertSee('bukan AI');

        $this->actingAs($admin)->put(route('komisi.update', $commission), [
            'name' => 'Komisi Kata Kunci',
            'field' => 'Bidang Kata Kunci',
            'description' => 'Uji pengelolaan kata kunci.',
            'keywords_text' => "jalan|3\nlampu jalan|3\npelayanan publik|2",
            'is_active' => '1',
        ])->assertRedirect(route('komisi.index'))
            ->assertSessionHas('success');

        $commission->refresh();

        $this->assertSame([
            ['term' => 'jalan', 'weight' => 3],
            ['term' => 'lampu jalan', 'weight' => 3],
            ['term' => 'pelayanan publik', 'weight' => 2],
        ], $commission->classificationKeywords());

        $this->actingAs($admin)->put(route('komisi.update', $commission), [
            'name' => 'Komisi Kata Kunci',
            'keywords_text' => "jalan|99",
            'is_active' => '1',
        ])->assertSessionHasErrors('keywords_text');

        $this->assertCount(3, $commission->refresh()->classificationKeywords());
    }

    public function test_administrator_can_create_commission_with_classification_keywords(): void
    {
        $admin = $this->administrator();

        $this->actingAs($admin)->post(route('komisi.store'), [
            'name' => 'Komisi 5',
            'field' => 'Pendidikan dan Kesehatan',
            'keywords_text' => "pendidikan|3\nsekolah|2\npuskesmas|3",
            'is_active' => '1',
        ])->assertRedirect(route('komisi.index'))
            ->assertSessionHas('success');

        $commission = DprdCommission::query()->where('name', 'Komisi 5')->firstOrFail();

        $this->assertSame([
            ['term' => 'pendidikan', 'weight' => 3],
            ['term' => 'sekolah', 'weight' => 2],
            ['term' => 'puskesmas', 'weight' => 3],
        ], $commission->classificationKeywords());
    }

    public function test_commission_without_rdp_can_be_soft_deleted(): void
    {
        $admin = $this->administrator();
        $commission = DprdCommission::factory()->create();

        $this->actingAs($admin)
            ->delete(route('komisi.destroy', $commission))
            ->assertRedirect(route('komisi.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('dprd_commissions', ['id' => $commission->id]);
    }

    public function test_commission_with_rdp_cannot_be_deleted(): void
    {
        $admin = $this->administrator();
        $commission = DprdCommission::factory()->create();
        RdpMeeting::factory()->forCommission($commission)->create();

        $this->actingAs($admin)
            ->from(route('komisi.index'))
            ->delete(route('komisi.destroy', $commission))
            ->assertRedirect(route('komisi.index'))
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted('dprd_commissions', ['id' => $commission->id]);
    }

    public function test_staff_cannot_access_commission_management(): void
    {
        $staff = User::factory()->staff()->create();
        $commission = DprdCommission::factory()->create();

        $this->actingAs($staff)->get(route('komisi.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('komisi.create'))->assertForbidden();
        $this->actingAs($staff)->post(route('komisi.store'), [
            'name' => 'Komisi Liar',
            'is_active' => '1',
        ])->assertForbidden();
        $this->actingAs($staff)
            ->delete(route('komisi.destroy', $commission))
            ->assertForbidden();

        $this->assertDatabaseMissing('dprd_commissions', ['name' => 'Komisi Liar']);
    }
}

<?php

namespace Tests\Feature;

use App\Models\DprdMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffAccountManagementTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        return User::factory()->administrator()->create([
            'name' => 'Administrator Sekretariat',
        ]);
    }

    public function test_administrator_can_view_account_list(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create(['nama_lengkap' => 'Anggota Uji Daftar']);
        $staff = User::factory()->staff($member)->create(['name' => 'Staf Uji Daftar']);

        $this->actingAs($admin)
            ->get(route('akun.index'))
            ->assertOk()
            ->assertSee('Daftar Akun Pengguna')
            ->assertSee('Daftar seluruh akun internal dan wewenang pengarsipan.')
            ->assertSee($admin->name)
            ->assertSee($staff->name)
            ->assertSee('Anggota Uji Daftar');
    }

    public function test_administrator_can_filter_accounts_by_role_and_status(): void
    {
        $admin = $this->administrator();
        $activeStaff = User::factory()->staff()->create(['name' => 'Staf Aktif Filter']);
        $inactiveStaff = User::factory()->staff()->create([
            'name' => 'Staf Nonaktif Filter',
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('akun.index', ['status' => 'inactive']))
            ->assertOk()
            ->assertSee($inactiveStaff->name)
            ->assertDontSee($activeStaff->name);

        $this->actingAs($admin)
            ->get(route('akun.index', ['role' => 'administrator']))
            ->assertOk()
            ->assertSee($admin->name)
            ->assertDontSee($activeStaff->name);
    }

    public function test_administrator_can_create_staff_account_with_forced_password_change(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();

        $response = $this->actingAs($admin)->post(route('akun.store'), [
            'name' => 'Staf Baru Pendamping',
            'username' => 'staf.baru',
            'email' => 'staf.baru@example.test',
            'password' => 'RahasiaKuat123',
            'password_confirmation' => 'RahasiaKuat123',
            'dprd_member_id' => $member->id,
            'is_active' => '1',
            'role' => User::ROLE_ADMINISTRATOR,
        ]);

        $response
            ->assertRedirect(route('akun.index'))
            ->assertSessionHas('success');

        $account = User::query()->where('username', 'staf.baru')->firstOrFail();

        $this->assertSame(User::ROLE_STAF_PENDAMPING, $account->role);
        $this->assertSame($member->id, $account->dprd_member_id);
        $this->assertTrue($account->is_active);
        $this->assertTrue($account->must_change_password);
        $this->assertTrue(Hash::check('RahasiaKuat123', $account->password));
        $this->assertNotSame('RahasiaKuat123', $account->password);
    }

    public function test_account_creation_validates_unique_username_and_email(): void
    {
        $admin = $this->administrator();
        $existing = User::factory()->staff()->create([
            'username' => 'sudah.dipakai',
            'email' => 'sudah@example.test',
        ]);
        $member = DprdMember::factory()->create();

        $this->actingAs($admin)->post(route('akun.store'), [
            'name' => 'Staf Duplikat',
            'username' => $existing->username,
            'email' => $existing->email,
            'password' => 'RahasiaKuat123',
            'password_confirmation' => 'RahasiaKuat123',
            'dprd_member_id' => $member->id,
            'is_active' => '1',
        ])->assertSessionHasErrors(['username', 'email']);
    }

    public function test_account_creation_rejects_member_who_already_has_active_staff(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        User::factory()->staff($member)->create();

        $this->actingAs($admin)->post(route('akun.store'), [
            'name' => 'Staf Kedua',
            'username' => 'staf.kedua',
            'email' => 'staf.kedua@example.test',
            'password' => 'RahasiaKuat123',
            'password_confirmation' => 'RahasiaKuat123',
            'dprd_member_id' => $member->id,
            'is_active' => '1',
        ])->assertSessionHasErrors('dprd_member_id');

        $this->assertDatabaseMissing('users', ['username' => 'staf.kedua']);
    }

    public function test_account_creation_rejects_inactive_member(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->inactive()->create();

        $this->actingAs($admin)->post(route('akun.store'), [
            'name' => 'Staf Anggota Nonaktif',
            'username' => 'staf.nonaktif',
            'email' => 'staf.nonaktif@example.test',
            'password' => 'RahasiaKuat123',
            'password_confirmation' => 'RahasiaKuat123',
            'dprd_member_id' => $member->id,
            'is_active' => '1',
        ])->assertSessionHasErrors('dprd_member_id');
    }

    public function test_administrator_can_update_staff_identity_and_assignment(): void
    {
        $admin = $this->administrator();
        $oldMember = DprdMember::factory()->create();
        $newMember = DprdMember::factory()->create();
        $staff = User::factory()->staff($oldMember)->create([
            'name' => 'Nama Lama',
            'username' => 'nama.lama',
        ]);

        $this->actingAs($admin)->put(route('akun.update', $staff), [
            'name' => 'Nama Baru',
            'username' => 'nama.baru',
            'email' => 'nama.baru@example.test',
            'dprd_member_id' => $newMember->id,
            'is_active' => '1',
        ])->assertRedirect(route('akun.index'));

        $staff->refresh();

        $this->assertSame('Nama Baru', $staff->name);
        $this->assertSame('nama.baru', $staff->username);
        $this->assertSame($newMember->id, $staff->dprd_member_id);
        $this->assertSame(User::ROLE_STAF_PENDAMPING, $staff->role);
    }

    public function test_administrator_can_reset_staff_password(): void
    {
        $admin = $this->administrator();
        $staff = User::factory()->staff()->create([
            'remember_token' => 'token-lama',
        ]);

        $this->actingAs($admin)->post(route('akun.reset-password'), [
            'user_id' => $staff->id,
            'password' => 'SandiBaruKuat123',
            'password_confirmation' => 'SandiBaruKuat123',
        ])->assertRedirect(route('akun.index'))
            ->assertSessionHas('success');

        $staff->refresh();

        $this->assertTrue(Hash::check('SandiBaruKuat123', $staff->password));
        $this->assertTrue($staff->must_change_password);
        $this->assertNull($staff->remember_token);
    }

    public function test_password_reset_validation_uses_dedicated_error_bag(): void
    {
        $admin = $this->administrator();
        $staff = User::factory()->staff()->create();

        $this->actingAs($admin)->post(route('akun.reset-password'), [
            'user_id' => $staff->id,
            'password' => 'pendek',
            'password_confirmation' => 'berbeda',
        ])->assertSessionHasErrors('password', null, 'resetPassword');
    }

    public function test_administrator_can_toggle_staff_account_status(): void
    {
        $admin = $this->administrator();
        $staff = User::factory()->staff()->create([
            'is_active' => true,
            'remember_token' => 'token-lama',
        ]);

        $this->actingAs($admin)
            ->patch(route('akun.toggle-status', $staff))
            ->assertSessionHas('success');

        $staff->refresh();
        $this->assertFalse($staff->is_active);
        $this->assertNull($staff->remember_token);

        $this->actingAs($admin)
            ->patch(route('akun.toggle-status', $staff))
            ->assertSessionHas('success');

        $this->assertTrue($staff->refresh()->is_active);
    }

    public function test_administrator_can_soft_delete_staff_account(): void
    {
        $admin = $this->administrator();
        $staff = User::factory()->staff()->create();

        $this->actingAs($admin)
            ->delete(route('akun.destroy', $staff))
            ->assertRedirect(route('akun.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('users', ['id' => $staff->id]);
        $this->assertFalse((bool) $staff->fresh()?->is_active);
    }

    public function test_administrator_cannot_deactivate_or_delete_own_account(): void
    {
        $admin = $this->administrator();

        $this->actingAs($admin)->put(route('akun.update', $admin), [
            'name' => $admin->name,
            'username' => $admin->username,
            'email' => $admin->email,
            'is_active' => '0',
        ])->assertForbidden();

        $this->actingAs($admin)
            ->patch(route('akun.toggle-status', $admin))
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('akun.destroy', $admin))
            ->assertForbidden();

        $this->assertTrue($admin->refresh()->is_active);
        $this->assertNotSoftDeleted('users', ['id' => $admin->id]);
    }

    public function test_administrator_can_view_account_forms(): void
    {
        $admin = $this->administrator();
        $member = DprdMember::factory()->create();
        $staffMember = DprdMember::factory()->create();
        $staff = User::factory()->staff($staffMember)->create();

        $this->actingAs($admin)
            ->get(route('akun.create'))
            ->assertOk()
            ->assertSee('Tambah Akun Staf')
            ->assertSee($member->nama_lengkap);

        $this->actingAs($admin)
            ->get(route('akun.edit', $staff))
            ->assertOk()
            ->assertSee('Edit Akun Staf')
            ->assertSee($staff->name);

        $this->actingAs($admin)
            ->get(route('akun.edit', $admin))
            ->assertOk()
            ->assertSee('Akun administrator');
    }

    public function test_staff_cannot_access_account_management_urls(): void
    {
        $staff = User::factory()->staff()->create();
        $member = DprdMember::factory()->create();

        $this->actingAs($staff)->get(route('akun.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('akun.create'))->assertForbidden();
        $this->actingAs($staff)->post(route('akun.store'), [
            'name' => 'Akun Liar',
            'username' => 'akun.liar',
            'email' => 'akun.liar@example.test',
            'password' => 'RahasiaKuat123',
            'password_confirmation' => 'RahasiaKuat123',
            'dprd_member_id' => $member->id,
            'is_active' => '1',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['username' => 'akun.liar']);
    }

    public function test_inactive_account_cannot_login(): void
    {
        User::factory()->staff()->create([
            'username' => 'staf.nonaktif.login',
            'password' => 'RahasiaKuat123',
            'is_active' => false,
        ]);

        $this->post('/login', [
            'username' => 'staf.nonaktif.login',
            'password' => 'RahasiaKuat123',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_inactive_account_with_old_session_is_logged_out(): void
    {
        $admin = User::factory()->administrator()->create(['is_active' => false]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}

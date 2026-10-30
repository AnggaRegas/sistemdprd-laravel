<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Username atau Email');
    }

    public function test_administrator_can_authenticate_using_username(): void
    {
        $user = User::factory()->administrator()->create([
            'username' => 'admin-utama',
            'email' => 'admin-utama@example.test',
        ]);

        $response = $this->post('/login', [
            'username' => 'admin-utama',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));
    }

    public function test_administrator_can_authenticate_using_email(): void
    {
        $user = User::factory()->administrator()->create([
            'username' => 'admin-email',
            'email' => 'admin-email@example.test',
        ]);

        $response = $this->post('/login', [
            'username' => 'admin-email@example.test',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));
    }

    public function test_staff_is_redirected_to_the_staff_dashboard(): void
    {
        $staff = User::factory()->staff()->create([
            'username' => 'staf-utama',
            'email' => 'staf-utama@example.test',
        ]);

        $response = $this->post('/login', [
            'username' => 'staf-utama',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($staff);
        $response->assertRedirect(route('dashboard.staf'));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'username' => $user->username,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');
    }

    public function test_guests_are_redirected_to_login_for_protected_pages(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get('/dashboard/staf')->assertRedirect(route('login'));
        $this->get('/arsip')->assertRedirect(route('login'));
        $this->get('/anggota')->assertRedirect(route('login'));
        $this->get('/laporan')->assertRedirect(route('login'));
        $this->get('/profile')->assertRedirect(route('login'));
    }
}

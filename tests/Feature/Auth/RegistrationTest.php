<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'attendee',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role' => 'attendee',
        ]);
    }

    public function test_organizer_registration_persists_organizer_role(): void
    {
        $response = $this->post('/register', [
            'name' => 'Event Organizer',
            'email' => 'organizer-register@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'organizer',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseHas('users', [
            'email' => 'organizer-register@example.com',
            'role' => 'organizer',
        ]);
        $this->assertTrue(User::where('email', 'organizer-register@example.com')->first()->isOrganizer());
    }

    public function test_invalid_registration_role_is_rejected(): void
    {
        $response = $this->post('/register', [
            'name' => 'Admin Attempt',
            'email' => 'admin@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'admin',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', [
            'email' => 'admin@example.com',
        ]);
    }
}

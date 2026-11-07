<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_and_private_api_require_authentication(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->getJson('/companion')->assertUnauthorized();
        $this->postJson('/companion/tasks', ['text' => 'Test'])->assertUnauthorized();
    }

    public function test_registration_validates_and_claims_previous_session_data(): void
    {
        $this->post('/register', ['name' => 'A', 'email' => 'bad', 'password' => 'short'])->assertSessionHasErrors(['email', 'password']);
        $owner = '11111111-1111-4111-8111-111111111111';
        $this->withSession(['companion_owner' => $owner])->post('/register', ['name' => 'Ada', 'email' => 'ADA@example.com', 'password' => 'password123', 'password_confirmation' => 'password123'])->assertRedirect('/');
        $this->assertAuthenticated();
        $user = User::where('email', 'ada@example.com')->firstOrFail();
        $this->assertSame($owner, $user->companion_owner);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_login_and_logout(): void
    {
        $user = User::factory()->create(['email' => 'person@example.com', 'password' => Hash::make('password123')]);
        $this->post('/login', ['email' => 'person@example.com', 'password' => 'bad'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => 'person@example.com', 'password' => 'password123'])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->companion_owner);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }
}

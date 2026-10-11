<?php

namespace Tests\Feature;

use App\Models\PhoneChallenge;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\SocialiteManager;
use Laravel\Socialite\Two\User as SocialUser;
use Mockery;
use Tests\TestCase;

class ConnectedSignInTest extends TestCase
{
    use RefreshDatabase;

    private string $sid = 'VEaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    public function test_social_customer_can_request_a_password_and_is_signed_out_to_use_the_reset_link(): void
    {
        Notification::fake();
        $user = User::factory()->withoutTwoFactor()->create();
        $this->actingAs($user)->post('/settings/connections/password-reset')->assertRedirect('/forgot-password')->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPassword::class);
        $this->assertGuest();
    }

    public function test_codes_and_provider_secrets_are_not_flashed_on_validation_errors(): void
    {
        $this->post('/login/phone/verify', ['code' => 'invalid'])->assertSessionHasErrors('code')->assertSessionMissing('_old_input.code');
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->withSession([])->withCookie(config('session.cookie'), session()->getId());
        Http::preventStrayRequests();
        config(['services.google.client_id' => 'test-client', 'services.google.client_secret' => 'test-secret',
            'services.facebook.client_id' => 'facebook-client', 'services.facebook.client_secret' => 'facebook-secret',
            'services.twilio.enabled' => true, 'services.twilio.account_sid' => 'test-account',
            'services.twilio.auth_token' => 'test-token', 'services.twilio.verify_service_sid' => 'test-service']);
    }

    private function identity(string $provider = 'google', string $id = 'provider-user', string $email = 'buyer@example.com'): void
    {
        $user = (new SocialUser)->map(['id' => $id, 'name' => 'Shoe Buyer', 'email' => $email]);
        $driver = Mockery::mock(Provider::class);
        $driver->shouldReceive('user')->once()->andReturn($user);
        $manager = Mockery::mock(SocialiteManager::class);
        $manager->shouldReceive('driver')->with($provider)->once()->andReturn($driver);
        Socialite::swap($manager);
    }

    private function flow(?User $user = null, string $provider = 'google'): array
    {
        return ['social_flow' => ['provider' => $provider, 'user_id' => $user?->id, 'expires' => now()->addMinutes(10)->timestamp]];
    }

    private function sms(string $status = 'approved', string $phone = '+9779800000000'): void
    {
        Http::fake([
            '*VerificationCheck' => Http::response(['sid' => $this->sid, 'status' => $status, 'to' => $phone]),
            '*Verifications' => Http::response(['sid' => $this->sid, 'status' => 'pending']),
        ]);
    }

    public function test_sign_in_options_only_report_configuration_never_secrets(): void
    {
        $response = $this->get('/login')->assertOk()->assertInertia(fn (Assert $page) => $page->where('signInOptions.google', true)->where('signInOptions.phone', true));
        $response->assertDontSee('test-secret')->assertDontSee('test-token');
        config(['services.google.client_secret' => '', 'services.twilio.enabled' => false]);
        $this->get('/login')->assertInertia(fn (Assert $page) => $page->where('signInOptions.google', false)->where('signInOptions.phone', false));
        $this->from('/login')->post('/auth/google')->assertRedirect('/login')->assertSessionHasErrors('social');
        $this->from('/login/phone')->post('/login/phone/send', ['phone' => '9800000000'])->assertSessionHasErrors('phone');
        Http::assertNothingSent();
    }

    public function test_oauth_redirect_uses_state_and_rejects_a_mismatched_callback(): void
    {
        $this->post('/auth/google')->assertRedirectContains('accounts.google.com')->assertSessionHas('state')->assertSessionHas('social_flow');
        $this->get('/auth/google/callback?state=wrong&code=attacker')->assertRedirect('/login')->assertSessionHasErrors('social');
        $this->assertGuest();
        $this->assertDatabaseCount('social_accounts', 0);
        $this->get('/auth/google')->assertStatus(405);
    }

    public function test_google_creates_a_customer_and_cannot_replay_the_callback(): void
    {
        $this->identity();
        $this->withSession($this->flow())->get('/auth/google/callback')->assertRedirect('/');
        $user = User::sole();
        $this->assertAuthenticatedAs($user);
        $this->assertFalse($user->is_admin);
        $this->assertDatabaseHas('social_accounts', ['provider' => 'google', 'user_id' => $user->id]);
        $this->get('/auth/google/callback')->assertSessionHasErrors('social');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_matching_email_does_not_take_over_an_existing_account(): void
    {
        User::factory()->create(['email' => 'buyer@example.com']);
        $this->identity();
        $this->withSession($this->flow())->get('/auth/google/callback')->assertRedirect('/login')->assertSessionHasErrors('social');
        $this->assertGuest();
        $this->assertDatabaseCount('social_accounts', 0);
    }

    public function test_facebook_without_email_can_be_linked_but_cannot_create_an_account(): void
    {
        $this->identity('facebook', 'fb-id', '');
        $this->withSession($this->flow(null, 'facebook'))->get('/auth/facebook/callback')->assertSessionHasErrors('social');
        $this->assertDatabaseCount('users', 0);
        $user = User::factory()->create();
        $this->identity('facebook', 'fb-id', '');
        $this->actingAs($user)->withSession($this->flow($user, 'facebook'))->get('/auth/facebook/callback')->assertRedirect('/settings/connections');
        $this->assertDatabaseHas('social_accounts', ['provider' => 'facebook', 'user_id' => $user->id]);
    }

    public function test_social_login_preserves_two_factor_authentication(): void
    {
        $user = User::factory()->create(['two_factor_secret' => encrypt('secret'), 'two_factor_confirmed_at' => now()]);
        DB::table('social_accounts')->insert(['user_id' => $user->id, 'provider' => 'google', 'provider_id' => 'provider-user']);
        $this->identity();
        $this->withSession($this->flow())->get('/auth/google/callback')->assertRedirect('/two-factor-challenge')->assertSessionHas('login.id', $user->id);
        $this->assertGuest();
    }

    public function test_linking_and_disconnecting_require_an_authenticated_confirmed_password(): void
    {
        $this->post('/settings/connections/google')->assertRedirect('/login');
        $user = User::factory()->create();
        $this->actingAs($user)->post('/settings/connections/google')->assertRedirect('/user/confirm-password');
        $this->delete('/settings/connections/phone')->assertRedirect('/user/confirm-password');
        $this->withSession(['auth.password_confirmed_at' => time()])->post('/settings/connections/google')->assertRedirectContains('accounts.google.com');
    }

    public function test_a_social_account_cannot_be_linked_to_two_users(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        DB::table('social_accounts')->insert(['user_id' => $owner->id, 'provider' => 'google', 'provider_id' => 'provider-user']);
        $this->identity();
        $this->actingAs($other)->withSession($this->flow($other))->get('/auth/google/callback')->assertSessionHasErrors('social');
        $this->assertDatabaseHas('social_accounts', ['provider_id' => 'provider-user', 'user_id' => $owner->id]);
    }

    public function test_oauth_flow_is_bound_to_the_original_account_and_expires(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withSession($this->flow())->get('/auth/google/callback')->assertSessionHasErrors('social');
        $this->withSession(['social_flow' => ['provider' => 'google', 'user_id' => $user->id, 'expires' => now()->subMinute()->timestamp]])->get('/auth/google/callback')->assertSessionHasErrors('social');
        $this->assertDatabaseCount('social_accounts', 0);
    }

    public function test_nepali_phone_is_normalized_and_only_stored_after_sms_verification(): void
    {
        $this->sms();
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])->from('/settings/connections')->post('/settings/connections/phone/send', ['phone' => '९८००००००००'])->assertSessionHasNoErrors();
        $this->assertNull($user->fresh()->phone);
        Http::assertSent(fn ($r) => $r['To'] === '+9779800000000' && $r['Channel'] === 'sms');
        $this->post('/settings/connections/phone/verify', ['code' => '१२३४५६'])->assertSessionHasNoErrors();
        $this->assertSame('+9779800000000', $user->fresh()->phone);
        $this->assertNotNull($user->fresh()->phone_verified_at);
        $this->assertNotNull(PhoneChallenge::sole()->used_at);
        $this->assertStringNotContainsString($this->sid, DB::table('phone_challenges')->value('provider_sid'));
        $this->post('/settings/connections/phone/verify', ['code' => '123456'])->assertSessionHasErrors('code');
    }

    public function test_phone_login_and_two_factor_challenge(): void
    {
        $this->sms();
        $user = User::factory()->create(['phone' => '+9779800000000', 'phone_verified_at' => now(), 'two_factor_secret' => encrypt('secret'), 'two_factor_confirmed_at' => now()]);
        $this->from('/login/phone')->post('/login/phone/send', ['phone' => '9800000000'])->assertSessionHasNoErrors();
        $this->post('/login/phone/verify', ['code' => '123456'])->assertRedirect('/two-factor-challenge')->assertSessionHas('login.id', $user->id);
        $this->assertGuest();
    }

    public function test_verified_phone_can_sign_in_and_return_to_wholesale(): void
    {
        $this->sms();
        $user = User::factory()->withoutTwoFactor()->create(['phone' => '+9779800000000', 'phone_verified_at' => now()]);
        $this->get('/wholesale')->assertSessionHas('url.intended', '/wholesale');
        $this->post('/login/phone/send', ['phone' => '+977 9800000000']);
        $this->post('/login/phone/verify', ['code' => '123456'])->assertRedirect('/wholesale');
        $this->assertAuthenticatedAs($user);
    }

    public function test_unknown_or_unverified_phones_do_not_disclose_users_or_send_sms(): void
    {
        User::factory()->create(['phone' => '+9779800000000', 'phone_verified_at' => null]);
        $this->from('/login/phone')->post('/login/phone/send', ['phone' => '9800000000'])->assertRedirect('/login/phone')->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->post('/login/phone/verify', ['code' => '123456'])->assertSessionHasErrors('code');
        Http::assertNothingSent();
        $this->assertGuest();
    }

    public function test_failed_codes_are_limited_and_expired_or_wrong_session_challenges_are_rejected(): void
    {
        $this->sms('pending');
        User::factory()->create(['phone' => '+9779800000000', 'phone_verified_at' => now()]);
        $this->post('/login/phone/send', ['phone' => '9800000000']);
        for ($i = 0; $i < 6; $i++) {
            $this->post('/login/phone/verify', ['code' => '111111'])->assertSessionHasErrors('code');
        }
        $this->assertEquals(5, PhoneChallenge::sole()->attempts);
        Http::assertSentCount(6); // One send, five checks.
        PhoneChallenge::query()->update(['attempts' => 0, 'expires_at' => now()->subMinute()]);
        $this->post('/login/phone/verify', ['code' => '123456'])->assertSessionHasErrors('code');
        PhoneChallenge::query()->update(['expires_at' => now()->addMinute(), 'session_hash' => str_repeat('a', 64)]);
        $this->post('/login/phone/verify', ['code' => '123456'])->assertSessionHasErrors('code');
        Http::assertSentCount(6);
    }

    public function test_phone_send_limits_and_provider_failure_do_not_create_a_login(): void
    {
        User::factory()->create(['phone' => '+9779800000000', 'phone_verified_at' => now()]);
        Http::fake(['*' => Http::response([], 503)]);
        $this->post('/login/phone/send', ['phone' => '9800000000'])->assertSessionHasErrors('phone');
        $this->assertDatabaseCount('phone_challenges', 0);
        $this->post('/login/phone/send', ['phone' => '9800000000'])->assertSessionHasErrors('phone');
        Http::assertSentCount(1);
        $this->assertGuest();
    }

    public function test_phone_linking_cannot_steal_another_users_number(): void
    {
        $this->sms();
        $owner = User::factory()->create(['phone' => '+9779800000000', 'phone_verified_at' => now()]);
        $other = User::factory()->create();
        $this->actingAs($other)->withSession(['auth.password_confirmed_at' => time()])->post('/settings/connections/phone/send', ['phone' => '9800000000']);
        $this->post('/settings/connections/phone/verify', ['code' => '123456'])->assertSessionHasErrors('code');
        $this->assertNull($other->fresh()->phone);
        $this->assertSame('+9779800000000', $owner->fresh()->phone);
    }
}

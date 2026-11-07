<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;
    public function test_home_page_displays_daily_companion_and_plain_javascript_assets(): void
    {
        $this->actingAs(User::factory()->create(['companion_owner' => '11111111-1111-4111-8111-111111111111']))->get('/')
            ->assertOk()
            ->assertSee('My Day')
            ->assertSee('My list')
            ->assertSee('Find the pairs')
            ->assertSee('Tech help')
            ->assertSee('js/my-day.js');
    }
}

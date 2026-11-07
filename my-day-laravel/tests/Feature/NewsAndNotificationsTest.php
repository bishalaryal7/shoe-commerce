<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\CompanionTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NewsAndNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private function user(bool $admin = false): User
    {
        return User::factory()->create(['is_admin' => $admin, 'companion_owner' => (string) \Illuminate\Support\Str::uuid()]);
    }
    public function test_admin_routes_require_admin_and_validate_content(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->actingAs($this->user())->get('/admin')->assertForbidden();
        $this->post('/admin/articles', [])->assertForbidden();
        $this->actingAs($this->user(true));
        $this->post('/admin/articles', [])->assertSessionHasErrors(['title', 'body', 'summary', 'category']);
        $this->post('/admin/articles', ['title'=>'A story','summary'=>'Summary','body'=>'Text','category'=>'Community','published'=>'1'])->assertRedirect('/admin');
        $this->assertDatabaseHas('articles', ['title' => 'A story', 'published' => 1]);
        $article = Article::where('title', 'A story')->firstOrFail();
        $this->put('/admin/articles/'.$article->id, ['title'=>'Updated','summary'=>'Summary','body'=>'Text','category'=>'Community'])->assertRedirect('/admin');
        $this->delete('/admin/articles/'.$article->id)->assertRedirect('/admin');
        $this->assertDatabaseMissing('articles', ['id' => $article->id]);
    }
    public function test_news_search_drafts_and_saved_stories_are_scoped_to_user(): void
    {
        $a = $this->user(); $b = $this->user();
        $article = Article::factory()->create(['title'=>'Garden gathering','published'=>true]);
        $draft = Article::factory()->create(['title'=>'Hidden draft','published'=>false]);
        $this->actingAs($a)->get('/news?q=Garden')->assertOk()->assertSee('Garden gathering')->assertDontSee('Hidden draft');
        $this->get('/news/'.$draft->id)->assertNotFound();
        $this->post('/news/'.$article->id.'/save')->assertRedirect();
        $this->get('/news?saved=1')->assertSee('Garden gathering');
        $this->actingAs($b)->get('/news?saved=1')->assertDontSee('Garden gathering');
        $this->assertDatabaseHas('saved_articles', ['user_id'=>$a->id, 'article_id'=>$article->id]);
    }
    public function test_due_reminders_are_deduplicated_and_private(): void
    {
        $a = $this->user(); $b = $this->user();
        $task = CompanionTask::create(['owner'=>$a->companion_owner,'text'=>'Call family','due_date'=>today()->format('Y-m-d'),'done'=>false]);
        $this->actingAs($a)->getJson('/reminders')->assertOk()->assertJsonCount(1, 'notifications')->assertJsonPath('notifications.0.text', 'Call family');
        $this->getJson('/reminders')->assertJsonCount(1, 'notifications');
        $id = DB::table('reminder_notifications')->value('id');
        $this->actingAs($b)->postJson('/reminders/'.$id.'/read')->assertNotFound();
        $this->actingAs($a)->postJson('/reminders/'.$id.'/read')->assertNoContent();
        $this->getJson('/reminders')->assertJsonPath('notifications.0.id', $id);
        $task->update(['done'=>true]);
        $this->getJson('/reminders')->assertJsonCount(0, 'notifications');
    }
    public function test_verified_stories_show_sources_and_seeding_preserves_samples(): void
    {
        $sample = Article::factory()->create(['is_sample' => true, 'published' => true]);
        $this->seed(\Database\Seeders\VerifiedStoriesSeeder::class);
        $this->seed(\Database\Seeders\VerifiedStoriesSeeder::class);
        $this->assertSame(5, Article::whereNotNull('source_url')->count());
        $this->assertFalse($sample->fresh()->published);
        $story = Article::where('source_name', 'NASA')->firstOrFail();
        $this->actingAs($this->user())->get('/news/'.$story->id)->assertOk()->assertSee('NASA')->assertSee($story->source_url);
    }

    public function test_admin_cannot_save_an_unsafe_source_link(): void
    {
        $this->actingAs($this->user(true))->post('/admin/articles', ['title'=>'Story','summary'=>'Summary','body'=>'Text','category'=>'Community','source_url'=>'javascript:alert(1)'])->assertSessionHasErrors('source_url');
    }

}

<?php

namespace Tests\Feature;

use App\Models\CompanionTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanionTest extends TestCase
{
    use RefreshDatabase;

    private const OWNER_A = '11111111-1111-4111-8111-111111111111';
    private const OWNER_B = '22222222-2222-4222-8222-222222222222';

    public function test_tasks_can_be_created_completed_and_deleted(): void
    {
        $this->actingAs(User::factory()->create(['companion_owner' => self::OWNER_A]));
        $created = $this->postJson('/companion/tasks', ['text' => 'Call a friend', 'due_date' => '2026-12-01'])->assertCreated()->assertJsonPath('done', false);
        $id = $created->json('id');
        $this->patchJson("/companion/tasks/$id", ['done' => true])->assertOk()->assertJsonPath('done', true);
        $this->getJson('/companion')->assertJsonPath('tasks.0.text', 'Call a friend')->assertJsonPath('tasks.0.due_date', '2026-12-01');
        $this->patchJson("/companion/tasks/$id", ['text' => 'Call family', 'due_date' => null])->assertOk()->assertJsonPath('text', 'Call family');
        $this->deleteJson("/companion/tasks/$id")->assertNoContent();
        $this->assertDatabaseMissing('companion_tasks', ['id' => $id]);
    }

    public function test_sessions_cannot_read_or_change_each_others_data(): void
    {
        $this->actingAs(User::factory()->create(['companion_owner' => self::OWNER_A]));
        $id = $this->postJson('/companion/tasks', ['text' => 'Private task'])->json('id');
        $this->putJson('/companion/note', ['content' => 'Private note'])->assertOk();
        $this->actingAs(User::factory()->create(['companion_owner' => self::OWNER_B]));
        $this->getJson('/companion')->assertJsonCount(0, 'tasks')->assertJsonPath('note', '');
        $this->patchJson("/companion/tasks/$id", ['done' => true])->assertNotFound();
        $this->deleteJson("/companion/tasks/$id")->assertNotFound();
    }

    public function test_validates_input_and_import_is_not_duplicated(): void
    {
        $this->actingAs(User::factory()->create(['companion_owner' => self::OWNER_A]));
        $this->postJson('/companion/tasks', ['text' => ' ', 'due_date' => 'wrong'])->assertUnprocessable();
        $this->putJson('/companion/note', ['content' => str_repeat('a', 5001)])->assertUnprocessable();
        $this->withSession(['allow_legacy_import' => true]);
        $payload = ['tasks' => [['text' => 'Old task', 'done' => false]]];
        $this->postJson('/companion/import', $payload)->assertOk();
        $this->postJson('/companion/import', $payload)->assertOk();
        $this->assertSame(1, CompanionTask::where('text', 'Old task')->count());
    }
}

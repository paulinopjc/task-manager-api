<?php

namespace Tests\Feature\Tag;

use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTagTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_attach_tags_to_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['creator_id' => $user->id]);

        $response = $this->actingAs($user)
            ->postJson("/api/tasks/{$task->id}/tags", [
                'tags' => ['backend', 'urgent'],
            ]);

        $response->assertStatus(200);
        $this->assertCount(2, $task->fresh()->tags);
    }

    public function test_attaching_tags_creates_new_tags(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['creator_id' => $user->id]);

        $this->actingAs($user)
            ->postJson("/api/tasks/{$task->id}/tags", [
                'tags' => ['new-tag'],
            ]);

        $this->assertDatabaseHas('tags', ['name' => 'new-tag']);
    }

    public function test_attaching_existing_tag_does_not_duplicate(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['creator_id' => $user->id]);
        Tag::factory()->create(['name' => 'backend']);

        $this->actingAs($user)
            ->postJson("/api/tasks/{$task->id}/tags", [
                'tags' => ['backend'],
            ]);

        $this->assertDatabaseCount('tags', 1); // still just 1 tag, not 2
    }

    public function test_can_detach_tag_from_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['creator_id' => $user->id]);
        $tag = Tag::factory()->create();
        $task->tags()->attach($tag);

        $response = $this->actingAs($user)
            ->deleteJson("/api/tasks/{$task->id}/tags/{$tag->id}");

        $response->assertStatus(200);
        $this->assertCount(0, $task->fresh()->tags);
        // Tag still exists in the tags table, just detached from this task
        $this->assertDatabaseHas('tags', ['id' => $tag->id]);
    }

    public function test_can_filter_tasks_by_tag(): void
    {
        $user = User::factory()->create();
        $task1 = Task::factory()->create(['creator_id' => $user->id]);
        $task2 = Task::factory()->create(['creator_id' => $user->id]);
        $tag = Tag::factory()->create(['name' => 'backend']);
        $task1->tags()->attach($tag);
        // task2 has no tags

        $response = $this->actingAs($user)
            ->getJson('/api/tasks?tag=backend');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_list_all_tags(): void
    {
        $user = User::factory()->create();
        Tag::factory()->count(3)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/tags');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }
}
<?php

namespace Tests\Feature\Task;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_tasks(): void
    {
        $user = User::factory()->create();
        Task::factory()->count(3)->create(['creator_id' => $user->id]);

        $response = $this->actingAs($user)
            ->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_view_single_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['creator_id' => $user->id]);

        $response = $this->actingAs($user)
            ->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $task->id,
                    'title' => $task->title,
                ],
            ]);
    }

    public function test_viewing_nonexistent_task_returns_404(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/tasks/99999');

        $response->assertStatus(404);
    }
}
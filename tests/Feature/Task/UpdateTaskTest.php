<?php

namespace Tests\Feature\Task;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['creator_id' => $user->id]);

        $response = $this->actingAs($user)
            ->putJson("/api/tasks/{$task->id}", [
                'title' => 'Updated title',
                'priority' => 'urgent',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'title' => 'Updated title',
                    'priority' => 'urgent',
                ],
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated title',
        ]);
    }

    public function test_user_can_update_task_status(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create([
            'creator_id' => $user->id,
            'status' => 'todo',
        ]);

        $response = $this->actingAs($user)
            ->putJson("/api/tasks/{$task->id}", [
                'status' => 'in_progress',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => ['status' => 'in_progress'],
            ]);
    }
}
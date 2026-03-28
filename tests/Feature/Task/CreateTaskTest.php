<?php

namespace Tests\Feature\Task;

use App\Enums\TaskStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_task(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/tasks', [
                'title' => 'Fix login bug',
                'description' => 'Users cannot log in with + in email',
                'priority' => 'high',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'description',
                    'status',
                    'priority',
                    'creator',
                    'assigned_to',
                    'due_date',
                    'tags',
                    'created_at',
                ],
            ]);

        // Verify the task was saved correctly
        $this->assertDatabaseHas('tasks', [
            'title' => 'Fix login bug',
            'creator_id' => $user->id,
            'status' => TaskStatus::Todo->value,
        ]);
    }

    public function test_task_defaults_to_todo_status(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/tasks', [
                'title' => 'New task',
                'priority' => 'low',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'status' => 'todo',
                ],
            ]);
    }

    public function test_create_task_requires_title_and_priority(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/tasks', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'priority']);
    }

    public function test_unauthenticated_user_cannot_create_task(): void
    {
        $response = $this->postJson('/api/tasks', [
            'title' => 'Test task',
            'priority' => 'low',
        ]);

        $response->assertStatus(401);
    }
}
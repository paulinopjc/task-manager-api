<?php

namespace Tests\Feature\Task;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_view_own_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['creator_id' => $user->id]);

        $response = $this->actingAs($user)
            ->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200);
    }

    public function test_member_can_view_task_assigned_to_them(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);

        $response = $this->actingAs($user)
            ->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200);
    }

    public function test_member_cannot_view_other_users_task(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $task = Task::factory()->create(['creator_id' => $otherUser->id]);

        $response = $this->actingAs($user)
            ->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(403);
    }

    public function test_admin_can_view_any_task(): void
    {
        $admin = User::factory()->admin()->create();
        $otherUser = User::factory()->create();
        $task = Task::factory()->create(['creator_id' => $otherUser->id]);

        $response = $this->actingAs($admin)
            ->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200);
    }

    public function test_member_cannot_delete_other_users_task(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $task = Task::factory()->create(['creator_id' => $otherUser->id]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(403);
    }

    public function test_admin_can_delete_any_task(): void
    {
        $admin = User::factory()->admin()->create();
        $task = Task::factory()->create();

        $response = $this->actingAs($admin)
            ->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(200);
    }

    public function test_member_only_sees_own_tasks_in_list(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Task::factory()->count(2)->create(['creator_id' => $user->id]);
        Task::factory()->create(['assigned_to' => $user->id]);
        Task::factory()->count(3)->create(['creator_id' => $otherUser->id]);

        $response = $this->actingAs($user)
            ->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data'); // 2 created + 1 assigned
    }
}
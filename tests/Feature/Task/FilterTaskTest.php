<?php

namespace Tests\Feature\Task;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilterTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_filter_tasks_by_status(): void
    {
        $user = User::factory()->create();
        Task::factory()->count(2)->create([
            'creator_id' => $user->id,
            'status' => TaskStatus::Todo,
        ]);
        Task::factory()->create([
            'creator_id' => $user->id,
            'status' => TaskStatus::Done,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/tasks?status=todo');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_tasks_by_priority(): void
    {
        $user = User::factory()->create();
        Task::factory()->create([
            'creator_id' => $user->id,
            'priority' => TaskPriority::High,
        ]);
        Task::factory()->count(2)->create([
            'creator_id' => $user->id,
            'priority' => TaskPriority::Low,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/tasks?priority=high');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_search_tasks_by_title(): void
    {
        $user = User::factory()->create();
        Task::factory()->create([
            'creator_id' => $user->id,
            'title' => 'Fix the login bug',
        ]);
        Task::factory()->create([
            'creator_id' => $user->id,
            'title' => 'Update the README',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/tasks?search=login');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_combine_filters(): void
    {
        $user = User::factory()->create();
        Task::factory()->create([
            'creator_id' => $user->id,
            'status' => TaskStatus::Todo,
            'priority' => TaskPriority::High,
        ]);
        Task::factory()->create([
            'creator_id' => $user->id,
            'status' => TaskStatus::Todo,
            'priority' => TaskPriority::Low,
        ]);
        Task::factory()->create([
            'creator_id' => $user->id,
            'status' => TaskStatus::Done,
            'priority' => TaskPriority::High,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/tasks?status=todo&priority=high');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_pagination_returns_correct_page_size(): void
    {
        $user = User::factory()->create();
        Task::factory()->count(10)->create(['creator_id' => $user->id]);

        $response = $this->actingAs($user)
            ->getJson('/api/tasks?per_page=3');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['meta' => ['next_cursor', 'per_page']]);
    }

    public function test_can_sort_by_due_date_descending(): void
    {
        $user = User::factory()->create();
        $old = Task::factory()->create([
            'creator_id' => $user->id,
            'due_date' => '2026-04-01',
        ]);
        $new = Task::factory()->create([
            'creator_id' => $user->id,
            'due_date' => '2026-05-01',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/tasks?sort=-due_date');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals($new->id, $data[0]['id']);
        $this->assertEquals($old->id, $data[1]['id']);
    }
}
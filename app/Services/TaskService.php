<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Task;
use App\Models\User;
use App\Models\Tag;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Facades\Gate;

class TaskService
{
    public function create(User $user, array $data): Task
    {
        $task = Task::create([
            ...$data,
            'creator_id' => $user->id,
            'status' => $data['status'] ?? TaskStatus::Todo,
        ]);

        return $task->load(['creator', 'assignee', 'tags']);
    }

    public function update(Task $task, array $data): Task
    {
        $task->update($data);

        return $task->fresh(['creator', 'assignee', 'tags']);
    }

    public function delete(Task $task): void
    {
        $task->delete();
    }

    public function find(User $user, int $id): Task
    {
        $task = Task::with(['creator', 'assignee', 'tags'])->findOrFail($id);

        Gate::authorize('view', $task);

        return $task;
    }

    public function list(User $user, array $filters = []): CursorPaginator
    {
        $query = Task::with(['creator', 'assignee', 'tags']);

        // Scope by user role — members only see their own tasks
        if ($user->role !== UserRole::Admin) {
            $query->where(function ($q) use ($user) {
                $q->where('creator_id', $user->id)
                  ->orWhere('assigned_to', $user->id);
            });
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (isset($filters['due_before'])) {
            $query->where('due_date', '<=', $filters['due_before']);
        }

        if (isset($filters['due_after'])) {
            $query->where('due_date', '>=', $filters['due_after']);
        }

        if (isset($filters['sort'])) {
            $sortField = $filters['sort'];
            $direction = 'asc';

            if (str_starts_with($sortField, '-')) {
                $direction = 'desc';
                $sortField = substr($sortField, 1);
            }

            $allowed = ['title', 'status', 'priority', 'due_date', 'created_at'];
            if (in_array($sortField, $allowed)) {
                $query->orderBy($sortField, $direction);
            }
        } else {
            $query->latest();
        }

        if (isset($filters['tag'])) {
            $query->whereHas('tags', function ($q) use ($filters) {
                $q->where('name', $filters['tag']);
            });
        }

        $perPage = min($filters['per_page'] ?? 15, 50);
        return $query->cursorPaginate($perPage);

    }

    public function attachTags(Task $task, array $tagNames): void
    {
        $tagIds = collect($tagNames)->map(function ($name) {
            return Tag::firstOrCreate(['name' => strtolower(trim($name))])->id;
        });

        $task->tags()->syncWithoutDetaching($tagIds);
    }

    public function detachTag(Task $task, Tag $tag): void
    {
        $task->tags()->detach($tag);
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
    public function __construct(
        private TaskService $taskService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tasks = $this->taskService->list($request->user(), $request->query());

        return response()->json([
            'data' => TaskResource::collection($tasks),
            'meta' => [
                'next_cursor' => $tasks->nextCursor()?->encode(),
                'prev_cursor' => $tasks->previousCursor()?->encode(),
                'per_page' => $tasks->perPage(),
            ],
        ]);
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = $this->taskService->create(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'data' => new TaskResource($task),
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $task = $this->taskService->find($request->user(), $id);

        return response()->json([
            'data' => new TaskResource($task),
        ]);
    }

    public function update(UpdateTaskRequest $request, int $id): JsonResponse
    {
        $task = $this->taskService->find($request->user(), $id);

        Gate::authorize('update', $task);

        $task = $this->taskService->update($task, $request->validated());

        return response()->json([
            'data' => new TaskResource($task),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $task = $this->taskService->find($request->user(), $id);

        Gate::authorize('delete', $task);

        $this->taskService->delete($task);

        return response()->json(['message' => 'Task deleted.']);
    }

    public function attachTags(Request $request, int $id): JsonResponse
    {
        $task = $this->taskService->find($request->user(), $id);

        $request->validate([
            'tags' => ['required', 'array'],
            'tags.*' => ['required', 'string', 'max:50'],
        ]);

        $this->taskService->attachTags($task, $request->input('tags'));

        return response()->json([
            'data' => new TaskResource($task->fresh(['creator', 'assignee', 'tags'])),
        ]);
    }

    public function detachTag(Request $request, int $taskId, int $tagId): JsonResponse
    {
        $task = $this->taskService->find($request->user(), $taskId);
        $tag = \App\Models\Tag::findOrFail($tagId);

        $this->taskService->detachTag($task, $tag);

        return response()->json(['message' => 'Tag detached.']);
    }
}
<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Can the user view this task?
     */
    public function view(User $user, Task $task): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        return $task->creator_id === $user->id
            || $task->assigned_to === $user->id;
    }

    /**
     * Can the user update this task?
     */
    public function update(User $user, Task $task): bool
    {
        return $this->view($user, $task);
    }

    /**
     * Can the user delete this task?
     */
    public function delete(User $user, Task $task): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        return $task->creator_id === $user->id;
    }
}
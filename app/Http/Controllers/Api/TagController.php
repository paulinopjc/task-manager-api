<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index(): JsonResponse
    {
        $tags = Tag::all();

        return response()->json([
            'data' => $tags->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
            ]),
        ]);
    }
}
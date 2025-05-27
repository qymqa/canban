<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comment;
use App\Models\Task;
use Illuminate\Http\JsonResponse;

class CommentController extends Controller
{
    public function index(Task $task): JsonResponse
    {
        $comments = $task->comments()->orderBy('created_at', 'asc')->get();
        return response()->json($comments);
    }

    public function store(Request $request, Task $task): JsonResponse
    {
        $request->validate([
            'content' => 'required|string',
            'user_id' => 'required|string',
            'user_name' => 'required|string',
            'user_surname' => 'required|string',
        ]);

        $comment = $task->comments()->create([
            'user_id' => $request->user_id,
            'user_name' => $request->user_name,
            'user_surname' => $request->user_surname,
            'content' => $request->content,
        ]);

        return response()->json($comment, 201);
    }

    public function destroy(Comment $comment): JsonResponse
    {
        $comment->delete();
        return response()->json(['message' => 'Комментарий удален']);
    }
}

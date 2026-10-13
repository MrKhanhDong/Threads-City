<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Services\CommentThreads;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentThreadController extends Controller
{
    public function index(Request $request, Comment $comment, CommentThreads $threads): JsonResponse
    {
        $data = $request->validate(['after' => 'nullable|integer|min:0', 'focus' => 'nullable|integer|min:1']);
        $viewerId = $request->user()?->id;
        abort_unless($comment->parent_id === null && $threads->root($comment, $viewerId) === $comment->id, 404);
        $batch = $threads->batch($comment, $viewerId, (int) ($data['after'] ?? 0), isset($data['focus']) ? (int) $data['focus'] : null);

        return response()->json([
            'html' => view('blog.replies', ['replies' => $batch['replies'], 'post' => $comment->post])->render(),
            'next' => $batch['next'], 'more' => $batch['more'], 'earlier' => $batch['earlier'],
        ])->header('Cache-Control', 'private, no-store');
    }
}

<?php
namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Comment;
use App\Http\Requests\StoreCommentRequest;

class CommentController extends Controller
{
    public function store(StoreCommentRequest $request, Post $post)
    {
        $user = auth('api')->user();

        $comment = $post->comments()->create([
            'user_id' => $user->id,
            'body' => $request->validated()['body'],
        ]);

        return response()->json([
            'id' => $comment->id,
            'post_id' => $post->id,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
            'body' => $comment->body,
            'created_at' => $comment->created_at,
        ], 201);
    }
}

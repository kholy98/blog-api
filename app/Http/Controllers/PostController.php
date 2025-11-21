<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use Symfony\Component\HttpFoundation\Response;

class PostController extends Controller
{
    /**
     * GET /api/posts
     * List posts with search + filtering
     */
    public function index(Request $request)
    {
        try {
            $query = Post::with('author');

            // search
            if ($search = $request->query('search')) {
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhereHas('author', function ($qa) use ($search) {
                          $qa->where('name', 'like', "%{$search}%");
                      })
                      ->orWhere('category', 'like', "%{$search}%");
                });
            }

            // filter by category
            if ($category = $request->query('category')) {
                $query->where('category', $category);
            }

            // filter by author id
            if ($author = $request->query('author_id')) {
                $query->where('author_id', $author);
            }

            // date range
            if ($request->has(['from', 'to'])) {
                $query->whereBetween('created_at', [
                    $request->query('from'),
                    $request->query('to')
                ]);
            }

            // sorting
            if ($sort = $request->query('sort')) {
                $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
                $sort = ltrim($sort, '-');
                $query->orderBy($sort, $direction);
            } else {
                $query->latest();
            }

            $perPage = intval($request->query('per_page', 10));
            $posts = $query->paginate($perPage)->appends($request->query());

            return PostResource::collection($posts);

        } catch (\Exception $e) {
            Log::error("POST INDEX ERROR: " . $e->getMessage());

            return response()->json([
                'error'   => 'Failed to fetch posts',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * POST /api/posts
     * Create a new blog post
     */
    public function store(StorePostRequest $request)
    {
        try {
            $user = auth('api')->user();

            // AUTHOR role only (admins can also create)
            if (!$user->hasAnyRole(['admin', 'author'])) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'You are not allowed to create posts.'
                ], Response::HTTP_FORBIDDEN);
            }

            $data = $request->validated();
            $data['author_id'] = $user->id;

            $post = Post::create($data);

            return new PostResource($post->load('author'));

        } catch (\Exception $e) {
            Log::error("POST CREATE ERROR: " . $e->getMessage());

            return response()->json([
                'error' => 'Failed to create post',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * GET /api/posts/{post}
     */
    public function show($id)
    {
        try {
            $post = Post::with('author','comments.user')->find($id);

            if (!$post) {
                return response()->json([
                    'error' => 'Post Not Found',
                    'message' => "No post found with ID {$id}"
                ], Response::HTTP_NOT_FOUND);
            }

            return new PostResource($post);

        } catch (\Exception $e) {
            Log::error("POST SHOW ERROR: " . $e->getMessage());

            return response()->json([
                'error' => 'Failed to show post',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * PUT /api/posts/{post}
     * Admin OR Owner can update
     */
    public function update(UpdatePostRequest $request, $id)
    {
        try {
            $post = Post::find($id);

            if (!$post) {
                return response()->json([
                    'error' => 'Post Not Found',
                    'message' => "No post found with ID {$id}"
                ], Response::HTTP_NOT_FOUND);
            }

            $user = auth('api')->user();

            // admin or owner
            if (!$user->hasRole('admin') && $user->id !== $post->author_id) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'Only admins or the post owner can update this post.'
                ], Response::HTTP_FORBIDDEN);
            }

            $post->update($request->validated());

            return new PostResource($post->fresh()->load('author'));

        } catch (\Exception $e) {
            Log::error("POST UPDATE ERROR: " . $e->getMessage());

            return response()->json([
                'error' => 'Failed to update post',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * DELETE /api/posts/{post}
     * Admin OR Owner can delete
     */
    public function destroy($id)
    {
        try {
            $post = Post::find($id);

            if (!$post) {
                return response()->json([
                    'error' => 'Post Not Found',
                    'message' => "No post found with ID {$id}"
                ], Response::HTTP_NOT_FOUND);
            }

            $user = auth('api')->user();

            //dd($user->getRoleNames());

            if (!$user->hasRole('admin') && $user->id !== $post->author_id) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'Only admins or the post owner can delete this post.'
                ], Response::HTTP_FORBIDDEN);
            }

            $post->delete();

            return response()->json([
                'message' => 'Post deleted successfully'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            Log::error("POST DELETE ERROR: " . $e->getMessage());

            return response()->json([
                'error' => 'Failed to delete post',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

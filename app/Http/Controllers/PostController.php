<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Http\Resources\PostCollection;

class PostController extends Controller
{
    // GET /api/posts
    public function index(Request $request)
    {
        $query = Post::with('author');

        // search
        if ($search = $request->query('search')) {
            $query->where(function($q) use ($search) {
                $q->where('title','like',"%{$search}%")
                  ->orWhereHas('author', function($qa) use ($search) {
                      $qa->where('name','like',"%{$search}%");
                  })
                  ->orWhere('category','like',"%{$search}%");
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

        // date range: ?from=YYYY-MM-DD&to=YYYY-MM-DD
        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        // sorting optional: ?sort=created_at or -created_at for desc
        if ($sort = $request->query('sort')) {
            $direction = 'asc';
            if (substr($sort,0,1) === '-') {
                $direction = 'desc';
                $sort = substr($sort,1);
            }
            $query->orderBy($sort, $direction);
        } else {
            $query->latest();
        }

        $perPage = intval($request->query('per_page', 10));
        $posts = $query->paginate($perPage)->appends($request->query());

        return PostResource::collection($posts);
    }

    // POST /api/posts
    public function store(StorePostRequest $request)
    {
        $user = auth('api')->user();

        $data = $request->validated();
        $data['author_id'] = $user->id; // ensure assigned to logged in user

        $post = Post::create($data);

        return new PostResource($post->load('author'));
    }

    // GET /api/posts/{post}
    public function show(Post $post)
    {
        return new PostResource($post->load('author','comments.user'));
    }

    // PUT /api/posts/{post}
    public function update(UpdatePostRequest $request, Post $post)
    {
        $this->authorize('update', $post);

        $post->update($request->validated());

        return new PostResource($post->fresh()->load('author'));
    }

    // DELETE /api/posts/{post}
    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);
        $post->delete();
        return response()->json(['message' => 'Post deleted']);
    }
}

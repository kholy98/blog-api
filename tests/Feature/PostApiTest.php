<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class PostApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $author;
    protected User $otherAuthor;

    public function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'author']);

        // Create users
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->author = User::factory()->create();
        $this->author->assignRole('author');

        $this->otherAuthor = User::factory()->create();
        $this->otherAuthor->assignRole('author');

        // Create posts
        Post::factory()->create([
            'title' => 'Admin Post',
            'author_id' => $this->admin->id,
            'category' => 'Technology'
        ]);

        Post::factory()->create([
            'title' => 'Author Post',
            'author_id' => $this->author->id,
            'category' => 'Lifestyle'
        ]);
    }

    /** @test */
    public function can_list_posts_with_filters_and_search()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/posts?search=Admin&category=Technology');

        $response->assertStatus(200)
                 ->assertJsonFragment(['title' => 'Admin Post'])
                 ->assertJsonMissing(['title' => 'Author Post']);
    }

    /** @test */
    public function admin_can_create_post()
    {
        $data = [
            'title' => 'New Admin Post',
            'content' => 'Content here',
            'category' => 'Education'
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/posts', $data);

        $response->assertStatus(201)
            ->assertJsonFragment(['title' => 'New Admin Post'])
            ->assertJsonPath('data.author.id', $this->admin->id);
    }

    /** @test */
    public function author_can_create_post()
    {
        $data = [
            'title' => 'New Author Post',
            'content' => 'Content here',
            'category' => 'Education'
        ];

        $response = $this->actingAs($this->author, 'api')
            ->postJson('/api/posts', $data);

        $response->assertStatus(201)
            ->assertJsonFragment(['title' => 'New Author Post'])
            ->assertJsonPath('data.author.id', $this->author->id);
    }

    /** @test */
    public function non_authorized_user_cannot_create_post()
    {
        $user = User::factory()->create(); // no role
        $data = [
            'title' => 'Unauthorized Post',
            'content' => 'Content here',
            'category' => 'Education'
        ];

        $response = $this->actingAs($user, 'api')->postJson('/api/posts', $data);

        $response->assertStatus(403)
                 ->assertJsonFragment(['error' => 'Unauthorized']);
    }

    /** @test */
    public function can_show_single_post_with_author_and_comments()
    {
        $post = Post::first();
        Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $this->author->id,
            'body' => 'Test comment'
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/posts/{$post->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('data.id', $post->id)
                 ->assertJsonPath('data.author.id', $post->author_id)
                 ->assertJsonFragment(['body' => 'Test comment']);
    }

    /** @test */
    public function author_can_update_own_post_but_not_others()
    {
        $ownPost = Post::where('author_id', $this->author->id)->first();
        $otherPost = Post::where('author_id', $this->admin->id)->first();

        $updateData = ['title' => 'Updated Title'];

        // Own post
        $response = $this->actingAs($this->author, 'api')
            ->putJson("/api/posts/{$ownPost->id}", $updateData);

        $response->assertStatus(200)
                 ->assertJsonFragment(['title' => 'Updated Title']);

        // Other's post
        $response = $this->actingAs($this->author, 'api')
            ->putJson("/api/posts/{$otherPost->id}", $updateData);

        $response->assertStatus(403)
                 ->assertJsonFragment(['error' => 'Unauthorized']);
    }

    /** @test */
    public function admin_can_update_any_post()
    {
        $post = Post::where('author_id', $this->author->id)->first();
        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/posts/{$post->id}", ['title' => 'Admin Update']);

        $response->assertStatus(200)
                 ->assertJsonFragment(['title' => 'Admin Update']);
    }

    /** @test */
    public function author_can_delete_own_post_but_not_others()
    {
        $ownPost = Post::where('author_id', $this->author->id)->first();
        $otherPost = Post::where('author_id', $this->admin->id)->first();

        $response = $this->actingAs($this->author, 'api')
            ->deleteJson("/api/posts/{$ownPost->id}");
        $response->assertStatus(200);

        $response = $this->actingAs($this->author, 'api')
            ->deleteJson("/api/posts/{$otherPost->id}");
        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_delete_any_post()
    {
        $post = Post::where('author_id', $this->author->id)->first();
        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/posts/{$post->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function authenticated_user_can_comment_on_post()
    {
        $post = Post::first();
        $commentData = ['body' => 'This is a comment'];

        $response = $this->actingAs($this->author, 'api')
            ->postJson("/api/posts/{$post->id}/comments", $commentData);

        $response->assertStatus(201)
                 ->assertJsonFragment(['body' => 'This is a comment'])
                 ->assertJsonPath('user.id', $this->author->id);
    }

    /** @test */
    public function unauthenticated_user_cannot_comment()
    {
        $post = Post::first();
        $commentData = ['body' => 'This is a comment'];

        $response = $this->postJson("/api/posts/{$post->id}/comments", $commentData);
        $response->assertStatus(401); // Unauthorized
    }
}

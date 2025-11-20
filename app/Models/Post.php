<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Post extends Model
{
    use HasFactory;

    protected $fillable = ['title','content','author_id','category'];

    public function author()
    {
        return $this->belongsTo(User::class,'author_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}

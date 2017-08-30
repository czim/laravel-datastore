<?php
namespace Czim\DataStore\Test\Helpers\Models;

use Illuminate\Database\Eloquent\Model;

class TestComment extends Model
{
    protected $fillable = [ 'title', 'body' ];

    public function post()
    {
        return $this->belongsTo(TestPost::class, 'test_post_id');
    }

    public function author()
    {
        return $this->belongsTo(TestAuthor::class, 'test_author_id');
    }

    public function tags()
    {
        return $this->morphMany(TestTag::class, 'taggable');
    }
    
}

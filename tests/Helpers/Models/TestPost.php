<?php
namespace Czim\DataStore\Test\Helpers\Models;

use Illuminate\Database\Eloquent\Model;

class TestPost extends Model
{
    protected $fillable = ['title', 'body'];

    public function authors()
    {
        return $this->belongsToMany(TestAuthor::class);
    }

    public function comments()
    {
        return $this->hasMany(TestComment::class, 'test_post_id');
    }

    public function genre()
    {
        return $this->belongsTo(TestGenre::class, 'test_genre_id');
    }

    public function tags()
    {
        return $this->morphMany(TestTag::class, 'taggable');
    }

    
    public function someOtherRelationMethod()
    {
        return $this->belongsTo(TestGenre::class, 'test_genre_id');
    }

    public function commentHasOne()
    {
        return $this->hasOne(TestComment::class, 'test_post_id');
    }

    public function specials()
    {
        return $this->hasMany(TestSpecial::class, 'test_post_id');
    }

}

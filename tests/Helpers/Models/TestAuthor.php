<?php
namespace Czim\DataStore\Test\Helpers\Models;

use Illuminate\Database\Eloquent\Model;

class TestAuthor extends Model
{
    protected $fillable = [ 'name' ];

    public function posts()
    {
        return $this->belongsToMany(TestPost::class);
    }

    public function comments()
    {
        return $this->hasMany(TestComment::class, 'test_author_id');
    }
}

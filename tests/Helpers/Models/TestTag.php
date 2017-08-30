<?php
namespace Czim\DataStore\Test\Helpers\Models;

use Illuminate\Database\Eloquent\Model;

class TestTag extends Model
{
    protected $fillable = [ 'name' ];

    public function posts()
    {
        return $this->morphTo(TestPost::class, 'taggable');
    }

    public function comments()
    {
        return $this->morphTo(TestComment::class, 'taggable');
    }

}

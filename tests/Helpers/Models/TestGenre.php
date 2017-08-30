<?php
namespace Czim\DataStore\Test\Helpers\Models;

use Illuminate\Database\Eloquent\Model;

class TestGenre extends Model
{
    protected $fillable = [ 'name' ];

    public function posts()
    {
        return $this->hasMany(TestPost::class, 'genre_id');
    }

}

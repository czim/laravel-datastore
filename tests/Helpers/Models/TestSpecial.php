<?php
namespace Czim\DataStore\Test\Helpers\Models;

use Illuminate\Database\Eloquent\Model;

class TestSpecial extends Model
{
    protected $primaryKey = 'special';
    public $incrementing = false;

    protected $fillable = [ 'special', 'name' ];

    public function post()
    {
        return $this->belongsTo(TestPost::class, 'test_post_id');
    }

}

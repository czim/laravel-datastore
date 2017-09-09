<?php
namespace Czim\DataStore\Test\Helpers\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TestTag
 *
 * @property integer $id
 * @property string  $name
 * @property string  $taggable_type
 * @property integer $taggable_id
 */
class TestTag extends Model
{
    protected $fillable = [ 'name' ];

    public function taggable()
    {
        return $this->morphTo('taggable');
    }

    public function posts()
    {
        return $this->morphedByMany(TestPost::class, 'taggable', 'taggables');
    }

}

<?php
namespace Czim\DataStore\Test\Helpers\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TestGenre
 *
 * @property integer $id
 * @property string $name
 */
class TestGenre extends Model
{
    protected $fillable = [ 'name' ];

    public function posts()
    {
        return $this->hasMany(TestPost::class, 'genre_id');
    }

}

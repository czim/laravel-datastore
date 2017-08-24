<?php
namespace Czim\DataStore\Test\Helpers\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TestModel
 *
 * @property integer        $id
 * @property string         $name
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 */
class TestModel extends Model
{

    protected $fillable = [
        'name',
    ];

    public function testRelatedModels()
    {
        return $this->hasMany(TestRelatedModel::class);
    }

    public function testMorphRelatedModels()
    {
        return $this->morphMany(TestMorphRelatedModel::class, 'morphable');
    }

    public function notARelation()
    {
        return false;
    }

    public function throwsAnException()
    {
        throw new Exception('testing');
    }

}

<?php
namespace Czim\DataStore\Test\Helpers\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TestRelatedModel
 *
 * @property integer        $id
 * @property integer        $test_model_id
 * @property string         $name
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 */
class TestRelatedModel extends Model
{

    protected $fillable = [
        'name',
        'test_model_id',
    ];

    public function testModels()
    {
        return $this->belongsTo(TestModel::class);
    }

}

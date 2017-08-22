<?php
namespace Czim\DataStore\Test\Helpers\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TestMorphRelatedModel
 *
 * @property integer        $id
 * @property string         $name
 * @property integer        $morphable_id
 * @property string         $morphable_type
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 */
class TestMorphRelatedModel extends Model
{

    protected $fillable = [
        'name',
        'morphable',
    ];

    public function morphable()
    {
        return $this->morphTo();
    }

}

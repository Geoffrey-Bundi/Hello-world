<?php namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Expected extends Model
{
  	/**
  	 * Enabling soft deletes for expected results.
  	 *
  	 */
  	use SoftDeletes;
  	protected $dates = ['deleted_at'];

  	/**
  	 * The database table used by the model.
  	 *
  	 * @var string
  	 */
  	protected $table = 'expected_results';
    /**
  	 * Type of result
  	 *
  	 */
  	const NEGATIVE = 0;
  	const POSITIVE = 1;
    /**
  	 * Pt relationship
  	 *
  	 */
     public function pt()
     {
          return $this->belongsTo('App\Models\Pt');
     }
    /**
  	 * Field relationship
  	 *
  	 */
     public function field()
     {
          return $this->belongsTo('App\Models\Field');
     }
}

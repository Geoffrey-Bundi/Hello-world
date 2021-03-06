<?php namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Pt extends Model
{
  	/**
  	 * Enabling soft deletes for pt survey.
  	 *
  	 */
  	use SoftDeletes;
  	protected $dates = ['deleted_at'];

  	/**
  	 * The database table used by the model.
  	 *
  	 * @var string
  	 */
  	protected $table = 'pt';
    /**
  	 * Receipt relationship
  	 *
  	 */
     public function receipt()
     {
          return $this->belongsTo('App\Models\Receipt');
     }
    /**
  	 * Result relationship
  	 *
  	 */
     public function results()
     {
          return $this->hasMany('App\Models\Result');
     }
    /**
  	 * Tester relationship
  	 *
  	 */
     public function user()
     {
          return $this->belongsTo('App\Models\User');
     }
}

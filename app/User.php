<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Zizaco\Entrust\Traits\EntrustUserTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

use DB;

class User extends Model implements AuthenticatableContract,
                                    AuthorizableContract,
                                    CanResetPasswordContract
{
    use Authenticatable, CanResetPassword, EntrustUserTrait{
EntrustUserTrait::restore insteadof SoftDeletes;
}
    use Notifiable;
    use SoftDeletes;
    const MALE = 0;
    const FEMALE = 1;

    //  Tester ID ranges
    const ZERO = 0;
    const ONE = 1;
    const TWO = 2;
    const THREE = 3;
    const FOUR = 4;
    const FIVE = 5;
    const SIX = 6;
    const SEVEN = 7;
    const EIGHT = 8;
    const NINE = 9;
    //	Default password
	  const DEFAULT_PASSWORD = 'secret';
    //  Tester Designations
    const NURSE = 1;
    const LABTECH = 2;
    const COUNSELLOR = 3;
    const RCO = 4;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'email', 'password', 'username', 'address', 'phone', 'gender', 'designation'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];
    public function getIsAdminAttribute()
    {
        return true;
    }
    /**
     * Get the admin user currently the first user
     *
     * @return User model
     */
    public static function getAdminUser()
    {
        return User::find(1);
    }

    /**
  	 * Return lot
  	 */
  	public function lot($round)
  	{
        $last = substr($this->uid, -1);
        $lot = Lot::where('round_id', $round)->where('tester_id', 'LIKE', '%'.$last.'%')->first();
        return $lot;
  	}
    /**
	  * role-user
	  *
	  * @return RoleUserTier object
	  */
  	public function ru()
  	{
        $res = DB::table('role_user')->where('user_id', $this->id)->first();
        return $res;
  	}
    /**
  	 * Return tester-id-range
  	 */
  	public function idRange($id)
  	{
          $last = substr($id, -1);
          if($last == 0 || $last == 1 || $last == 2)
                return User::ZERO_TO_TWO;
          else if($last == 3 || $last == 4 || $last == 5)
                return User::THREE_TO_FIVE;
          else if($last == 6 || $last == 7 || $last == 8)
                return User::SIX_TO_EIGHT;
          else if($last == 9)
                return User::NINE;
  	}
    /**
	* Return User ID given the uid
	* @param $uid the unique ID of the field
	*/
	public static function idByUID($uid=NULL)
	{
		if($uid!=NULL)
		{
			try 
			{
				$count = User::where('uid', $uid)->count();
        if($count > 0)
        {
            $user = User::where('uid', $uid)->orderBy('uid', 'asc')->first();
            return $user->id;
        }
        else
        {
            return null;
        }
			} 
			catch (ModelNotFoundException $e) 
			{
				Log::error("The user ` $uid ` does not exist:  ". $e->getMessage());
				//TODO: send email?
				return null;
			}
		}
		else
		{
			return null;
		}
	}
    /**
  	 * Return readable gender
  	 */
  	public static function gender($gender)
  	{
  		  if($gender == "Male")
              return USER::MALE;
          else
              return USER::FEMALE;;
  	}
    /**
    * Check if new user...use intended tester lot
    *
    * @return user object
    */
    public function registration()
    {
        return $this->hasOne('App\Registration');
    }

    /**
     * Check if user is County Coordinator
     *
     * @return User model
     */
    public function isCountyCoordinator()
    {
        if($this->hasRole('County Coordinator'))
            return true;
        else
            return false;
    }
    /**
     * Check if user is Sub-County Coordinator
     *
     * @return User model
     */
    public function isSubCountyCoordinator()
    {
        if($this->hasRole('Sub-County Coordinator'))
            return true;
        else
            return false;
    }
    /**
     * Check if user is Facility Incharge
     *
     * @return User model
     */
    public function isFacilityInCharge()
    {
        if($this->hasRole('Facility Incharge'))
            return true;
        else
            return false;
    }
    /**
     * Check if user is Participant
     *
     * @return User model
     */
    public function isParticipant()
    {
        if($this->hasRole('Participant'))
            return true;
        else
            return false;
    }
    /**
    * Return User ID given the name
    *
    */
    public static function idByName($name=NULL)
    {
        if($name!=NULL)
        {
            try 
            {
                $count = User::where('name', $name)->count();
                if($count > 0)
                {
                    $user = User::where('name', $name)->orderBy('name', 'asc')->first();
                    return $user->id;
                }
                else
                {
                    return null;
                }
            } 
            catch (ModelNotFoundException $e) 
            {
                Log::error("The user ` $name ` does not exist:  ". $e->getMessage());
                //TODO: send email?
                return null;
            }
        }
        else
        {
            return null;
        }
    }
    /**
    * Return User ID given the email
    *
    */
    public static function idByEmail($email=NULL)
    {
        if($email!=NULL)
        {
            try 
            {
                $count = User::where('email', $email)->count();
                if($count > 0)
                {
                    $user = User::where('email', $email)->orderBy('name', 'asc')->first();
                    return $user->id;
                }
                else
                {
                    return null;
                }
            } 
            catch (ModelNotFoundException $e) 
            {
                Log::error("The user with email ` $email ` does not exist:  ". $e->getMessage());
                //TODO: send email?
                return null;
            }
        }
        else
        {
            return null;
        }
    }
    /**
    * Pt relationship
    *
    */
    public function pt()
    {
        return $this->hasMany('App\Pt');
    }
    public function getEmailVerificationUrlAttribute()
    {
        return url('/email/verify/' . $this->verification_code);
    }
    /**
    * Return user designation
    *
    */
    public function designation($des)
    {
        if($des)
        {
            if($des == User::NURSE)
                return "Nurse";
            else if($des == User::LABTECH)
                return "Lab Tech.";
            else if($des == User::COUNSELLOR)
                return "Counsellor";
            else if($des == User::RCO)
                return "RCO";
        }
        return "N/A";
    }
    /**
     * Return readable gender
     */
    public static function sex($gender)
    {
        if($gender == "Male")
              return USER::MALE;
          else
              return USER::FEMALE;;
    }

     /**
     * Detach all roles from a user
     *
     * @return object
     */
    public function detachAllRoles()
    {
        DB::table('role_user')->where('user_id', $this->id)->delete();
        return $this;
    }
}
<?php
namespace SpaceXStats\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

use SpaceXStats\Library\Enums\ObjectPublicationStatus;
use SpaceXStats\Library\Enums\UserRole;
use SpaceXStats\Library\Enums\VisibilityStatus;
use SpaceXStats\Presenters\PresentableTrait;
use SpaceXStats\Presenters\UserPresenter;

use Carbon\Carbon;
use Auth;
use Hash;
use Input;

class User extends Model implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword;

    use PresentableTrait;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';
    protected $primaryKey = 'user_id';
    public $timestamps = true;

    protected $hidden = ['password', 'remember_token', 'email', 'mobile_national_format', 'mobile_country_code', 'mobile_carrier', 'subscription_expiry', 'key', 'last_login'];
    protected $appends = [];
    protected $fillable = [];
    protected $guarded = ['role_id', 'username','email','password', 'key'];
    protected $dates = ['subscription_expiry'];

    protected $presenter = UserPresenter::class;

    // Relations
    public function profile() {
        return $this->hasOne('SpaceXStats\Models\Profile');
    }

    public function objects() {
        return $this->hasMany('SpaceXStats\Models\Object');
    }

    public function favorites() {
        return $this->hasMany('SpaceXStats\Models\Favorite');
    }

    public function notes() {
        return $this->hasMany('SpaceXStats\Models\Note');
    }

    public function role() {
        return $this->belongsTo('SpaceXStats\Models\Role');
    }

    public function notifications() {
        return $this->hasMany('SpaceXStats\Models\Notification');
    }

    public function emails() {
        return $this->hasManyThrough('SpaceXStats\Models\Email', 'SpaceXStats\Models\Notification');
    }

    public function messages() {
        return $this->hasMany('SpaceXStats\Models\Message');
    }

    public function comments() {
        return $this->hasMany('SpaceXStats\Models\Comment');
    }

    public function awards() {
        return $this->hasMany('SpaceXStats\Models\Award');
    }

    // Conditional relations
    public function objectsInMissionContr() {
        if (Auth::isAdmin()) {
            return $this->hasMany('SpaceXStats\Models\Object')->where('status', ObjectPublicationStatus::PublishedStatus);
        }
        return $this->hasMany('SpaceXStats\Models\Object')
            ->where('status', ObjectPublicationStatus::PublishedStatus)
            ->where('visibility', VisibilityStatus::DefaultStatus);
    }

    public function unreadMessages() {
        return $this->hasMany('SpaceXStats\Models\Message')->where('hasBeenRead', false);
    }

    // Helpers
    public function isValidForSignUp($input) {
        $rules = array(
            'username' => 'required|unique:users,username|min:3|alpha_dash|varchar:tiny',
            'email' => 'required|unique:users,email|email|varchar:tiny',
            'password' => 'required|confirmed|min:6',
            'eula' => 'required|accepted'
        );

        $messages = array(
            'eula.required' => 'Please confirm you agree with the End User License Agreement'
        );

        $validator = Validator::make($input, $rules, $messages);
        return $validator->passes() ? true : $validator->errors();
    }

    public function isValidKey($email, $key) {
        $user = User::where('email', urldecode($email))->where('key', $key)->firstOrFail();
        if (!empty($user)) {
            $user->role_id = UserRole::Member;
            return $user->save();
        }
    }

    public function isLaunchController() {
        return $this->launchControllerFlag == true;
    }

    public function setMobileDetails($number) {
        $this->mobile = $number->phone_number;
        $this->mobile_national_format = $number->national_format;
        $this->mobile_country_code = $number->country_code;
        $this->mobile_carrier = isset($number->carrier->name) ? $number->carrier->name : null;
    }

    public function resetMobileDetails() {
        $this->mobile = null;
        $this->mobile_national_format = null;
        $this->mobile_country_code = null;
        $this->mobile_carrier = null;
    }

    /**
     * Increment the user's Mission Control subscription by the given number of seconds if they are a
     * Mission Control subscriber.
     *
     * @param $secondsToIncrement   integer     The number of seconds to increment a user subscription by.
     */
    public function incrementSubscription($secondsToIncrement) {
        if ($this->role_id == UserRole::Subscriber) {
            $this->subscription_expires_at->addSeconds($secondsToIncrement);
        }
    }

    // Attribute accessors
    public function getDaysUntilSubscriptionExpiresAttribute() {
        return Carbon::now()->diffInDays($this->subscription_expires_at);
    }

    // Attribute mutators
    public function setPasswordAttribute($value) {
        $this->attributes['password'] = Hash::make($value);
    }
}

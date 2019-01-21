<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;

use App\Helpers\Helper;

use Setting;

class User extends Authenticatable
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password','user_type','device_type','login_by',
        'picture','is_activated', 'timezone', 'verification_code' , 
        'verification_code_expiry'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function userHistory()
    {
        return $this->hasMany('App\UserHistory');
    }

    public function subProfile()
    {
        return $this->hasMany('App\SubProfile');
    }

    public function userRating()
    {
        return $this->hasMany('App\UserRating');
    }

    public function userWishlist()
    {
        return $this->hasMany('App\Wishlist');
    }

    public function userPayment()
    {
        return $this->hasMany('App\UserPayment');
    }

    public function loggedDevice()
    {
        return $this->hasMany('App\UserLoggedDevice');
    }

    /**
     * Get the flag record associated with the user.
     */
    public function userFlag()
    {
        return $this->hasMany('App\Flag', 'user_id', 'id');
    }

    /**
     * Get the pay per view record associated with the user.
     */
    public function userVideoSubscription()
    {
        return $this->hasMany('App\PayPerView', 'user_id', 'id');
    }

    /**
     * Get the continueWatchingVideo record associated with the user.
     */
    public function continueWatchingVideo()
    {
        return $this->hasMany('App\ContinueWatchingVideo', 'user_id', 'id');
    }

     /**
     * Boot function for using with User Events
     *
     * @return void
     */

    public static function boot()
    {
        //execute the parent's boot method 
        parent::boot();

        //delete your related models here, for example
        static::deleting(function($user)
        {
            if (count($user->userHistory) > 0) {

                foreach($user->userHistory as $history)
                {
                    $history->delete();
                } 

            }

            if (count($user->loggedDevice) > 0) {

                foreach($user->loggedDevice as $logged)
                {
                    $logged->delete();
                } 

            }

            if (count($user->userRating) > 0) {

                foreach($user->userRating as $rating)
                {
                    $rating->delete();
                } 

            }

            if (count($user->userWishlist) > 0) {

                foreach($user->userWishlist as $wishlist)
                {
                    $wishlist->delete();
                } 

            }

            if (count($user->userFlag) > 0) {

                foreach($user->userFlag as $flag)
                {
                    $flag->delete();
                }

            }

            if (count($user->userVideoSubscription) > 0) {

                foreach($user->userVideoSubscription as $video)
                {
                    $video->delete();
                }
            }

            if (count($user->userPayment) > 0) {

                foreach($user->userPayment as $payment)
                {
                    $payment->delete();
                } 
            }

             if (count($user->subProfile) > 0) {

                foreach($user->subProfile as $profile) {
                    
                    Helper::delete_picture($profile->picture , '/uploads/images/');

                    $profile->delete();
                } 
            }

             if (count($user->continueWatchingVideo) > 0) {

                foreach($user->continueWatchingVideo as $continue_watching_video)
                {
                    $continue_watching_video->delete();
                }

            }
        }); 

        static::creating(function ($model) {

            if (Setting::get('email_verify_control')) { 

                if($model->login_by == 'manual') {

                    $model->generateEmailCode();

                } else {

                    $model->attributes['is_verified'] = 1;

                }

            } else {

                $model->attributes['is_verified'] = 1;
            }

        });
    }


    /**
     * Generates Token and Token Expiry
     * 
     * @return bool returns true if successful. false on failure.
     */

    protected function generateEmailCode() {

        $this->attributes['verification_code'] = Helper::generate_email_code();

        $this->attributes['verification_code_expiry'] = Helper::generate_email_expiry();

        $this->attributes['is_verified'] = 0;

        return true;
    }

}

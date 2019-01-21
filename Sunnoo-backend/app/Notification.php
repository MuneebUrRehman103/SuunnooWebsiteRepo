<?php

namespace App;

use App\User;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    //

    public static function save_notification($video_id) {

        \Log::info("Notification Inside");

    	$users = User::where('is_verified', 1)->get();

        \Log::info("Count of users ".count($users));

    	foreach ($users as $key => $value) {
    		
    		$model = new Notification;

    		$model->user_id = $value->id;

    		$model->admin_video_id = $video_id;

    		$model->status = 0;

    		$model->save();

    	}

    }


    public function adminVideo() {
        return $this->hasOne('App\AdminVideo', 'id', 'admin_video_id');
    }
}

<?php 

   namespace App\Helpers;

    use Hash;

    use App\Admin;

    use App\User;

    use App\AdminVideo;

    use App\AdminVideoImage;

    use App\Category;

    use App\SubCategory;

    use App\SubCategoryImage;

    use App\Wishlist;

    use App\UserHistory;

    use App\UserRating;

    use App\UserPayment;

    use App\LikeDislikeVideo;

    use Exception;

    use Auth;

    use AWS;

    use App\Requests;

    use Mail;

    use File;

    use Log;

    use Storage;

    use Setting;

    use DB;

    use App\Jobs\OriginalVideoCompression;

    use Mailgun\Mailgun;

    use App\ContinueWatchingVideo;

    use App\EmailTemplate;

    class Helper
    {
        public static function clean($string)
        {
            $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.

            return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
        }

        public static function web_url()
        {
            return url('/');
        }

        public static function generate_email_code($value = "")
        {
            return uniqid($value);
        }

        public static function generate_email_expiry()
        {
            return time() + 24*3600*30;  // 30 days
        }

        // Check whether email verification code and expiry

        public static function check_email_verification($verification_code , $user_id , &$error) {

            if(!$user_id) {

                $error = tr('user_id_empty');

                return FALSE;

            } else {

                $user_details = User::find($user_id);
            }

            // Check the data exists

            if($user_details) {

                // Check whether verification code is empty or not

                if($verification_code) {

                    // Log::info("Verification Code".$verification_code);

                    // Log::info("Verification Code".$user_details->verification_code);

                    if ($verification_code ===  $user_details->verification_code ) {

                        // Token is valid

                        $error = NULL;

                        // Log::info("Verification CODE MATCHED");

                        return true;

                    } else {

                        $error = tr('verification_code_mismatched');

                        // Log::info(print_r($error,true));

                        return FALSE;
                    }

                }
                    
                // Check whether verification code expiry 

                if ($user_details->verification_code_expiry > time()) {

                    // Token is valid

                    $error = NULL;

                    Log::info(tr('token_expiry'));

                    return true;

                } else if($user_details->verification_code_expiry < time() || (!$user_details->verification_code || !$user_details->verification_code_expiry) ) {

                    $user_details->verification_code = Helper::generate_email_code();
                    
                    $user_details->verification_code_expiry = Helper::generate_email_expiry();
                    
                    $user_details->save();

                    // If code expired means send mail to that user

                    $subject = tr('verification_code_title');
                    $email_data = $user_details;
                    $page = "emails.welcome";
                    $email = $user_details->email;
                    $result = Helper::send_email($page,$subject,$email,$email_data);

                    $error = tr('verification_code_expired');

                    Log::info(print_r($error,true));

                    return FALSE;
                }
           
            }

        }

        // Note: $error is passed by reference
        public static function is_token_valid($entity, $id, $token, &$error)
        {
            if (
                ( $entity== 'USER' && ($row = User::where('id', '=', $id)->where('token', '=', $token)->first()))
            ) {
                if ($row->token_expiry > time()) {
                    // Token is valid
                    $error = NULL;
                    return $row;
                } else {
                    $error = array('success' => false, 'error_messages' => Helper::get_error_message(103), 'error_code' => 103);
                    return FALSE;
                }
            }
            $error = array('success' => false, 'error_messages' => Helper::get_error_message(104), 'error_code' => 104);
            return FALSE;
        }

        // Convert all NULL values to empty strings
        public static function null_safe($arr)
        {
            $newArr = array();
            foreach ($arr as $key => $value) {
                $newArr[$key] = ($value == NULL) ? "" : $value;
            }
            return $newArr;
        }

        public static function generate_token()
        {
            return Helper::clean(Hash::make(rand() . time() . rand()));
        }

        public static function generate_token_expiry()
        {
            return time() + Setting::get('token_expiry_hour')*3600;  // 1 Hour
        }

        public static function send_email($page,$subject,$email,$email_data)
        {
            \Log::info(envfile('MAIL_USERNAME'));

            \Log::info(envfile('MAIL_PASSWORD'));

            if( config('mail.username') &&  config('mail.password')) {

                try {

                    $site_url=url('/');

                    $isValid = 1;

                    if(envfile('MAIL_DRIVER') == 'mailgun' && Setting::get('MAILGUN_PUBLIC_KEY')) {

                        Log::info("isValid - STRAT");

                        # Instantiate the client.

                        $email_address = new Mailgun(Setting::get('MAILGUN_PUBLIC_KEY'));

                        $validateAddress = $email;
                        
                        # Issue the call to the client.
                        $result = $email_address->get("address/validate", array('address' => $validateAddress));

                        # is_valid is 0 or 1

                        $isValid = $result->http_response_body->is_valid;
                        
                        Log::info("isValid FINAL STATUS - ".$isValid);

                    }

                    if($isValid) {

                        /* $template = EmailTemplate::where('template_type', $email_data['template_type'])->first();

                        $content = "";

                        if ($template) {

                            $content = $template->content;

                            $content = isset($email_data['email']) ? str_replace('&lt;%email%&gt;',$email_data['email'], $content) : $content;

                            $content = isset($email_data['password']) ? str_replace('&lt;%password%&gt;',$email_data['password'],$content) : $content;

                            $content = str_replace('%&lt;site_name&gt;%', Setting::get('site_name'),$content);

                        }

                        $email_data['content'] = $content; */

                        if (Mail::queue($page, array('email_data' => $email_data,'site_url' => $site_url), 
                                function ($message) use ($email, $subject) {

                                    $message->to($email)->subject($subject);
                                }
                        )) {

                            return Helper::get_message(106);

                        } else {

                            throw new Exception(Helper::get_error_message(123));
                            
                        }

                    } else {

                        return Helper::get_message(106);

                    }

                } catch(\Exception $e) {
                    
                    return $e->getMessage();

                }
            

            } else {

                return Helper::get_error_message(123);

            }
        }

        public static function get_error_message($code)
        {
            switch($code) {
                case 3000: 
                    $string = tr('user_record_deleted_contact_admin');
                    break;
                case 101:
                    $string = tr('invalid_input');
                    break;
                case 102:
                    $string = tr('email_address_already_use');
                    break;
                case 103:
                    $string = tr('token_expiry');
                    break;
                case 104:
                    $string = tr('invalid_token');
                    break;
                case 105:
                    $string = tr('username_password_donot_match');
                    break;
                case 106:
                    $string = tr('all_fields_required');
                    break;
                case 107:
                    $string = tr('current_password_incorrect');
                    break;
                case 108:
                    $string = tr('password_not_correct');
                    break;
                case 109:
                    $string = tr('application_encountered_unknown');
                    break;
                case 111:
                    $string = tr('email_not_activated');
                    break;
                case 115:
                    $string = tr('invalid_refresh_token');
                    break;
                case 123:
                    $string = tr('something_went_wrong_error');
                    break;
                case 124:
                    $string = tr('email_not_registered');
                    break;
                case 125:
                    $string = tr('not_valid_social_register');
                    break;
                case 130:
                    $string = tr('no_result_found');
                    break;
                case 131:
                    $string = tr('old_password_wrong_password_doesnot_match');
                    break;
                case 132:
                    $string = tr('provider_id_not_found');
                    break;
                case 133:
                    $string = tr('user_id_not_found');
                    break;
                case 141:
                    $string = tr('something_went_wrong_paying_amount');
                    break;
                case 144:
                    $string = tr('please_verify_your_account');
                    break;
                case 145:
                    $string = tr('video_already_added_history');
                    break;
                case 146:
                    $string = tr('something_wrong_please_try_again');
                    break;

                case 147:
                    $string = tr('redeem_disabled_by_admin');
                    break;
                case 148:
                    $string = tr('minimum_redeem_not_have');
                    break;
                case 149:
                    $string = tr('redeem_wallet_empty');
                    break;
                case 150:
                    $string = tr('redeem_request_status_mismatch');
                    break;
                case 151:
                    $string = tr('redeem_not_found');
                    break;
                case 162:
                    $string = tr('failed_to_upload');
                    break;

                case 901:
                    $string = tr('default_card_not_available');
                    break;
                case 902:
                    $string = tr('something_went_wrong_error_payment');
                    break;
                case 903:
                    $string = tr('payment_not_completed_pay_again');
                    break;
                case 904:
                    $string = tr('flagged_video');
                    break;
                case 905:
                    $string = tr('user_login_decline');
                    break;

                case 3001:
                    $string = tr('verification_code_title');
                    break;
                
                default:
                    $string = tr('unknown_error_occured');
            }
            return $string;
        }

        public static function get_message($code)
        {
            switch($code) {
                case 101:
                    $string = tr('success');
                    break;
                case 102:
                    $string = tr('password_change_success');
                    break;
                case 103:
                    $string = tr('successfully_logged_in');
                    break;
                case 104:
                    $string = tr('successfully_logged_out');
                    break;
                case 105:
                    $string = tr('successfully_sign_up');
                    break;
                case 106:
                    $string = tr('mail_sent_successfully');
                    break;
                case 107:
                    $string = tr('payment_successful_done');
                    break;
                case 108:
                    $string = tr('favourite_provider_delete');
                    break;
                case 109:
                    $string = tr('payment_mode_changed');
                    break;
                case 110:
                    $string = tr('payment_mode_changed');
                    break;
                case 111:
                    $string = tr('service_accepted');
                    break;
                case 112:
                    $string = tr('provider_started');
                    break;
                case 113:
                    $string = tr('arrived_service_location');
                    break;
                case 114:
                    $string = tr('service_started');
                    break;
                case 115:
                    $string = tr('service_completed');
                    break;
                case 116:
                    $string = tr('user_rating_done');
                    break;
                case 117:
                    $string = tr('request_cancelled_successfully');
                    break;
                case 118:
                    $string = tr('wishlist_added');
                    break;
                case 119:
                    $string = tr('payment_confirmed_successfully');
                    break;
                case 120:
                    $string = tr('history_added');
                    break;
                case 121:
                    $string = tr('history_deleted_successfully');
                    break;
                default:
                    $string = "";
            
            }
            
            return $string;
        }

        public static function get_push_message($code) {

            switch ($code) {
                case 601:
                    $string = tr('no_provider_available');
                    break;
                case 602:
                    $string = tr('no_provider_available_take_service');
                    break;
                case 603:
                    $string = tr('request_complted_successfully');
                    break;
                case 604:
                    $string = tr('new_request');
                    break;
                default:
                    $string = "";
            }

            return $string;

        }

        public static function generate_password()
        {
            $new_password = time();
            $new_password .= rand();
            $new_password = sha1($new_password);
            $new_password = substr($new_password,0,8);
            return $new_password;
        }

        public static function upload_video_image($image,$video_id,$position) {

            $check_video_image = AdminVideoImage::where('admin_video_id' , $video_id)->where('position',$position)->first();

            if($check_video_image) {
                $video_image = $check_video_image;

                Helper::delete_picture($video_image->image, "/uploads/");

            } else {
                $video_image = new AdminVideoImage;
            }

            $video_image->admin_video_id = $video_id;
            $video_image->image = Helper::normal_upload_picture($image);

            if($position == 1) {
                $video_image->is_default = DEFAULT_TRUE;
            } else {
                $video_image->is_default = DEFAULT_FALSE;
            }

            $video_image->position = $position;

            $video_image->save();
            Log::info('VIDEO IMAGE SAVED : '.$video_image->id);

        }

        public static function upload_picture($picture)
        {
            Helper::delete_picture($picture, "/uploads/");

            $s3_url = "";

            $file_name = Helper::file_name();

            $ext = $picture->getClientOriginalExtension();
            $local_url = $file_name . "." . $ext;

            if(config('filesystems')['disks']['s3']['key'] && config('filesystems')['disks']['s3']['secret']) {

                Storage::disk('s3')->put($local_url, file_get_contents($picture) ,'public');

                $s3_url = Storage::url($local_url);
            } else {
                $ext = $picture->getClientOriginalExtension();
                $picture->move(public_path() . "/uploads", $file_name . "." . $ext);
                $local_url = $file_name . "." . $ext;

                $s3_url = Helper::web_url().'/uploads/'.$local_url;
            }

            return $s3_url;
        }

        public static function normal_upload_picture($picture)
        {
            $s3_url = "";

            $file_name = Helper::file_name();

            $ext = $picture->getClientOriginalExtension();

            $local_url = $file_name . "." . $ext;

            $path = '/uploads/images/';

            $inputFile = base_path('public'.$path.$local_url);

            // Convert bytes into MB
            $bytes = convertMegaBytes($picture->getClientSize());

            if ($bytes > Setting::get('image_compress_size')) {

                // Compress the video and save in original folder
                $FFmpeg = new \FFmpeg;

                $FFmpeg
                    ->input($picture->getPathname())
                    ->output($inputFile)
                    ->ready();
                // dd($FFmpeg->command);
            } else {

                $picture->move(public_path() . "/uploads/images/", $local_url);

            }

            $s3_url = Helper::web_url().'/uploads/images/'.$local_url;

            return $s3_url;
        }

        public static function subtitle_upload($subtitle)
        {
            $s3_url = "";

            $file_name = Helper::file_name();

            $ext = $subtitle->getClientOriginalExtension();

            $local_url = $file_name . "." . $ext;

            $path = '/uploads/subtitles/';

            $subtitle->move(public_path() . $path, $local_url);

            $s3_url = Helper::web_url().$path.$local_url;

            return $s3_url;
        }


        public static function video_upload($picture, $compress_type)
        {
            
            $s3_url = "";

            $file_name = Helper::file_name();

            $ext = $picture->getClientOriginalExtension();

            $local_url = $file_name . ".mp4" ;

            $path = '/uploads/videos/original/';

            // Convert bytes into MB
            $bytes = convertMegaBytes($picture->getClientSize());

            $inputFile = public_path().$path.$local_url;

            if ($bytes > Setting::get('video_compress_size') && $compress_type == DEFAULT_TRUE) {

                // dispatch(new OriginalVideoCompression($picture->getPathname(), $inputFile));

                Log::info("Compress Video : ".'Success');

                // Compress the video and save in original folder
                $FFmpeg = new \FFmpeg;

                $FFmpeg
                    ->input($picture->getPathname())
                    ->vcodec('h264')
                    ->constantRateFactor('28')
                    // ->forceFormat( 'mp4' )
                    ->output($inputFile)
                    ->ready();

            } else {
                Log::info("Original Video");

                 // Compress the video and save in original folder
               /* $FFmpeg = new \FFmpeg;

                $FFmpeg
                    ->input($picture->getPathname())
                    ->vcodec('h264')
                    ->forceFormat( 'mp4' )
                    ->output($inputFile)
                    ->ready();*/

                $picture->move(public_path() . $path, $local_url);
            }

            $s3_url = Helper::web_url().$path.$local_url;

            Log::info("Compress Video completed");

            return ['db_url'=>$s3_url, 'baseUrl'=> $inputFile, 'local_url'=>$local_url, 'file_name'=>$file_name];
        }

        public static function delete_picture($picture, $path) {

            if (file_exists(public_path() . $path . basename($picture))) {
            // "/uploads/"
                File::delete( public_path() . $path . basename($picture));

            }   
            return true;
        }

        public static function s3_delete_picture($picture) {
            Log::info($picture);

            Storage::Delete(basename($picture));
            return true;
        }

        public static function file_name() {

            $file_name = time();
            $file_name .= rand();
            $file_name = sha1($file_name);

            return $file_name;
        }

        public static function recently_added($web = NULL , $skip = 0, $take = 12, $id = null) {

            $videos_query = AdminVideo::where('admin_videos.is_approved' , 1)
                            ->leftJoin('categories' , 'admin_videos.category_id' , '=' , 'categories.id')
                            ->leftJoin('sub_categories' , 'admin_videos.sub_category_id' , '=' , 'sub_categories.id')
                            ->leftJoin('genres' , 'admin_videos.genre_id' , '=' , 'genres.id')
                            ->where('admin_videos.status' , 1)
                            ->whereNotIn('admin_videos.is_banner',[1])
                            ->videoResponse()
                            ->orderby('admin_videos.created_at' , 'desc');
            if ($id) {
                // Check any flagged videos are present
                $flagVideos = getFlagVideos($id);

                if($flagVideos) {
                    $videos_query->whereNotIn('admin_videos.id',$flagVideos);
                }
            }

            if($web) {

                // Check any flagged videos are present
                $continue_watching_videos = continueWatchingVideos($id);
                
                if($continue_watching_videos) {

                    $videos_query->whereNotIn('admin_videos.id', $continue_watching_videos);

                }

                $videos = $videos_query->paginate(12);

            } else {

                $videos = $videos_query->skip($skip)->take($take)
                            ->get();
            }

            return $videos;
        }

        public static function recently_video($count, $user_id = null) {

            $videos_query = AdminVideo::where('admin_videos.is_approved' , 1)
                            ->leftJoin('categories' , 'admin_videos.category_id' , '=' , 'categories.id')
                            ->leftJoin('sub_categories' , 'admin_videos.sub_category_id' , '=' , 'sub_categories.id')
                            ->leftJoin('genres' , 'admin_videos.genre_id' , '=' , 'genres.id')
                            ->where('admin_videos.status' , 1)
                            ->videoResponse()
                            ->whereNotIn('admin_videos.is_banner',[1])
                            ->orderby('admin_videos.created_at' , 'desc');
            if ($user_id) {
                // Check any flagged videos are present
                $flagVideos = getFlagVideos($user_id);

                if($flagVideos) {
                    $videos_query->whereNotIn('admin_videos.id',$flagVideos);
                }

                // Check any flagged videos are present
                $continue_watching_videos = continueWatchingVideos($user_id);
                
                if($continue_watching_videos) {

                    $videos_query->whereNotIn('admin_videos.id', $continue_watching_videos);

                }
            }

            if ($count > 0) {

                $video = $videos_query->skip(0)->take($count)->get();

            } else {

                $video = $videos_query->first();

            }
            return $video;
        }

        public static function wishlist($user_id, $web = NULL , $skip = 0, $take = 12) {

            $videos_query = Wishlist::where('user_id' , $user_id)
                            ->leftJoin('admin_videos' ,'wishlists.admin_video_id' , '=' , 'admin_videos.id')
                            ->leftJoin('categories' ,'admin_videos.category_id' , '=' , 'categories.id')
                            ->where('admin_videos.is_approved' , 1)
                            ->where('admin_videos.status' , 1)
                            ->where('wishlists.status' , 1)
                            ->select(
                                    'wishlists.id as wishlist_id','admin_videos.id as admin_video_id' ,
                                    'admin_videos.title','admin_videos.description' ,
                                    'default_image','admin_videos.watch_count','admin_videos.ratings',
                                    'admin_videos.duration','admin_videos.category_id',
                                    DB::raw('DATE_FORMAT(admin_videos.publish_time , "%e %b %y") as publish_time') , 'categories.name as category_name')
                            ->orderby('wishlists.created_at' , 'desc');

            // Check any flagged videos are present
            $flagVideos = getFlagVideos($user_id);
            
            if($flagVideos) {
                $videos_query->whereNotIn('admin_video_id', $flagVideos);
            }

            if($web) {

                 // Check any flagged videos are present
                $continue_watching_videos = continueWatchingVideos($user_id);
                
                if($continue_watching_videos) {

                    $videos_query->whereNotIn('admin_videos.id', $continue_watching_videos);

                }

                $videos = $videos_query->paginate(12);

            } else {
                $videos = $videos_query->skip($skip)->take($take)
                            ->get();
            }

            return $videos;

        }

        public static function watch_list($user_id, $web = NULL , $skip = 0, $take = 12) {

            $videos_query = UserHistory::where('user_id' , $user_id)
                            ->leftJoin('admin_videos' ,'user_histories.admin_video_id' , '=' , 'admin_videos.id')
                            ->leftJoin('categories' ,'admin_videos.category_id' , '=' , 'categories.id')
                            ->where('admin_videos.is_approved' , 1)
                            ->where('admin_videos.status' , 1)
                            ->select('user_histories.id as history_id','admin_videos.id as admin_video_id' ,
                                'admin_videos.title','admin_videos.description' , 'admin_videos.duration',
                                'default_image','admin_videos.watch_count','admin_videos.ratings',
                                DB::raw('DATE_FORMAT(admin_videos.publish_time , "%e %b %y") as publish_time'), 'admin_videos.category_id','categories.name as category_name')
                            ->orderby('user_histories.created_at' , 'desc');
                            
            // Check any flagged videos are present
            $flagVideos = getFlagVideos($user_id);

            if($flagVideos) {
                $videos_query->whereNotIn('admin_videos.id', $flagVideos);
            }


            if($web) {

                 // Check any flagged videos are present
                $continue_watching_videos = continueWatchingVideos($user_id);
                
                if($continue_watching_videos) {

                    $videos_query->whereNotIn('admin_videos.id', $continue_watching_videos);

                }

                $videos = $videos_query->paginate(12);

            } else {

                $videos = $videos_query->skip($skip)->take($take)->get();
            }

            return $videos;

        }

        public static function banner_videos($user_id) {

            $videos_query = AdminVideo::where('admin_videos.is_approved' , 1)
                            ->where('admin_videos.status' , 1)
                            ->where('admin_videos.is_banner' , 1)
                            ->select(
                                'admin_videos.id as admin_video_id' ,
                                'admin_videos.title','admin_videos.ratings',
                                'admin_videos.banner_image as default_image'
                                )
                            ->orderBy('created_at' , 'desc');

             // Check any flagged videos are present
            $flagVideos = getFlagVideos($user_id);

            if($flagVideos) {

                $videos_query->whereNotIn('admin_videos.id', $flagVideos);
                
            }


            $videos = $videos_query->get();
         

            return $videos;
        }

        public static function suggestion_videos($web = NULL , $skip = 0, $id = null,$user_id = null) {

            $history = UserHistory::where('user_id' , $user_id)
                            ->leftJoin('admin_videos' ,'user_histories.admin_video_id' , '=' , 'admin_videos.id')
                            ->leftJoin('categories' ,'admin_videos.category_id' , '=' , 'categories.id')
                            ->leftJoin('sub_categories' ,'sub_categories.category_id' , '=' , 'categories.id')
                            ->where('admin_videos.is_approved' , 1)
                            ->where('admin_videos.status' , 1)->pluck('sub_categories.id')->toArray();

            $videos_query = AdminVideo::where('admin_videos.is_approved' , 1)
                            ->where('admin_videos.status' , 1)
                            ->leftJoin('categories' , 'admin_videos.category_id' , '=' , 'categories.id')
                            ->leftJoin('sub_categories' , 'admin_videos.sub_category_id' , '=' , 'sub_categories.id')
                            ->leftJoin('genres' , 'admin_videos.genre_id' , '=' , 'genres.id')
                            ->whereIn('sub_categories.id', $history)
                            ->videoResponse()
                            ->orderByRaw('RAND()');
            if ($user_id) {
                // Check any flagged videos are present
                $flagVideos = getFlagVideos($user_id);

                if($flagVideos) {
                    $videos_query->whereNotIn('admin_videos.id',$flagVideos);
                }

            }

            if ($id) {

                $videos_query->where('admin_videos.id', '!=', $id);

            }
            
            if($web) {

                // Check any flagged videos are present
                $continue_watching_videos = continueWatchingVideos($user_id);
                
                if($continue_watching_videos) {

                    $videos_query->whereNotIn('admin_videos.id', $continue_watching_videos);

                }

                $videos = $videos_query->paginate(16);

            } else {

                $videos = $videos_query->skip($skip)->take(Setting::get('admin_take_count' ,12))
                            ->get();
            }

            return $videos;

        }

        public static function trending($web = NULL , $skip = 0, $take = 12, $id = null) {

            $videos_query = AdminVideo::where('watch_count' , '>' , 0)
                            ->where('admin_videos.is_approved' , 1)
                            ->where('admin_videos.status' , 1)
                            ->leftJoin('categories' , 'admin_videos.category_id' , '=' , 'categories.id')
                            ->leftJoin('sub_categories' , 'admin_videos.sub_category_id' , '=' , 'sub_categories.id')
                            ->leftJoin('genres' , 'admin_videos.genre_id' , '=' , 'genres.id')
                            ->videoResponse()
                            ->orderby('watch_count' , 'desc');
            if ($id) {
                // Check any flagged videos are present
                $flagVideos = getFlagVideos($id);

                if($flagVideos) {
                    $videos_query->whereNotIn('admin_videos.id', $flagVideos);
                }

            }

            if($web) {

                 // Check any flagged videos are present
                $continue_watching_videos = continueWatchingVideos($id);
                
                if($continue_watching_videos) {

                    $videos_query->whereNotIn('admin_videos.id', $continue_watching_videos);

                }

                $videos = $videos_query->paginate(12);
            } else {
                $videos = $videos_query->skip($skip)->take($take)->get();
            }

            return $videos;

        }

        public static function category_videos($category_id, $web = NULL , $skip = 0) {

            $videos_query = AdminVideo::where('admin_videos.is_approved' , 1)
                        ->where('admin_videos.status' , 1)
                        ->leftJoin('categories' , 'admin_videos.category_id' , '=' , 'categories.id')
                        ->leftJoin('sub_categories' , 'admin_videos.sub_category_id' , '=' , 'sub_categories.id')
                        ->leftJoin('genres' , 'admin_videos.genre_id' , '=' , 'genres.id')
                        ->where('admin_videos.category_id' , $category_id)
                        ->videoResponse()
                        ->orderby('admin_videos.sub_category_id' , 'asc');
            if (Auth::check()) {
                // Check any flagged videos are present
                $flagVideos = getFlagVideos(Auth::user()->id);

                if($flagVideos) {
                    $videos_query->whereNotIn('admin_videos.id', $flagVideos);
                }
            }

            if($web) {
                $videos = $videos_query->paginate(16);
            } else {
                $videos = $videos_query->skip($skip)->take(Setting::get('admin_take_count' ,12))->get();
            }

            return $videos;
        }

        public static function sub_category_videos($sub_category_id , $web = NULL , $skip = 0,$user_id = null) {

            $videos_query = AdminVideo::where('admin_videos.is_approved' , 1)
                        ->where('admin_videos.status' , 1)
                        ->leftJoin('categories' , 'admin_videos.category_id' , '=' , 'categories.id')
                        ->leftJoin('sub_categories' , 'admin_videos.sub_category_id' , '=' , 'sub_categories.id')
                        ->leftJoin('genres' , 'admin_videos.genre_id' , '=' , 'genres.id')
                        ->where('admin_videos.sub_category_id' , $sub_category_id)
                        ->videoResponse()
                        ->orderby('admin_videos.sub_category_id' , 'asc');
            if ($user_id) {
                // Check any flagged videos are present
                $flagVideos = getFlagVideos($user_id);

                if($flagVideos) {
                    $videos_query->whereNotIn('admin_videos.id', $flagVideos);
                }
            }
            
            if($web) {
                $videos = $videos_query->paginate(16);
            } else {
                $videos = $videos_query->skip($skip)->take(Setting::get('admin_take_count' ,12))->get();
            }

            return $videos;
        }

        public static function genre_videos($id , $web = NULL , $skip = 0) {

            $videos_query = AdminVideo::where('admin_videos.is_approved' , 1)
                        ->where('admin_videos.status' , 1)
                        ->leftJoin('categories' , 'admin_videos.category_id' , '=' , 'categories.id')
                        ->leftJoin('sub_categories' , 'admin_videos.sub_category_id' , '=' , 'sub_categories.id')
                        ->leftJoin('genres' , 'admin_videos.genre_id' , '=' , 'genres.id')
                        ->where('admin_videos.genre_id' , $id)
                        ->videoResponse()
                        ->orderby('admin_videos.sub_category_id' , 'asc');

            if($web) {
                $videos = $videos_query->paginate(16);
            } else {
                $videos = $videos_query->skip($skip)->take(Setting::get('admin_take_count' ,12))->get();
            }

            return $videos;
        }

        public static function get_video_details($video_id) {

            $videos = AdminVideo::where('admin_videos.id' , $video_id)
                    ->leftJoin('categories' , 'admin_videos.category_id' , '=' , 'categories.id')
                    ->leftJoin('sub_categories' , 'admin_videos.sub_category_id' , '=' , 'sub_categories.id')
                    ->leftJoin('genres' , 'admin_videos.genre_id' , '=' , 'genres.id')
                    ->videoResponse()
                    ->orderBy('admin_videos.created_at' , 'desc')
                    ->first();

            if(!$videos) {
                $videos = array();
            }

            return $videos;
        }

        public static function video_ratings($video_id) {

            $ratings = UserRating::where('admin_video_id' , $video_id)
                            ->leftJoin('users' , 'user_ratings.user_id' , '=' , 'users.id')
                            ->select('users.id as user_id' , 'users.name as username',
                                    'users.picture as user_picture' ,

                                    'user_ratings.rating' , 'user_ratings.comment',
                                    'user_ratings.created_at')
                            ->get();
            if(!$ratings) {
                $ratings = array();
            }

            return $ratings;
        }

        public static function wishlist_status($video_id,$user_id) {
            if($wishlist = Wishlist::where('admin_video_id' , $video_id)->where('user_id' , $user_id)->first()) {
                if($wishlist->status)
                    return $wishlist->id;
                else
                    return 0 ;
            } else {
                return 0;
            }
        }

        public static function history_status($user_id,$video_id) {
            if(UserHistory::where('admin_video_id' , $video_id)->where('user_id' , $user_id)->count()) {
                return 1;
            } else {
                return 0;
            }
        }

        public static function like_status($user_id,$video_id) {

            $model = LikeDislikeVideo::where('admin_video_id' , $video_id)  
                    
                    -> where('user_id' , $user_id)->first();

            if ($model) {

                if($model->like_status == DEFAULT_TRUE) {

                    return 1;

                } else if($model->dislike_status == DEFAULT_TRUE){

                    return -1;

                } else {

                    return 0;

                }

            } else {

                return 0;
            }
        }

        public static function likes_count($video_id) {

            $model = LikeDislikeVideo::where('admin_video_id' , $video_id)->where('like_status' , 1)->count();

            return $model ? $model : 0;

        }

        public static function search_video($key,$web = NULL,$skip = 0,$id = null) {

            $videos_query = AdminVideo::where('admin_videos.is_approved' ,'=', 1)
                        ->leftJoin('categories' , 'admin_videos.category_id' , '=' , 'categories.id')
                        ->leftJoin('sub_categories' , 'admin_videos.sub_category_id' , '=' , 'sub_categories.id')
                        ->leftJoin('genres' , 'admin_videos.genre_id' , '=' , 'genres.id')
                        ->where('title','like', '%'.$key.'%')
                        ->where('admin_videos.status' , 1)
                        ->whereNotIn('admin_videos.is_banner',[1])
                        ->videoResponse()
                        ->orderBy('admin_videos.created_at' , 'desc');
            if ($id) {
                // Check any flagged videos are present
                $flagVideos = getFlagVideos($id);

                if($flagVideos) {
                    $videos_query->whereNotIn('admin_videos.id',$flagVideos);
                }
            }

            if($web) {
                $videos = $videos_query->paginate(16);
            } else {
                $videos = $videos_query->skip($skip)->take(Setting::get('admin_take_count' ,12))->get();
            }

            return $videos;
        }

        public static function get_user_comments($user_id,$web = NULL) {

            $videos_query = UserRating::where('user_id' , $user_id)
                            ->leftJoin('admin_videos' ,'user_ratings.admin_video_id' , '=' , 'admin_videos.id')
                            ->where('admin_videos.is_approved' , 1)
                            ->where('admin_videos.status' , 1)
                            ->select('admin_videos.id as admin_video_id' ,
                                'admin_videos.title','admin_videos.description' ,
                                'default_image','admin_videos.watch_count',
                                'admin_videos.duration',
                                DB::raw('DATE_FORMAT(admin_videos.publish_time , "%e %b %y") as publish_time'))
                            ->orderby('user_ratings.created_at' , 'desc')
                            ->groupBy('admin_videos.id');

            if($web) {
                $videos = $videos_query->paginate(16);
            } else {
                $videos = $videos_query->skip($skip)->take(Setting::get('admin_take_count' ,12))->get();
            }

            return $videos;

        }

        public static function get_video_comments($video_id,$skip = 0 ,$web = NULL) {

            $videos_query = UserRating::where('admin_video_id' , $video_id)
                            ->leftJoin('admin_videos' ,'user_ratings.admin_video_id' , '=' , 'admin_videos.id')
                            ->leftJoin('users' ,'user_ratings.user_id' , '=' , 'users.id')
                            ->where('admin_videos.is_approved' , 1)
                            ->where('admin_videos.status' , 1)
                            ->select('admin_videos.id as admin_video_id' ,
                                'user_ratings.user_id as rating_user_id' ,
                                'user_ratings.rating as rating',
                                'user_ratings.comment', 'user_ratings.created_at',
                                'users.name as username' , 'users.picture')
                            ->orderby('user_ratings.created_at' , 'desc');
            if($web) {
                $videos = $videos_query->get();
            } else {
                $videos = $videos_query->skip($skip)->take(Setting::get('admin_take_count' , 12));
            }

            return $videos;

        }

        public static function check_wishlist_status($user_id,$video_id) {

            $status = Wishlist::where('user_id' , $user_id)
                                        ->where('admin_video_id' , $video_id)
                                        ->where('status' , 1)
                                        ->first();
            return $status;
        }

        public static function send_notification($id,$title,$message) {

            Log::info("Send Push Started");

            // Check the user type whether "USER" or "PROVIDER"
            if($id == "all") {
                $users = User::where('push_status' , 1)->get();
            } else {
                $users = User::find($id);
            }

            $push_data = array();

            $push_message = array('success' => true,'message' => $message,'data' => array());

            $push_notification = 1; // Check the push notifictaion is enabled

            if ($push_notification == 1) {

                Log::info('Admin enabled the push ');

                if($users){

                    Log::info('Check users variable');

                    foreach ($users as $key => $user) {

                        Log::info('Individual User');

                        if ($user->device_type == 'ios') {

                            Log::info("iOS push Started");

                            require_once app_path().'/ios/apns.php';

                            $msg = array("alert" => $message,
                                "status" => "success",
                                "title" => $title,
                                "message" => $push_message,
                                "badge" => 1,
                                "sound" => "default",
                                "status" => "",
                                "rid" => "",
                                );

                            if (!isset($user->device_token) || empty($user->device_token)) {
                                $deviceTokens = array();
                            } else {
                                $deviceTokens = $user->device_token;
                            }

                            $apns = new \Apns();
                            $apns->send_notification($deviceTokens, $msg);

                            Log::info("iOS push end");

                        } else {

                            Log::info("Andriod push Started");

                            require_once app_path().'/gcm/GCM_1.php';
                            require_once app_path().'/gcm/const.php';

                            if (!isset($user->device_token) || empty($user->device_token)) {
                                $registatoin_ids = "0";
                            } else {
                                $registatoin_ids = trim($user->device_token);
                            }
                            if (!isset($push_message) || empty($push_message)) {
                                $msg = "Message not set";
                            } else {
                                $msg = $push_message;
                            }
                            if (!isset($title) || empty($title)) {
                                $title1 = "Message not set";
                            } else {
                                $title1 = trim($title);
                            }

                            $message = array(TEAM => $title1, MESSAGE => $msg);

                            $gcm = new \GCM();
                            $registatoin_ids = array($registatoin_ids);
                            $gcm->send_notification($registatoin_ids, $message);

                            Log::info("Andriod push end");

                        }

                    }

                }

            } else {
                Log::info('Push notifictaion is not enabled. Please contact admin');
            }

            Log::info("*************************************");

        }

        public static function upload_language_file($folder,$picture) {

            $ext = $picture->getClientOriginalExtension();

            $local_url = "messages" . "." . $ext;
            
            $picture->move(base_path() . "/resources/lang/".$folder ."/", $local_url);

        }

        public static function delete_language_files($folder, $boolean) {
            if ($boolean) {
                $path = base_path() . "/resources/lang/" .$folder;
                \File::cleanDirectory($path);
                \Storage::deleteDirectory( $path );
                rmdir( $path );
            } else {
                \File::delete( base_path() . "/resources/lang/" . $folder ."/messages.php");
            }
            return true;
        }

        /**
         * Used to generate index.php
         *
         * 
         */

        public static function generate_index_file($folder) {

            $filename = public_path()."/".$folder."/index.php"; 

            if(!file_exists($filename)) {

                $index_file = fopen($filename,'w');

                $sitename = Setting::get("site_name");

                fwrite($index_file, '<?php echo "You Are trying to access wrong path!!!!--|E"; ?>');       

                fclose($index_file);
            }
        
        }

        /**
         * Function name: RTMP Secure video url 
         *
         * @description: used to convert the video to rtmp secure link
         *
         * @created: vidhya R
         * 
         * @edited: 
         *
         * @param string $video_name
         *
         * @param string $video_link
         *
         * @return RTMP SECURE LINK or Normal video link
         */

        public static function convert_rtmp_to_secure($video_name  = "", $video_link = "") {

            if(Setting::get('RTMP_SECURE_VIDEO_URL') != "") {

                // HLS_STREAMING_URL
            
                // validity of the link in seconds (if rtmp and www are on two different machines, it is better to give a higher value, because there may be a time difference.

                $e = date('U')+20; 

                $secret_word = "cgshlockkey"; 

                $user_remote_address = $_SERVER['REMOTE_ADDR']; 

                $md5 = base64_encode(md5($secret_word . $user_remote_address . $e , true)); 

                $md5 = strtr($md5, '+/', '-_'); 

                $md5 = str_replace('=', '', $md5); 

                $rtmp = $video_name."?token=".$md5."&e=".$e; 
                
                $secure_url = Setting::get('RTMP_SECURE_VIDEO_URL').$rtmp;

                return $secure_url; 
            
            } elseif (Setting::get('streaming_url')) {

                $rtmp_video_url = Setting::get('streaming_url').$video_name;

                return $rtmp_video_url;

            } else {

                return $video_link;

            }
            
        }

        /**
         * Function name: RTMP Secure video url 
         *
         * @description: used to convert the video to rtmp secure link
         *
         * @created: vidhya R
         * 
         * @edited: 
         *
         * @param string $video_name
         *
         * @param string $video_link
         *
         * @return RTMP SECURE LINK or Normal video link
         */

        public static function convert_hls_to_secure($video_name  = "", $video_link = "") {

            if(Setting::get('HLS_SECURE_VIDEO_URL') != "") {


                // HLS_STREAMING_URL
            
                // validity of the link in seconds (if rtmp and www are on two different machines, it is better to give a higher value, because there may be a time difference.

                $expires = date('U')+20;

                // secure_link_md5 "$secure_link_expires$uri$remote_addr cgshlockkey";

                $secret_word = "cgshlockkey"; 
 
                $user_remote_address = $_SERVER['REMOTE_ADDR']; 

                Log::info("user_remote_address".$user_remote_address);

                $md5 = md5("$expires/$video_name$user_remote_address $secret_word", true);

                $md5 = base64_encode($md5); 

                $md5 = strtr($md5, '+/', '-_'); 

                $md5 = str_replace('=', '', $md5); 

                $hls = $video_name."?md5=".$md5."&expires=".$expires; 
                
                $secure_url = Setting::get('HLS_SECURE_VIDEO_URL').$hls;

                return $secure_url; 
            
            } elseif (Setting::get('HLS_STREAMING_URL')) {

                $hls_video_url = Setting::get('HLS_STREAMING_URL').$video_name;

                return $hls_video_url;

            } else {

                return $video_link;

            }
            
        }

        /**
         * Function Name : continue_watching_videos
         * 
         * Brief : Displayed partially seen videos by the users based on their profile
         *
         * @param object $request - USer id, token & sub profile id
         *
         * @return response of array 
         */

        public static function continue_watching_videos($sub_profile_id, $device_type, $skip) {

            $query = ContinueWatchingVideo::where('continue_watching_videos.sub_profile_id', $sub_profile_id)
                        ->leftJoin('admin_videos', 'admin_videos.id','=', 'continue_watching_videos.admin_video_id')
                        ->where('admin_videos.status', DEFAULT_TRUE)
                        ->where('admin_videos.is_approved', DEFAULT_TRUE)
                        ->orderby('continue_watching_videos.updated_at', 'desc');
                       
            // Check any flagged videos are present

            $flagVideos = getFlagVideos($sub_profile_id);
            
            if($flagVideos) {

                $query->whereNotIn('admin_video_id', $flagVideos);

            }

            if($device_type == DEVICE_WEB) {

                $model = $query->paginate(16);

            } else {

                $model = $query->skip($skip)->take(Setting::get('admin_take_count', 12))->get();

            }

            return $model;

            /*$data = [];

            foreach ($model as $key => $value) {

               $data[] = $value;

            }

            return $data;*/

        }


        /* Function name: RTMP Secure video url 
         *
         * @description: used to convert the video to rtmp secure link
         *
         * @created: vidhya R
         * 
         * @edited: 
         *
         * @param string $video_name
         *
         * @param string $video_link
         *
         * @return RTMP SECURE LINK or Normal video link
         */

        public static function convert_smil_to_secure($smil_file  = "", $smil_link = "") {

            if(Setting::get('VIDEO_SMIL_URL') != "") {
            
                // validity of the link in seconds (if rtmp and www are on two different machines, it is better to give a higher value, because there may be a time difference.

                $expires = date('U')+20;

                // secure_link_md5 "$secure_link_expires$uri$remote_addr cgshlockkey";

                $secret_word = "cgshlockkey"; 
 
                $user_remote_address = $_SERVER['REMOTE_ADDR']; 

                Log::info("user_remote_address".$user_remote_address);

                $md5 = md5("$expires/$smil_file$user_remote_address $secret_word", true);

                $md5 = base64_encode($md5); 

                $md5 = strtr($md5, '+/', '-_'); 

                $md5 = str_replace('=', '', $md5); 

                $smil = $smil_file."?md5=".$md5."&expires=".$expires; 
                
                $secure_url = Setting::get('VIDEO_SMIL_URL').'/'.$smil;

                return $secure_url; 
            
            } else {

                return $smil_link;

            }
            
        }
    }



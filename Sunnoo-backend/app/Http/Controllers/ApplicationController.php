<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Requests;

use App\Helpers\Helper;

use App\SubCategory;

use App\Genre;

use App\AdminVideo;

use App\User;

use App\Settings;

use Log;

use DB;

use Validator;

use App\Page;

use App\Admin;

use Setting;

use Auth;

use App\UserLoggedDevice;

class ApplicationController extends Controller {

    public $expiry_date = "";

    public function test(Request $request) {

        $subject = tr('user_welcome_title');
        $page = "emails.moderator_welcome";
        $email = "test@gmail.com";
        $email_data['email'] = "vidhya";
        $email_data['name'] = "vidhya";
        $email_data['password'] = "ABCD-!@HDM";

        return view($page)->with('email_data' , $email_data);

        // $user = user_verify_generate(642);

    }

    /**
     * Function Name : payment_failture()
     * 
     * Created By: vidhya R
     * 
     * Usage : used to show thw view page, whenever the payment failed.
     *
     */

    public function payment_failure($error = "") {

        $paypal_error = \Session::get("paypal_error") ? \Session::get('paypal_error') : "";

        \Session::forget("paypal_error");

        // Redirect to angular payment failture page

        // @TODO Shobana please change this page to angular payment failure page 

        return redirect()->away(Setting::get('ANGULAR_SITE_URL'));

    }

    /**
     * Used to generate index.php file to avoid uploads folder access
     *
     */

    public function generate_index(Request $request) {

        if($request->has('folder')) {

            Helper::generate_index_file($request->folder);

        }

        return response()->json(['success' => true , "message" => 'successfully']);

    }

    public function select_genre(Request $request) {
        
        $id = $request->option;

        $genres = Genre::where('sub_category_id', '=', $id)
                        ->where('is_approved' , 1)
                          ->orderBy('name', 'asc')
                          ->get();

        return response()->json($genres);
    
    }

    public function select_sub_category(Request $request) {
        
        $id = $request->option;

        $subcategories = SubCategory::where('category_id', '=', $id)
                            ->leftJoin('sub_category_images' , 'sub_categories.id' , '=' , 'sub_category_images.sub_category_id')
                            ->select('sub_category_images.picture' , 'sub_categories.*')
                            ->where('sub_category_images.position' , 1)
                            ->where('is_approved' , 1)
                            ->orderBy('name', 'asc')
                            ->get();

        return response()->json($subcategories);
    
    }

    public function about(Request $request) {

        $about = Page::where('type', 'about')->first();

        return view('static.about-us')->with('about' , $about)
                        ->with('page' , 'about')
                        ->with('subPage' , '');

    }

    public function privacy(Request $request) {

        $page = Page::where('type', 'privacy')->first();;

        // dd($page);
        return view('static.privacy')->with('data' , $page)
                        ->with('page' , 'conact_page')
                        ->with('subPage' , '');

    }

    public function terms(Request $request) {

        $page = Page::where('type', 'terms')->first();;

        // dd($page);
        return view('static.terms')->with('data' , $page)
                        ->with('page' , 'terms_and_condition')
                        ->with('subPage' , '');

    }


    public function cron_publish_video(Request $request) {
        
        Log::info('cron_publish_video');

        $admin = Admin::first();
        
        $timezone = 'Asia/Kolkata';

        if($admin) {

            if ($admin->timezone) {

                $timezone = $admin->timezone;

            } 

        }

        $date = convertTimeToUSERzone(date('Y-m-d H:i:s'), $timezone);

        $videos = AdminVideo::where('publish_time' ,'<=' ,$date)
                        ->where('status' , 0)->get();
        foreach ($videos as $key => $video) {
            Log::info('Change the status');
            $video->status = 1;
            $video->save();
        }
    
    }



    

    public function send_notification_user_payment(Request $request) {

        Log::info("Notification to User for Payment");

        $time = date("Y-m-d");
        // Get provious provider availability data

        $current_date = date('Y-m-d H:i:s');

        $compare_date = date('Y-m-d', strtotime('-1 day', strtotime($current_date)));

        $payments = UserPayment::where('expiry_date' , $compare_date)->where('status')->get();

        // $query = "SELECT *, TIMESTAMPDIFF(SECOND, '$time',expiry_date) AS date_difference FROM user_payments";

        // $payments = DB::select(DB::raw($query));

        // Log::info(print_r($payments,true));

        if($payments) {
            
            foreach($payments as $payment) {

                // if($payment->date_difference <= 864000) {
                   
                    // Delete provider availablity
                   
                    Log::info('Send mail to user');

                    if($user_details = User::where('id' , $payment->user_id)->where('user_type' , 1)->first()) {

                        if($user_details->is_activated == 1 && $user_details->is_verified == 1) {

                            Log::info($user->email);

                            $email_data = array();

                            // Send welcome email to the new user:

                            $subject = tr('payment_notification');

                            $email_data['id'] = $user->id;

                            $email_data['name'] = $user->name;

                            $email_data['expiry_date'] = $payment->expiry_date;

                            $email_data['status'] = 0;

                            $page = "emails.payment-notification-expiry";

                            $email = $user->email;

                            $result = Helper::send_email($page,$subject,$email,$email_data);

                            \Log::info("Email".$result);

                        }
                    
                    }

                // }
            }

            Log::info("Notification to the User successfully....:-)");
        
        } else {
            Log::info(" records not found ....:-(");
        }
    
    }

    public function user_payment_expiry(Request $request) {

        Log::info("user_payment_expiry");

        $time = date("Y-m-d");

        // $current_date = date('Y-m-d H:i:s');

        // $compare_date = date('Y-m-d', strtotime('-1 day', strtotime($current_date)));

        // $payments = UserPayment::where('expiry_date' , $compare_date)->where('status')->get();

        $query = "SELECT *, TIMESTAMPDIFF(SECOND, '$time',expiry_date) AS date_difference
                  FROM user_payments where status=1";

        $payments = DB::select(DB::raw($query));

        if($payments) {

            foreach($payments as $payment) {

                if($payment->date_difference < 0) {

                    // Delete User 

                    Log::info('Send mail to user');

                    $email_data = array();
                    
                    if($user_details = User::where('id' ,$payment->user_id)->where('user_type' , 1)->first()) {

                        $user_details->user_type = 0;
                        
                        $user_details->save();

                        if($user_details->is_activated == 1 && $user_details->is_verified == 1) {


                            // Send welcome email to the new user:

                            $subject = tr('payment_notification');

                            $email_data['id'] = $user_details->id;

                            $email_data['username'] = $user_details->name;

                            $email_data['expiry_date'] = $payment->expiry_date;

                            $email_data['status'] = 1;

                            $page = "emails.payment-expiry";

                            $email = $user_details->email;

                            $result = Helper::send_email($page,$subject,$email,$email_data);

                            \Log::info("Email".print_r($result , true));
                        }
                    }
                
                }
            }

            Log::info("Notification to the User successfully....:-)");

        } else {
            Log::info(" records not found ....:-(");
        }
    
    }

    public function search_video(Request $request) {

        $validator = Validator::make(
            $request->all(),
            array(
                'term' => 'required',
            ),
            array(
                'exists' => 'The :attribute doesn\'t exists',
            )
        );
    
        if ($validator->fails()) {

            $error_messages = implode(',', $validator->messages()->all());
            $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages'=>$error_messages);

            return false;
        
        } else {

            $q = $request->term;

            \Session::set('user_search_key' , $q);

            $items = array();
            
            $results = Helper::search_video($q);

            if($results) {

                foreach ($results as $i => $key) {

                    $check = $i+1;

                    if($check <=10) {
     
                        array_push($items,$key->title);

                    } if($check == 10 ) {
                        array_push($items,"View All" );
                    }
                
                }

            }

            return response()->json($items);
        }     
    
    }

    public function search_all(Request $request) {

        $validator = Validator::make(
            $request->all(),
            array(
                'key' => '',
            ),
            array(
                'exists' => 'The :attribute doesn\'t exists',
            )
        );
    
        if ($validator->fails()) {

            $error_messages = implode(',', $validator->messages()->all());
            $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages'=>$error_messages);
        
        } else {

            if($request->has('key')) {
                $q = $request->key;    
            } else {
                $q = \Session::get('user_search_key');
            }

            if($q == "all") {
                $q = \Session::get('user_search_key');
            }

            $videos = Helper::search_video($q,1);

            return view('user.search-result')->with('key' , $q)->with('videos' , $videos)->with('page' , "")->with('subPage' , "");
        }     
    
    }

    /**
     * To verify the email from user
     *
     */

    public function email_verify(Request $request) {

        \Log::info('User Detals'.print_r($request->all(), true));

        // Check the request have user ID

        if($request->id) {

            \Log::info('id');

            // Check the user record exists

            if($user = User::find($request->id)) {


                \Log::info('user');

                // Check the user already verified

                if(!$user->is_verified) {


                    \Log::info('is_verified');

                    // Check the verification code and expiry of the code

                    $response = Helper::check_email_verification($request->verification_code , $user->id, $error);

                    if($response) {

                        $user->is_verified = 1;

                        \Log::info('User verified');

                        \Log::info('Before User Id verified'.$user->is_verified);
                        
                        $user->save();

                        \Log::info('After User Id verified'.$user->is_verified);

                        // \Auth::loginUsingId($request->id);

                        // return redirect()->away(Setting::get('ANGULAR_SITE_URL')."signin");

                    } else {

                        // return redirect(route('user.login.form'))->with('flash_error' , $error);
                    }

                } else {

                    \Log::info('User Already verified');

                    // \Auth::loginUsingId($request->id);

                    //return redirect(route('user.dashboard'));
                }

            } else {
                // return redirect(route('user.login.form'))->with('flash_error' , "User Record Not Found");
            }

        } else {

            // return redirect(route('user.login.form'))->with('flash_error' , "Something Missing From Email verification");
        }

        return redirect()->away(Setting::get('ANGULAR_SITE_URL')."signin");
    
    }

    public function admin_control() {

        if (Auth::guard('admin')->check()) {

            return view('admin.settings.control')->with('page', tr('admin_control'));

        } else {

            return back();

        }
        
    }

    public function save_admin_control(Request $request) {

        $model = Settings::get();

        foreach ($model as $key => $value) {

            if ($value->key == 'admin_delete_control') {
                $value->value = $request->admin_delete_control;
            } else if ($value->key == 'is_spam') {
                $value->value = $request->is_spam;
            } else if ($value->key == 'is_subscription') {
                $value->value = $request->is_subscription;
            } else if ($value->key == 'is_payper_view') {
                $value->value = $request->is_payper_view;
            } else if ($value->key == 'email_verify_control') {
                $value->value = $request->email_verify_control;
            }
            
            $value->save();
        }
        return back()->with('flash_success' , tr('settings_success'));
    }

    public function set_session_language($lang) {

        $locale = \Session::put('locale', $lang);

        return back()->with('flash_success' , tr('session_success'));
    }


    public function check_token_expiry() {

        $model = UserLoggedDevice::get();

        foreach ($model as $key => $value) {

            $user = User::find($value->user_id);

            if ($user) {
           
                if(!Helper::is_token_valid('USER', $user->id, $user->token, $error)) {

                    // $response = response()->json($error, 200);
                    
                    if ($value->delete()) {

                        $user->logged_in_account -= 1;

                        $user->save();

                    }

                }

            }

        }

    }

}
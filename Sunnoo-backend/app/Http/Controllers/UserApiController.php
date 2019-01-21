<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Helpers\Helper;

use App\Jobs\NormalPushNotification;

use App\Repositories\PaymentRepository as PaymentRepo;

use Log;

use Hash;

use File;

use DB;

use Auth;

use Setting;

use Validator;

use Exception;

use App\Subscription;

use App\Card;

use App\Notification;

use App\PayPerView;

use App\Moderator;

use App\Flag;

use App\Genre;

use App\LikeDislikeVideo;

use App\UserPayment;

use App\User;

use App\Admin;

use App\AdminVideo;

use App\AdminVideoImage;

use App\Settings;

use App\UserRating;

use App\Wishlist;

use App\UserHistory;

use App\Coupon;

use App\Page;

use App\Category;

use App\SubProfile;

use App\UserLoggedDevice;

use App\ContinueWatchingVideo;

class UserApiController extends Controller
{

    public function __construct(Request $request)
    {
        $this->middleware('UserApiVal' , 
                    array('except' => ['register' , 
                                        'login' , 
                                        'forgot_password',
                                        'search_video' , 
                                        'privacy',
                                        'about' , 
                                        'terms',
                                        'contact', 
                                        'home', 
                                        'getCategories', 
                                        'site_settings',
                                        'allPages',
                                        'getPage', 
                                        'check_social', 
                                        'searchAll', 
                                        'reasons'])
                    );

    }
    
    /**
     * Function Name : register()
     * 
     * Register a new user 
     *
     * @param object $request - New User Details
     * 
     * @return Json Response with user details
     *
     */
    public function register(Request $request) {

        try {

            DB::beginTransaction();

            $basicValidator = Validator::make(
                $request->all(),
                array(
                    'device_type' => 'required|in:'.DEVICE_ANDROID.','.DEVICE_IOS.','.DEVICE_WEB,
                    'device_token' => 'required',
                    'login_by' => 'required|in:manual,facebook,google',
                )
            );

            if($basicValidator->fails()) {

                $error_messages = implode(',', $basicValidator->messages()->all());

                $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages'=> $error_messages);

                throw new Exception($error_messages);

            } else {

                $allowedSocialLogin = array('facebook','google');

                if (in_array($request->login_by,$allowedSocialLogin)) {

                    // validate social registration fields

                    $socialValidator = Validator::make(
                                $request->all(),
                                array(
                                    'social_unique_id' => 'required',
                                    'name' => 'required|max:255',
                                    'email' => 'required|email|max:255',
                                    'mobile' => 'digits_between:6,13',
                                    'picture' => '',
                                    'gender' => 'in:male,female,others',
                                )
                            );

                    if ($socialValidator->fails()) {

                        $error_messages = implode(',', $socialValidator->messages()->all());

                        $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages'=> $error_messages);

                        throw new Exception($error_messages);

                    }

                } else {

                    // Validate manual registration fields

                    $manualValidator = Validator::make(
                        $request->all(),
                        array(
                            'name' => 'required|max:255',
                            'email' => 'required|email|max:255',
                            'password' => 'required|min:6',
                            'picture' => 'mimes:jpeg,jpg,bmp,png',
                        )
                    );

                    // validate email existence

                    $emailValidator = Validator::make(
                        $request->all(),
                        array(
                            'email' => 'unique:users,email',
                        )
                    );

                    if($manualValidator->fails()) {

                        $error_messages = implode(',', $manualValidator->messages()->all());

                        $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages'=> $error_messages);

                        throw new Exception($error_messages);
                        
                    } else if($emailValidator->fails()) {

                        $error_messages = implode(',', $emailValidator->messages()->all());

                        $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages'=> $error_messages);
                        
                        throw new Exception($error_messages);

                    } 

                }

                $user = User::where('email' , $request->email)->first();

                $send_email = DEFAULT_FALSE;

                // Creating the user

                if(!$user) {

                    $user = new User;

                    register_mobile($request->device_type);

                    $send_email = DEFAULT_TRUE;

                } else {

                    if ($user->is_activated) {

                    } else {

                        throw new Exception(tr('user_login_decline'));
                    
                    }

                    $sub_profile = SubProfile::where('user_id', $user->id)->first();

                    if (!$sub_profile) {

                        $send_email = DEFAULT_TRUE;

                    }

                }

                if($request->has('name')) {

                    $user->name = $request->name;

                }

                if($request->has('email')) {

                    $user->email = $request->email;

                }

                if($request->has('mobile')) {

                    $user->mobile = $request->mobile;

                }

                if($request->has('password')) {

                    $user->password = Hash::make($request->password);

                }

                $user->gender = $request->has('gender') ? $request->gender : "male";

                $user->token = Helper::generate_token();

                $user->token_expiry = Helper::generate_token_expiry();

                $check_device_exist = User::where('device_token', $request->device_token)->first();

                if($check_device_exist){

                    $check_device_exist->device_token = "";

                    $check_device_exist->save();
                }

                $user->device_token = $request->has('device_token') ? $request->device_token : "";
                $user->device_type = $request->has('device_type') ? $request->device_type : "";
                $user->login_by = $request->has('login_by') ? $request->login_by : "";
                $user->social_unique_id = $request->has('social_unique_id') ? $request->social_unique_id : '';

                $user->picture = asset('placeholder.png');

                // Upload picture
                if($request->login_by == "manual") {

                    if($request->hasFile('picture')) {

                        $user->picture = Helper::normal_upload_picture($request->file('picture'));

                    }

                } else {

                    if($request->has('picture')) {

                        $user->picture = $request->picture;

                    }

                    $user->is_verified = 1;

                }

                $user->is_activated = 1;

                $user->no_of_account = 1;

                $user->is_verified = 1;

                if(Setting::get('email_verify_control')) {

                    $user->status = DEFAULT_FALSE;

                    if ($request->login_by == 'manual') {

                        $user->is_verified = DEFAULT_FALSE;

                    } else {

                        $user->is_verified = 1;
                    }

                } 

                if ($user->is_verified) {

                    $user->status = 1;   

                    $user->logged_in_account = 1;
                }

                if ($user->save()) {

                    // Check the default subscription and save the user type 

                    user_type_check($user->id);

                    // Send welcome email to the new user:
                    if($send_email) {

                        if ($user->login_by == 'manual') {

                            $user->password = $request->password;

                            $subject = tr('user_welcome_title').' '.Setting::get('site_name');

                            $email_data = $user;

                            $page = "emails.welcome";

                            $email = $user->email;

                            Helper::send_email($page,$subject,$email,$email_data);

                        }

                        $sub_profile = new SubProfile;

                        $sub_profile->user_id = $user->id;

                        $sub_profile->name = $user->name;

                        $sub_profile->picture = $user->picture;

                        $sub_profile->status = DEFAULT_TRUE;

                        if ($sub_profile->save()) {

                            // Response with registered user details:

                            if (!Setting::get('email_verify_control')) {

                                $logged_device = new UserLoggedDevice();

                                $logged_device->user_id = $user->id;

                                $logged_device->token_expiry = Helper::generate_token_expiry();

                                $logged_device->status = DEFAULT_TRUE;

                                $logged_device->save();

                            }
                            

                        } else {

                            throw new Exception(tr('sub_profile_not_save'));
                            
                        }


                    }

                    if ($user->is_verified) {

                        $response_array = array(
                            'success' => true,
                            'id' => $user->id,
                            'name' => $user->name,
                            'mobile' => $user->mobile,
                            'gender' => $user->gender,
                            'email' => $user->email,
                            'picture' => $user->picture,
                            'token' => $user->token,
                            'token_expiry' => $user->token_expiry,
                            'login_by' => $user->login_by,
                            'social_unique_id' => $user->social_unique_id,
                            'verification_control'=> Setting::get('email_verify_control'),
                            'sub_profile_id'=>$sub_profile->id,
                            'email_notification'=>$user->email_notification,
                            'payment_subscription' => Setting::get('ios_payment_subscription_status')
                        );

                        $response_array = Helper::null_safe($response_array);

                        $response_array['user_type'] = $user->user_type ? 1 : 0;
                        
                        $response_array['push_status'] = $user->push_status ? 1 : 0;

                    } else {

                       // throw new Exception(Helper::get_error_message(3001), 3001);

                        $response_array = ['success'=>false, 'error_messages'=>Helper::get_error_message(3001), 'error_code'=>3001];

                        DB::commit();

                        return response()->json($response_array, 200);

                    }
                }

            }

            DB::commit();

            $response = response()->json($response_array, 200);

            return $response;

        } catch(Exception $e) {

            DB::rollback();

            $error = $e->getMessage();

            $code = $e->getCode();

            $response_array = ['success'=>false, 'error_messages'=>$error, 'error_code'=>$code];

            return response()->json($response_array);

        }
    }


    /**
     * Function Name : login()
     *
     * Registered user can login using their email & Password
     * 
     * Created At - 
     * 
     *
     * @param object $request - User Email & Password
     *
     * @return Json response with user details
     */
    public function login(Request $request) {

        try {

            DB::beginTransaction();
            
            $basicValidator = Validator::make($request->all(), 
                array(

                    'device_token' => 'required',
                    'device_type' => 'required|in:'.DEVICE_ANDROID.','.DEVICE_IOS.','.DEVICE_WEB,
                    'login_by' => 'required|in:manual,facebook,google',
                )
            );
           
            if($basicValidator->fails()){
                
                $error_messages = implode(',',$basicValidator->messages()->all());
               
                $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages'=> $error_messages);

                throw new Exception($error_messages);
            
            } else {

                /*validate manual login fields*/

                $manualValidator = Validator::make($request->all(),
                    array(
                        'email' => 'required|email',
                        'password' => 'required',
                    )
                );

                if ($manualValidator->fails()) {

                    $error_messages = implode(',',$manualValidator->messages()->all());

                    $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages'=> $error_messages);

                    throw new Exception($error_messages);
                
                }

                $user = User::where('email', '=', $request->email)->first();

                $email_active = DEFAULT_TRUE;
                
                Log::info("Before user Login verification control = ".print_r($user->is_activated, true));

                if($user) {
                
                    if(!$user->is_activated) {

                        throw new Exception(Helper::get_error_message(905));

                    }
                    
                    Log::info("Before Login verification control = ".print_r($user->is_activated, true));

                    if ($user->is_activated) {

                        Log::info("Login verification control = ".print_r($user->is_activated, true));
                        
                        if (Setting::get('email_verify_control')) {

                            if (!$user->is_verified) {

                                $response_array = array( 'success' => false, 'error_messages' => Helper::get_error_message(111), 'error_code' => 111 );

                                Helper::check_email_verification("" , $user->id, $error);

                                $email_active = DEFAULT_FALSE;

                            }

                        }

                        if($email_active) {

                            if(!$user->is_activated) {

                                throw new Exception(Helper::get_error_message(905));

                            }

                            if(Hash::check($request->password, $user->password)){

                                $user->is_verified = 1;

                            } else {

                                $response_array = array( 'success' => false, 'error_messages' => Helper::get_error_message(105), 'error_code' => 105 );

                                throw new Exception(Helper::get_error_message(105));
                                
                            }

                        } else {

                            throw new Exception(tr('verification_code_title'));
                        }

                    }

                    else {

                        $response_array = ['success'=>false, 'error_messages'=>tr('user_login_decline')];

                    }
                } else {

                    $response_array = array( 'success' => false, 'error_messages' => Helper::get_error_message(105), 'error_code' => 105 );

                    throw new Exception(Helper::get_error_message(105));

                }

                if($email_active) {

                    $subProfile = SubProfile::where('user_id', $user->id)->where('status',1)->first();

                    if ($subProfile) {

                        $sub_profile_id = $subProfile->id;

                    } else {

                        $sub_profile = new SubProfile;

                        $sub_profile->user_id = $user->id;

                        $sub_profile->name = $user->name;

                        $sub_profile->status = DEFAULT_TRUE;

                        $sub_profile->picture = $user->picture;

                        if ($sub_profile->save()) {

                            $sub_profile_id = $sub_profile->id;

                            $user->no_of_account += DEFAULT_TRUE;

                            $user->save();

                        } else {

                            throw new Exception(tr('sub_profile_not_save'));
                            
                        }
                    }

                    if ($user->email != DEMO_USER) {

                        if ($user->no_of_account >= $user->logged_in_account) {

                            $model = UserLoggedDevice::where("user_id",$user->id)->get();

                            foreach ($model as $key => $value) {

                                if ($value->token_expiry > time()) {


                                } else {

                                   if ($value->delete()) {

                                        $user->logged_in_account -= 1;

                                        $user->save();

                                    }

                                }

                            }
                        }

                    } else {

                        $user->no_of_account = $user->no_of_account ? $user->no_of_account : 1;

                        $user->logged_in_account = 0;

                        $user->save();

                    }

                    if ($user->no_of_account > $user->logged_in_account) {
 
                        // Generate new tokens
                        // $user->token = Helper::generate_token();

                        $user->token_expiry = Helper::generate_token_expiry();

                        // Save device details
                        $user->device_token = $request->device_token;
                        $user->device_type = $request->device_type;
                        $user->login_by = $request->login_by;

                        if ($user->save()) {

                            $payment_mode_status = $user->payment_mode ? $user->payment_mode : 0;


                            $logged_device = new UserLoggedDevice();

                            $logged_device->user_id = $user->id;

                            $logged_device->token_expiry = Helper::generate_token_expiry();

                            $logged_device->status = DEFAULT_TRUE;

                            $logged_device->save();

                            $user->logged_in_account += 1;

                            $user->save();

                            // Respond with user details

                            $response_array = array(
                                'success' => true,
                                'id' => $user->id,
                                'name' => $user->name,
                                'mobile' => $user->mobile,
                                'email' => $user->email,
                                'gender' => $user->gender,
                                'picture' => $user->picture,
                                'token' => $user->token,
                                'token_expiry' => $user->token_expiry,
                                'login_by' => $user->login_by,
                                // 'user_type' => $user->user_type,
                                'sub_profile_id'=>$sub_profile_id,
                                'social_unique_id' => $user->social_unique_id,
                                // 'push_status' => $user->push_status,
                                'one_time_subscription'=>$user->one_time_subscription,
                                'sub_profile_id'=>$sub_profile_id,
                                'email_notification'=>$user->email_notification,
                                'payment_subscription' => Setting::get('ios_payment_subscription_status')
                            );

                            $response_array = Helper::null_safe($response_array);

                            $response_array['user_type'] = $user->user_type ? 1 : 0;
                            $response_array['push_status'] = $user->push_status ? 1 : 0;


                        } else {

                            throw new Exception(tr('user_details_not_save'));
                            
                        }

                    } else {

                        throw new Exception(tr('no_of_logged_in_device'));
                        
                    }
                        
                } else {

                    $response_array = ['success'=>false, 'error_messages'=>Helper::get_error_message(111)];
                }
            }

            DB::commit();

            $response = response()->json($response_array, 200);

            return $response;

        } catch(Exception $e) {

            DB::rollback();

            $e = $e->getMessage();

            $response_array = ['success'=>false, 'error_messages'=>$e];

            return response()->json($response_array);

        }
    }

 
    /**
     * Function Name : forgot_password()
     *
     * If the user forgot his/her password he can hange it over here
     *
     * @param object $request - Email id
     *
     * @return send mail to the valid user
     */
    
    public function forgot_password(Request $request) {

        try {

            DB::beginTransaction();

            $email =$request->email;
            
            $validator = Validator::make(

                $request->all(),

                array(
                    'email' => 'required|email|exists:users,email',
                ),
                 array(
                    'exists' => 'The :attribute doesn\'t exists',
                )
            );

            if ($validator->fails()) {
                
                $error_messages = implode(',',$validator->messages()->all());
                
                $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages'=> $error_messages);

                throw new Exception($error_messages);
            
            } else {

                $user = User::where('email' , $email)->first();

                if($user) {

                    $new_password = Helper::generate_password();

                    $user->password = Hash::make($new_password);

                    $user->save();

                    $email_data = array();

                    $subject = tr('user_forgot_email_title');

                   // $email = $user->email;

                    $email_data['email']  = $user->email;

                    $email_data['password'] = $new_password;

                    $email_data['template_type'] = FORGOT_PASSWORD;

                    $page = "emails.forgot-password";

                    $email_send = Helper::send_email($page,$subject,$user->email,$email_data);

                    $response_array['success'] = true;

                    $response_array['message'] = Helper::get_message(106);

                } else {

                    throw new Exception(tr('no_user_detail_found'));
                    
                }

            }

            DB::commit();

            $response = response()->json($response_array, 200);

            return $response;

        } catch(Exception $e) {

            DB::rollback();

            $e = $e->getMessage();

            $response_array = ['success'=>false, 'error_messages'=>$e];

            return response()->json($response_array);
        }
    }

    /**
     * Function Name : change_password()
     *
     * To change the password of the user
     *
     * @param object $request - Password & confirm Password
     *
     * @return json response of the user
     */
    public function change_password(Request $request) {

        try {

            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                    'password' => 'required|confirmed',
                    'old_password' => 'required',
                ]);

            if($validator->fails()) {
                
                $error_messages = implode(',',$validator->messages()->all());
               
                $response_array = array('success' => false, 'error' => 'Invalid Input', 'error_code' => 401, 'error_messages' => $error_messages );

                throw new Exception($error_messages);
           
            } else {

                $user = User::find($request->id);

                if(Hash::check($request->old_password,$user->password)) {

                    $user->password = Hash::make($request->password);
                    
                    $user->save();

                    $response_array = Helper::null_safe(array('success' => true , 'message' => Helper::get_message(102)));

                } else {
                    $response_array = array('success' => false , 'error' => Helper::get_error_message(131),'error_messages' => Helper::get_error_message(131) ,'error_code' => 131);

                    throw new Exception(Helper::get_error_message(131));
                    
                }

            }

            DB::commit();

            $response = response()->json($response_array,200);

            return $response;

        } catch(Exception $e) {

            DB::rollback();

            $e = $e->getMessage();

            $response_array = ['success'=>false, 'error_messages'=>$e];

            return response()->json($response_array);

        }

    }

    /** 
     * Function Name : user_details()
     *
     * To display the user details based on user  id
     *
     * @param object $request - User Id
     *
     * @return json response with user details
     */
    public function user_details(Request $request) {

        try {

            $user = User::find($request->id);

            if (!$user) { 

                throw new Exception(tr('no_user_detail_found'));
                
            }

            $subProfile = SubProfile::where('user_id', $user->id)->where('status', DEFAULT_TRUE)->first();

            $sub_profile_id = ($subProfile) ? $subProfile->id : '';

            $card_last_four_number = "";

            if ($user->card_id) {

                $card = Card::find($user->card_id);

                if ($card) {

                    $card_last_four_number = $card->last_four;

                }

            }

            $response_array = array(
                'success' => true,
                'id' => $user->id,
                'name' => $user->name,
                'mobile' => $user->mobile,
                'gender' => $user->gender,
                'email' => $user->email,
                'picture' => $user->picture,
                'token' => $user->token,
                'token_expiry' => $user->token_expiry,
                'login_by' => $user->login_by,
                'social_unique_id' => $user->social_unique_id,
                'user_type'=>$user->user_type,
                'sub_profile_id'=>$sub_profile_id,
                'card_last_four_number'=>$card_last_four_number,
                'email_notification'=>$user->email_notification
            );

            $response = response()->json(Helper::null_safe($response_array), 200);

            return $response;

        } catch(Exception $e) {

            $e = $e->getMessage();

            $response_array = ['success'=>false , 'error_messages'=> $e];

            return response()->json($response_array);
        }
    }
 
    /**
     * Function Name : update_profile()
     *
     * To update the user details
     *
     * @param objecct $request : User details
     *
     * @return json response with user details
     */
    public function update_profile(Request $request) {

        try {

            DB::beginTransaction();
            
            $validator = Validator::make(
                $request->all(),
                array(
                    'name' => 'required|max:255',
                    'email' => 'email|unique:users,email,'.$request->id.'|max:255',
                    'mobile' => 'digits_between:6,13',
                    'picture' => 'mimes:jpeg,bmp,png',
                    'gender' => 'in:male,female,others',
                    'device_token' => '',
                ));

            if ($validator->fails()) {
                // Error messages added in response for debugging
                $error_messages = implode(',',$validator->messages()->all());
                $response_array = array(
                        'success' => false,
                        'error' => Helper::get_error_message(101),
                        'error_code' => 101,
                        'error_messages' => $error_messages
                );

                throw new Exception($error_messages);
                
            } else {

                $user = User::find($request->id);

                if($user) {
                    
                    $user->name = $request->name ? $request->name : $user->name;
                    
                    if($request->has('email')) {

                        $user->email = $request->email;
                    }

                    $user->mobile = $request->mobile ? $request->mobile : $user->mobile;

                    $user->gender = $request->gender ? $request->gender : $user->gender;

                    $user->address = $request->address ? $request->address : $user->address;

                    $user->description = $request->description ? $request->description : $user->address;

                    // Upload picture
                    if ($request->hasFile('picture') != "") {

                        Helper::delete_picture($user->picture, "/uploads/images/"); // Delete the old pic

                        $user->picture = Helper::normal_upload_picture($request->file('picture'));

                    }

                    if ($user->save()) {

                        $payment_mode_status = $user->payment_mode ? $user->payment_mode : "";

                        $subProfile = SubProfile::where('user_id', $user->id)->where('status', DEFAULT_TRUE)->first();

                        $sub_profile_id = ($subProfile) ? $subProfile->id : '';

                        $response_array = array(
                            'success' => true,
                            'id' => $user->id,
                            'name' => $user->name,
                            'mobile' => $user->mobile,
                            'gender' => $user->gender,
                            'email' => $user->email,
                            'picture' => $user->picture,
                            'token' => $user->token,
                            'token_expiry' => $user->token_expiry,
                            'login_by' => $user->login_by,
                            'social_unique_id' => $user->social_unique_id,
                            'sub_profile_id'=>$sub_profile_id
                        );

                        $response_array = Helper::null_safe($response_array);

                    } else {

                        throw new Exception(tr('user_details_not_save'));
                        
                    }

                } else {

                    throw new Exception(tr('no_user_detail_found'));
                    
                }
            
            }

            DB::commit();

            $response = response()->json($response_array, 200);

            return $response;

        } catch (Exception $e) {

            DB::rollback();

            $e = $e->getMessage();

            $response_array = ['success'=>false, 'error_messages'=>$e];

            return $response_array;
        }
    }

    /**
     * Function Name : delete_account()
     * 
     * Delete user account based on user id
     * 
     * @param object $request - Password and user id
     *
     * @return json with boolean output
     */
    public function delete_account(Request $request) {

        try {

            DB::beginTransaction();

            $validator = Validator::make(
                $request->all(),
                array(
                    'password' => '',
                ));

            if ($validator->fails()) {

                $error_messages = implode(',',$validator->messages()->all());

                $response_array = array('success' => false,'error' => Helper::get_error_message(101),'error_code' => 101,'message' => $error_messages
                );

                throw new Exception($error_messages);
                
            } else {

                $user = User::find($request->id);

                if (!$user) {

                    throw new Exception(tr('no_user_detail_found'));
                    
                }

                if($user->login_by != 'manual') {

                    $allow = 1;

                } else {

                    if(Hash::check($request->password, $user->password)) {

                        $allow = 1;

                    } else {

                        $allow = 0 ;

                        $response_array = array('success' => false , 'message' => Helper::get_error_message(108) ,'error_code' => 108);

                        throw new Exception(Helper::get_error_message(108));
                        
                    }

                }

                if($allow) {

                    if ($user->device_type) {

                        // Load Mobile Registers
                        subtract_count($user->device_type);
                        
                    }

                    $user->delete();

                    $response_array = array('success' => true , 'message' => tr('user_account_delete_success'));

                }

            }

            DB::commit();

    		return response()->json($response_array,200);

        } catch(Exception $e) {

            DB::rollback();

            $e = $e->getMessage();

            $response_array = ['success'=>false, 'error_messages'=>$e];

            return response()->json($response_array);
        }

	}

    /**
     * Function Name : add_wishlist()
     *
     * To add wishlist of logged in user
     *
     * @param object $request - Sub profile id & Video id
     *
     * @return response of wishlist
     */
    public function add_wishlist(Request $request) {

        Log::info("add_wishlist".print_r($request->all(), true));

        try {

            DB::beginTransaction();

            if (!$request->has('sub_profile_id')) {

                $sub_profile = SubProfile::where('user_id', $request->id)->where('status', DEFAULT_TRUE)->first();

                if ($sub_profile) {

                    $request->request->add([ 

                        'sub_profile_id' => $sub_profile->id,

                    ]);

                } else {

                    throw new Exception(tr('sub_profile_details_not_found'));

                }

            } else {

                $subProfile = SubProfile::where('user_id', $request->id)
                            ->where('id', $request->sub_profile_id)->first();

                if (!$subProfile) {

                    throw new Exception(tr('sub_profile_details_not_found'));
                    
                }

            } 

            $validator = Validator::make(
                $request->all(),
                array(
                    'admin_video_id' => 'required|integer|exists:admin_videos,id',
                    'sub_profile_id'=>'required|exists:sub_profiles,id'
                ),
                array(
                    'exists' => 'The :attribute doesn\'t exists please provide correct video id',
                    'unique' => 'The :attribute already added in wishlist.'
                )
            );

            if ($validator->fails()) {

                $error_messages = implode(',', $validator->messages()->all());

                $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages'=>$error_messages);

                throw new Exception($error_messages);

            } else {

                if (check_flag_video($request->admin_video_id,$request->sub_profile_id)) {

                    throw new Exception(tr('flagged_video'));

                }

                $wishlist = Wishlist::where('user_id' , $request->sub_profile_id)
                            ->where('admin_video_id' , $request->admin_video_id)
                            ->first();

                if(count($wishlist) > 0) {

                    $wishlist->delete();

                    $response_array = ['success'=>true, 'message'=> tr('wishlist_removed'),'wishlist_status' => 0];
                    
                } else {
                    
                    $wishlist = new Wishlist();

                    $wishlist->user_id = $request->sub_profile_id;

                    $wishlist->admin_video_id = $request->admin_video_id;

                    $wishlist->status = DEFAULT_TRUE;

                    if ($wishlist->save()) {

                        $response_array = array('success' => true ,
                                'wishlist_id' => $wishlist->id ,
                                'wishlist_status' => $wishlist->status,
                                'message' => tr('added_wishlist'));

                    } else {

                        throw new Exception(tr('wishlist_not_save'));
                        
                    }
                }
               
            }

            DB::commit();

            $response = response()->json($response_array, 200);

            return $response;

        } catch (Exception $e) {

            DB::rollback();

            $e = $e->getMessage();

            $response_array = ['success'=>false, 'error_messages'=>$e];

            return response()->json($response_array);
            
        }
    
    }

    /**
     * Function Name : get_wishlist()
     *
     * To get all the lists based on logged in user id
     *
     * @param object $request - Wishlist id
     *
     * @return respone with array of objects
     */
    public function get_wishlist(Request $request)  {

        try {

            $validator = Validator::make(
                $request->all(),
                array(
                    'skip' => 'required|numeric',
                )
            );

            if ($validator->fails()) {

                $error_messages = implode(',', $validator->messages()->all());

                throw new Exception($error_messages);
                
            } else {

                if (!$request->has('sub_profile_id')) {

                    $sub_profile = SubProfile::where('user_id', $request->id)->where('status', DEFAULT_TRUE)->first();

                    if ($sub_profile) {
                        
                        $sub_profile_id = $sub_profile->id;

                    } else  {

                        throw new Exception(tr('sub_profile_details_not_found'));
                        
                    }

                } else  {

                    $subProfile = SubProfile::where('user_id', $request->id)
                                    ->where('id', $request->sub_profile_id)->first();

                    if (!$subProfile) {

                        throw new Exception(tr('sub_profile_details_not_found'));
                        
                    }

                    $sub_profile_id = $request->sub_profile_id;
                 
                }
            

                $wishlist = Helper::wishlist($sub_profile_id,NULL,$request->skip);

                $wishlist_video = [];

                if ($wishlist != null && !empty($wishlist)) {

                    foreach ($wishlist as $key => $value) {
                        
                        $wishlist_video[] = displayFullDetails($value->admin_video_id, $request->id);

                    }
                }

                $total = count($wishlist_video);

        		$response_array = array('success' => true, 'wishlist' => $wishlist_video , 'total' => $total);

                return response()->json($response_array, 200);
            }

        } catch (Exception $e) {

            $e = $e->getMessage();

            $response_array = ['success'=>false, 'error_messages'=>$e];

            return response()->json($response_array);

        }
    
    }

    /**
     * Function Name : delete_wishlist()
     * 
     * To delete wishlist based on the logged in user id and video id
     *
     * @param object $request - User Id & Video Id
     *
     * @return response with boolean status
     *
     */
    public function delete_wishlist(Request $request) {

        Log::info(print_r($request->all() , true));

        try {

            DB::beginTransaction();

            if (!$request->has('sub_profile_id')) {

                $sub_profile = SubProfile::where('user_id', $request->id)->where('status', DEFAULT_TRUE)->first();

                if ($sub_profile) {

                    $request->request->add([ 
                        'sub_profile_id' => $sub_profile->id,
                    ]);

                } else {

                    throw new Exception(tr('sub_profile_details_not_found'));
                    
                }

            } else {

                $subProfile = SubProfile::where('user_id', $request->id)
                                    ->where('id', $request->sub_profile_id)->first();

                if (!$subProfile) {

                    throw new Exception(tr('sub_profile_details_not_found'));
                    
                }
            
            }

            $validator = Validator::make(
                $request->all(),
                array(
                    'wishlist_id' => 'integer|exists:admin_videos,id',
                    'sub_profile_id' => 'integer|exists:sub_profiles,id',
                ),
                array(
                    'exists' => 'The :attribute doesn\'t exists please add to wishlists',
                )
            );

            if ($validator->fails()) {

                $error_messages = implode(',', $validator->messages()->all());

                $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages'=>$error_messages);

                throw new Exception($error_messages);
                
            } else {

                /** Clear All wishlist of the loggedin user */

                if($request->status == 1) {

                    Log::info("Check Delete Wishlist - 1");

                    $wishlist = Wishlist::where('user_id',$request->sub_profile_id)->delete();

                } else {  /** Clear particularv wishlist of the loggedin user */

                    Log::info("Check Delete Wishlist - 0");

                    $wishlist = Wishlist::where('admin_video_id',$request->wishlist_id)
                            ->where('user_id', $request->sub_profile_id)
                            ->first();

                    if($wishlist) {

                        $wishlist->delete();
                    }
                }

    			$response_array = array('success' => true, 'message'=>tr('delete_wishlist_success'));
           
            }

            DB::commit();

            $response = response()->json($response_array, 200);

            return $response;

        } catch (Exception $e) {

            DB::rollback();

            $e = $e->getMessage();

            $response_array = ['success'=>false, 'error_messages'=>$e];

            return response()->json($response_array);

        }
    
    }

    /**
     * Function Name : add_history
     *
     * To add history based on logged in user id
     *
     * @param object $request - History Id
     *
     * @return response with history details
     */
    public function add_history(Request $request)  {

        try {

            DB::beginTransaction();

            if (!$request->has('sub_profile_id')) {

                $sub_profile = SubProfile::where('user_id', $request->id)->where('status', DEFAULT_TRUE)->first();

                if ($sub_profile) {

                    $request->request->add([ 
                        'sub_profile_id' => $sub_profile->id,
                    ]);

                } else {

                    throw new Exception(tr('sub_profile_details_not_found'));
                    
                }
            } else {

                $subProfile = SubProfile::where('user_id', $request->id)
                                        ->where('id', $request->sub_profile_id)->first();

                if (!$subProfile) {

                    throw new Exception(tr('sub_profile_details_not_found'));
                    
                }
            }

            $validator = Validator::make(
                $request->all(),
                array(
                    'admin_video_id' => 'required|integer|exists:admin_videos,id',
                    'sub_profile_id' => 'required|integer|exists:sub_profiles,id',
                ),
                array(
                    'exists' => 'The :attribute doesn\'t exists please provide correct video id',
                    'unique' => 'The :attribute already added in history.'
                )
            );

            if ($validator->fails()) {

                $error_messages = implode(',', $validator->messages()->all());

                $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages'=>$error_messages);

                throw new Exception($error_messages);

            } else {

                if (check_flag_video($request->admin_video_id,$request->sub_profile_id)) {

                    throw new Exception(tr('flagged_video'));

                }

                $history = UserHistory::where('user_id' , $request->sub_profile_id)
                            ->where('admin_video_id' ,$request->admin_video_id)->first();

                if ($history) {

                    $response_array = array('success' => true , 'message'=>tr('added_history'));

                } else {

                    //Save Wishlist
                    $rev_user = new UserHistory();

                    $rev_user->user_id = $request->sub_profile_id;

                    $rev_user->admin_video_id = $request->admin_video_id;

                    $rev_user->save();

                    $response_array = array('success' => true);
                }

                $payperview = PayPerView::where('user_id', $request->id)
                                ->where('video_id',$request->admin_video_id)
                                ->where('status',0)
                                ->orderBy('created_at', 'desc')
                                ->first();

                if($video = AdminVideo::find($request->admin_video_id)) {

                    // Check the video have PPV -> if yes need to restrict the watch_count revenue

                    if ($video->amount <= 0) {

                        \Log::info("uploaded_by ".$video->uploaded_by);

                        \Log::info("Viewer Count ".Setting::get('video_viewer_count'));

                        if($video->watch_count >= Setting::get('video_viewer_count') && is_numeric($video->uploaded_by)) {

                            $video_amount = Setting::get('amount_per_video');

                            // $video->watch_count = $video->watch_count + 1;

                            $video->redeem_amount += $video_amount;

                            Log::info("Uploaded By ".$video->uploaded_by);

                            if($moderator = Moderator::find($video->uploaded_by)) {

                                Log::info("Inside");

                                $moderator->total_user_amount += $video_amount;

                                $moderator->remaining_amount += $video_amount;

                                $moderator->total += $video_amount;

                                $moderator->save();

                            }

                            add_to_redeem($video->uploaded_by , $video_amount);

                        } 
                    }

                    $video->watch_count += 1;

                    $video->save();
                }

                
                if ($payperview) {

                    $payperview->status = DEFAULT_TRUE;

                    $payperview->save();

                }

            }

            DB::commit();

            return response()->json($response_array, 200);

        } catch (Exception $e) {

            DB::rollback();

            $e = $e->getMessage();

            $response_array = ['success'=>false, 'error_messages'=>$e];

            return response()->json($response_array);
        }
    
    }

    /**
     * Function Name : get_history()
     *  
     * To get all the history details based on logged in user id
     *
     * @param object $request - User Profile details
     *
     * @return Response with list of details
     */     
    public function get_history(Request $request) {

        try {

            $validator = Validator::make(
                $request->all(),
                array(
                    'skip' => 'required|numeric',
                )
            );

            if ($validator->fails()) {

                $error_messages = implode(',', $validator->messages()->all());

                throw new Exception($error_messages);
                
            } else {

                if (!$request->has('sub_profile_id')) {

                    $sub_profile = SubProfile::where('user_id', $request->id)->where('status', DEFAULT_TRUE)->first();

                    if ($sub_profile) {

                        $request->id = $sub_profile->id;

                    } else {

                        throw new Exception(tr('sub_profile_details_not_found'));
                    }

                } else {

                    $subProfile = SubProfile::where('user_id', $request->id)
                                                ->where('id', $request->sub_profile_id)->first();

                    if (!$subProfile) {

                        throw new Exception(tr('sub_profile_details_not_found'));
                        
                    }

                    $request->id = $request->sub_profile_id;

                }
                
        		//get wishlist

                $history = Helper::watch_list($request->id,NULL,$request->skip);

                $history_video = [];

                if ($history != null && !empty($history)) {

                    foreach ($history as $key => $value) {
                        
                        $history_video[] = displayFullDetails($value->admin_video_id, $request->id);

                    }
                }

                $total = count($history_video);

        		$response_array = array('success' => true, 'history' => $history_video , 'total' => $total);

                return response()->json($response_array, 200);

            }

        } catch (Exception $e) {

            $e = $e->getMessage();

            $response_array = ['success'=>false, 'error_messages'=>$e];

            return response()->json($response_array);
        }   
    
    }

    /**
     * Function Name : delete_history()
     *
     * To delete history based on login id
     *
     * @param Object $request - History Id
     *
     * @return Json object based on history
     */
    public function delete_history(Request $request) {

        try {

            DB::beginTransaction();

            if (!$request->has('sub_profile_id')) {

                $sub_profile = SubProfile::where('user_id', $request->id)->where('status', DEFAULT_TRUE)->first();

                if ($sub_profile) {

                    $request->request->add([ 
                        'sub_profile_id' => $sub_profile->id,
                    ]);

                } else {

                    throw new Exception(tr('sub_profile_details_not_found'));

                }

            } else {

                $subProfile = SubProfile::where('user_id', $request->id)
                                                ->where('id', $request->sub_profile_id)->first();

                if (!$subProfile) {

                    throw new Exception(tr('sub_profile_details_not_found'));
                    
                }

            }

            $validator = Validator::make(
                $request->all(),
                array(
                    'admin_video_id' => 'integer|exists:admin_videos,id',
                    'sub_profile_id' => 'required|integer|exists:sub_profiles,id',
                ),
                array(
                    'exists' => 'The :attribute doesn\'t exists please add to history',
                )
            );

            if ($validator->fails()) {

                $error_messages = implode(',', $validator->messages()->all());

                $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages'=>$error_messages);

                throw new Exception($error_messages);
                
            } else {

                if($request->has('status')) {

                    $history = UserHistory::where('user_id',$request->sub_profile_id)->delete();

                } else {
                    
                    $history = UserHistory::where('admin_video_id' ,  $request->admin_video_id)
                        >where('user_id', $request->sub_profile_id)
                        ->delete();
                }

                $response_array = array('success' => true, 'message'=>tr('delete_history_success'));
            }

            DB::commit();

            $response = response()->json($response_array, 200);

            return $response;

        } catch(Exception $e) {

            DB::rollback();

            $e = $e->getMessage();

            $response_array = ['success'=>false, 'error_messages'=>$e];

            return $response_array;
        }
    }

    /**
     * Function Name : get_categories
     *
     * To get all the categories
     *
     * @param object $request - As of now no attributes
     *
     * @return array of response
     */
    public function get_categories(Request $request) {

        $categories = get_categories();

        if($categories) {

            if ($categories != null && !empty($categories)) {

                $response_array = array('success' => true , 'categories' => $categories->toArray());

            } else {

                $response_array = array('success' => true , 'categories' => []);
            }
        
        } else {

            $response_array = array('success' => false,'error_messages' => Helper::get_error_message(135),'error_code' => 135);
        }

        $response = response()->json($response_array, 200);

        return $response;
    }


    /**
     * Function Name : get_sub_categories()
     *
     * To get sub categories based on category id
     *
     * @param object $request - Category id
     *
     * @return response of array
     */
    public function get_sub_categories(Request $request) {

        $validator = Validator::make(
            $request->all(),
            array(
                'category_id' => 'required|integer|exists:categories,id',
            ),
            array(
                'exists' => 'The :attribute doesn\'t exists',
            )
        );

        if ($validator->fails()) {

            $error_messages = implode(',', $validator->messages()->all());

            $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages'=>$error_messages);

        } else {

            $sub_categories = get_sub_categories($request->category_id);

            if($sub_categories) {

                if ($sub_categories != null && !empty($sub_categories)) {

                    $response_array = array('success' => true , 'sub_categories' => $sub_categories->toArray());

                } else {

                    $response_array = array('success' => true , 'sub_categories' => []);

                }

            } else {
                $response_array = array('success' => false,'error_messages' => Helper::get_error_message(130),'error_code' => 130);
            }

        }

        $response = response()->json($response_array, 200);

        return $response;
    }

    /**
     * Function Name : home()
     * 
     * To list out all wishlist, history, recommended videos, suggestion videos based on logged in user 
     *
     * @param object @request - User Id, skip , take and etc
     *
     * @return response of array
     */
    public function home(Request $request) {

        Log::info("HOME PAGE".print_r($request->all() , true));

        $videos = $wishlist = $recent =  $banner = $trending = $history = $suggestion =array();

        counter('home');

        if (!$request->has('sub_profile_id')) {

            $sub_profile = SubProfile::where('user_id', $request->id)->where('status', DEFAULT_TRUE)->first();

            if ($sub_profile) {

                $request->request->add([ 

                    'sub_profile_id' => $sub_profile->id,

                ]);

                $id = $sub_profile->id;

            } else {

                $response_array = ['success'=>false, 'error_messages'=>tr('sub_profile_details_not_found')];

                return response()->json($response_array , 200);

            }

        } else {

            $subProfile = SubProfile::where('user_id', $request->id)
                        ->where('id', $request->sub_profile_id)->first();

            if (!$subProfile) {

                $response_array = ['success'=>false, 'error_messages'=>tr('sub_profile_details_not_found')];
                
                return response()->json($response_array , 200);
                
            } else {

                $id = $subProfile->id;

            }

        } 

        $banner['name'] = tr('mobile_banner_heading');

        $banner['key'] = BANNER;

        $banner['list'] = Helper::banner_videos($id);

        $continue_videos['name'] = tr('continue_watching_videos');

        $continue_videos['key'] = CONTINUE_WATCHING;

        $continnue_watching_videos = Helper::continue_watching_videos($request->sub_profile_id, $request->device_type, 0);

        $continue_watch_videos = [];

        foreach ($continnue_watching_videos as $key => $value) {
            
            $continue_watch_videos[] = displayFullDetails($value->admin_video_id, $id);

        }

        $continue_videos['list'] = $continue_watch_videos;

        array_push($videos , $continue_videos);

        $wishlist['name'] = tr('mobile_wishlist_heading');

        $wishlist['key'] = WISHLIST;

        $wishlists = Helper::wishlist($request->sub_profile_id, $request->device_type);

        $wishlist_video = [];

        foreach ($wishlists as $key => $value) {
            
            $wishlist_video[] = displayFullDetails($value->admin_video_id, $id);

        }

        $wishlist['list'] = $wishlist_video;

        array_push($videos , $wishlist);

        $recent['name'] = tr('mobile_recent_upload_heading');

        $recent['key'] = RECENTLY_ADDED;

        $recents = Helper::recently_added(WEB, 0, 0, $id);

        $recent_videos = [];

        foreach ($recents as $key => $value) {
            
            $recent_videos[] = displayFullDetails($value->admin_video_id, $id);

        }

        $recent['list'] = $recent_videos;


        array_push($videos , $recent);

        $trending['name'] = tr('mobile_trending_heading');
        $trending['key'] = TRENDING;

        $trendings = Helper::trending(WEB, 0, 0,$id);

        $trending_videos = [];

        foreach ($trendings as $key => $value) {
            
            $trending_videos[] = displayFullDetails($value->admin_video_id, $id);

        }

        $trending['list'] = $trending_videos;

        array_push($videos, $trending);

        $history['name'] = tr('mobile_watch_again_heading');

        $history['key'] = WATCHLIST;

        $history_videos = Helper::watch_list($request->sub_profile_id, $request->device_type);

        $histories = [];

        foreach ($history_videos as $key => $value) {
            
            $histories[] = displayFullDetails($value->admin_video_id, $id);

        }

        $history['list'] = $histories;

        array_push($videos , $history);

        $suggestion['name'] = tr('mobile_suggestion_heading');

        $suggestion['key'] = SUGGESTIONS;

        $suggestion_videos = Helper::suggestion_videos(WEB, null, null, $id);

        $suggestions = [];

        foreach ($suggestion_videos as $key => $value) {
            
            $suggestions[] = displayFullDetails($value->admin_video_id, $id);

        }

        $suggestion['list'] = $suggestions;

        array_push($videos , $suggestion);

        $recent_video = Helper::recently_video(0, $id);

        $get_video_details = ($recent_video) ? displayFullDetails($recent_video->admin_video_id, $id) : '';

        $response_array = array('success' => true , 'data' => $videos , 'banner' => $banner, 'recent_video'=>$get_video_details);

        return response()->json($response_array , 200);

    }


    /**
     * Function Name : common()
     *
     * To get common response from all the section of videos like recent, recommended, wishlist and history
     *
     * @param object @request - User Id, skip , take and etc
     * 
     * @request response of array
     */  
    public function common(Request $request) {

        $validator = Validator::make(
            $request->all(),
            array(
                'key'=>'required',
                'skip' => 'required|numeric',
            )
        );

        if ($validator->fails()) {

            $error_messages = implode(',', $validator->messages()->all());

            return response()->json(['success'=>false, 'error_messages'=>$error_messages]);
            
        } else {

            $key = $request->key;

            $total = 18;

            switch($key) {

                case TRENDING:

                    $videos = Helper::trending(NULL,$request->skip);

                    break;

                case WISHLIST:

                    $videos = Helper::wishlist($request->id,NULL,$request->skip);


                    $total = get_wishlist_count($request->id);

                    break;

                case SUGGESTIONS:

                    $videos = Helper::suggestion_videos(NULL,$request->skip);

                    break;
                case RECENTLY_ADDED:

                    $videos = Helper::recently_added(NULL,$request->skip);

                    break;

                case WATCHLIST:

                    $videos = Helper::watch_list($request->id,NULL,$request->skip);

                    $total = get_history_count($request->id);

                    break;

                default:

                    $videos = Helper::recently_added(NULL,$request->skip);
            }


            $response_array = array('success' => true , 'data' => $videos , 'total' => $total);

            return response()->json($response_array , 200);

        }

    }

    /**
     * Function Name : get_category_videos()
     *
     * Based on category id , videos will dispaly
     *
     * @param object @request - Category id
     *
     * @return array of response
     */
    public function get_category_videos(Request $request) {

        $validator = Validator::make(
            $request->all(),
            array(
                'category_id' => 'required|integer|exists:categories,id',
            ),
            array(
                'exists' => 'The :attribute doesn\'t exists',
            )
        );

        if ($validator->fails())  {

            $error_messages = implode(',', $validator->messages()->all());

            $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages'=>$error_messages);

        } else {

            $data = array();

            $sub_categories = get_sub_categories($request->category_id);

            if($sub_categories) {

                foreach ($sub_categories as $key => $sub_category) {

                    $videos = Helper::sub_category_videos($sub_category->id);

                    if(count($videos) > 0) {

                        $results['sub_category_name'] = $sub_category->name;
                        $results['key'] = $sub_category->id;
                        $results['videos_count'] = count($videos);
                        $results['videos'] = $videos->toArray();

                        array_push($data, $results);
                    }
                }
            }

            $response_array = array('success' => true, 'data' => $data);
        }

        $response = response()->json($response_array, 200);

        return $response;

    }

    /**
     * Function Name : get_category_videos()
     *
     * Based on category id , videos will dispaly
     *
     * @param object @request - Category id
     *
     * @return array of response
     */
    public function get_sub_category_videos(Request $request) {

        $validator = Validator::make(
            $request->all(),
            array(
                'sub_category_id' => 'required|integer|exists:sub_categories,id',
                'skip' => 'integer'
            ),
            array(
                'exists' => 'The :attribute doesn\'t exists',
            )
        );

        if ($validator->fails()) {
            $error_messages = implode(',', $validator->messages()->all());

            $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages'=>$error_messages);

        } else {

            $data = array();

            $total = 18;

            if($videos = Helper::sub_category_videos($request->sub_category_id , NULL,$request->skip)) {
                $data = $videos->toArray();
            }

            $total = get_sub_category_video_count($request->sub_category_id);

            $response_array = array('success' => true, 'data' => $data , 'total' => $total);

        }

        $response = response()->json($response_array, 200);
        
        return $response;

    }


    /**
     * Function Name : single_video()
     *
     * To get a single video page based on the id
     *
     * Edited By : vidhya R (11/12/2017)
     *
     * @param object $request - Video Id
     *
     * @return response of single video details
     */

    public function single_video(Request $request) {

        try {

            $validator = Validator::make(
                $request->all(),
                array(
                    'admin_video_id' => 'required|integer|exists:admin_videos,id',
                  // 'sub_profile_id'=>'required|sub_profiles,id'
                ),
                array(
                    'exists' => 'The :attribute doesn\'t exists',
                )
            );

            if ($validator->fails()) {

                $error_messages = implode(',', $validator->messages()->all());

                $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages'=>$error_messages);

                throw new Exception($error_messages);
                
            } else {

                if (!$request->has('sub_profile_id')) {

                    $sub_profile = SubProfile::where('user_id', $request->id)->where('status', DEFAULT_TRUE)->first();

                    if ($sub_profile) {

                        $request->request->add([ 
                            'sub_profile_id' => $sub_profile->id,
                        ]);
                    }

                }  else {

                    $subProfile = SubProfile::where('user_id', $request->id)
                                        ->where('id', $request->sub_profile_id)->first();

                    if (!$subProfile) {

                        throw new Exception(tr('sub_profile_details_not_found'));
                        
                    }

                }

                if (check_flag_video($request->admin_video_id,$request->sub_profile_id)) {

                    throw new Exception(Helper::get_error_message(904), 904);

                }

                $data = Helper::get_video_details($request->admin_video_id);

                $trailer_video = $ios_trailer_video = $data->trailer_video;

                $video = $ios_video = $data->video;

                $trailer_video_rtmp_smil = $video_rtmp_smil = $trailer_subtitle_name = $video_subtitle_name = "";

                if($data->video_type == VIDEO_TYPE_UPLOAD && $data->video_upload_type == VIDEO_UPLOAD_TYPE_DIRECT) {

                    $trailer_subtitle_name = $data->trailer_subtitle ? get_video_end($data->trailer_subtitle) : "";

                    $video_subtitle_name = $data->video_subtitle ? get_video_end($data->video_subtitle) : "";

                    if(check_valid_url($data->trailer_video)) {

                        $trailer_video = Helper::convert_rtmp_to_secure(get_video_end($data->trailer_video) , $data->trailer_video);

                    
                        $ios_trailer_video = Helper::convert_hls_to_secure(get_video_end($data->trailer_video) , $data->trailer_video);

                        
                        // $ios_trailer_video = Setting::get('HLS_STREAMING_URL').get_video_end($data->trailer_video);
                    
                    }

                    if(check_valid_url($data->video)) {

                        // if(Setting::get('streaming_url'))
                            $video = Helper::convert_rtmp_to_secure(get_video_end($data->video) , $data->video);
                            
                            $ios_video = Helper::convert_hls_to_secure(get_video_end($data->video) , $data->video);
                            // $video = Setting::get('streaming_url').get_video_end($data->video);

                        // if(Setting::get('HLS_STREAMING_URL'))
                            // $ios_video = Setting::get('HLS_STREAMING_URL').get_video_end($data->video);
                    
                    }

                    if ($request->device_type == DEVICE_WEB) {

                        if (\Setting::get('streaming_url')) {
                            
                            if($data->trailer_video_resolutions) {

                                $trailer_video_rtmp_smil = get_video_end_smil($data->trailer_video).'.smil';

                                // $trailer_video = Helper::web_url().'/uploads/smil/'.$trailer_video_rtmp_smil;

                                $trailer_video = Helper::convert_smil_to_secure($trailer_video_rtmp_smil , $trailer_video);

                            } 

                            if ($data->video_resolutions) {

                                $video_rtmp_smil = get_video_end_smil($data->video).'.smil';

                                // $video = Helper::web_url().'/uploads/smil/'.$video_rtmp_smil;

                                Log::info("video_rtmp_smil".$video_rtmp_smil);

                                $video = Helper::convert_smil_to_secure($video_rtmp_smil , $video);
                            
                            }
                            
                        } 
                    
                    }
                }

                if($data->video_type == VIDEO_TYPE_YOUTUBE) {

                    if ($request->device_type != DEVICE_WEB) {

                        $video = $ios_video = get_api_youtube_link($data->video);

                        $trailer_video =  $ios_trailer_video = get_api_youtube_link($data->trailer_video);

                    } else {

                        $video = $ios_video = get_youtube_embed_link($data->video);

                        $trailer_video =  $ios_trailer_video = get_youtube_embed_link($data->trailer_video);


                    }

                }

                $admin_video_images = AdminVideoImage::where('admin_video_id' , $request->admin_video_id)
                                    ->orderBy('is_default' , 'desc')
                                    ->get();

                if ($ratings = Helper::video_ratings($request->admin_video_id,0)) {

                    $ratings = $ratings->toArray();

                }

                $wishlist_status = Helper::wishlist_status($request->admin_video_id,$request->sub_profile_id);

                $history_status = Helper::history_status($request->sub_profile_id,$request->admin_video_id);

                $like_status = Helper::like_status($request->id,$request->admin_video_id);

                $share_link = Setting::get('ANGULAR_SITE_URL').'video/'.$request->admin_video_id;

                $user = User::find($request->id);

                $cnt = $this->watch_count($request)->getData();

                $likes_count = Helper::likes_count($request->admin_video_id);

                $video_play_duration = videoPlayDuration($request->admin_video_id, $request->sub_profile_id);

                $is_ppv_status = ($data->type_of_user == NORMAL_USER || $data->type_of_user == BOTH_USERS) ? ( ( $user->user_type == 0 ) ? DEFAULT_TRUE : DEFAULT_FALSE ) : DEFAULT_FALSE; 

                $response_array = array(
                        'success' => true,
                        'user_type' => $user->user_type ? $user->user_type : 0,
                        'wishlist_status' => $wishlist_status,
                        'history_status' => $history_status,
                        'share_link' => $share_link,
                        'main_video' => $video,
                        'trailer_video' => $trailer_video,
                        'ios_video' => $ios_video,
                        'ios_trailer_video' => $ios_trailer_video,
                        'is_liked' => $like_status,
                        'currency' => Setting::get('currency') ? Setting::get('currency') : "$",
                        'likes' => number_format_short($likes_count),
                        'video_subtitle'=>$data->video_subtitle,
                        'trailer_subtitle'=>$data->trailer_subtitle,
                        'trailer_embed_link'=>route('embed_video', array('v_t'=>2, 'u_id'=>$data->unique_id)),
                        'video_embed_link'=>route('embed_video', array('v_t'=>1, 'u_id'=>$data->unique_id)),
                        'pay_per_view_status'=>watchFullVideo($user->id, $user->user_type, $data),
                        'is_ppv_subscribe_page'=>$is_ppv_status,
                        'video_images' => $admin_video_images,
                        'video' => $data,
                        'comments' => $ratings,
                        'watch_count'=>number_format_short($data->watch_count),
                        'trailer_subtitle_name' => $trailer_subtitle_name,
                        'video_subtitle_name' => $video_subtitle_name,
                        'trailer_video_rtmp_smil' => $trailer_video,
                        'video_rtmp_smil' => $video,
                        'video_play_duration'=>$video_play_duration,
                        'seek'=>seek($video_play_duration),
                );
            }

            $response = response()->json($response_array, 200);

            return $response;

        } catch (Exception $e) {

            $message = $e->getMessage();

            $code = $e->getCode();

            $response_array = ['success'=>false, 'error_messages'=>$message, 'error_code'=> $code];

            return response()->json($response_array);
        }

    }

    /**
     * Function Name : search_video()
     *
     * To search videos based on title
     *
     * @param object $request - Title of the video (For Web Usage)
     *
     * @return response of the array 
     */
    public function search_video(Request $request) {

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

            $query = AdminVideo::where('is_approved' , 1)
                ->where('title', 'like', '%' . $request->key . '%')
                ->whereNotIn('admin_videos.is_banner',[1])
                ->where('status' , 1)->orderBy('created_at' , 'desc');

            $subProfile = SubProfile::where('user_id', $request->id)
                        ->where('id', $request->sub_profile_id)->first();

            if (!$subProfile) {

                return response()->json(['success'=>false, 'error_messages'=>tr('sub_profile_details_not_found')]);
                
            }

            if ($request->key) {

               

            } else {

                $query->skip(0)->take(6);
            }
                
            if ($request->sub_profile_id) {
                
                $flagVideos = getFlagVideos($request->sub_profile_id);

                if($flagVideos) {

                    $query->whereNotIn('admin_videos.id', $flagVideos);

                }

            }

            $videos = $query->get();

            $results = [];

            if (!empty($videos) && $videos != null) {

                if($request->device_type == DEVICE_WEB) {

                    $chunk = $videos->chunk(4);

                    foreach ($chunk as $key => $value) {

                        $group = [];

                        foreach ($value as $key => $data) {
                         
                            $group[] = displayFullDetails($data->id, $request->sub_profile_id);

                        }

                        array_push($results , $group);

                    }

                } else {

                    foreach ($videos as $key => $value) {

                        $results[] = displayFullDetails($value->id, $request->sub_profile_id);

                    }
                    
                }

            }

            $response_array = array('success' => true, 'data' => $results, 'title'=>$request->key);
        }

        $response = response()->json($response_array, 200);

        return $response;

    }

    /**
     * Function Name : api_search_video()
     *
     * To search videos based on title
     *
     * @param object $request - Title of the video (For Mobile Usage)
     *
     * @return response of the array 
     */
    public function api_search_video(Request $request) {

        $validator = Validator::make(
            $request->all(),
            array(
                'key' => '',
                'sub_profile_id'=>'required|exists:sub_profiles,id',
            ),
            array(
                'exists' => 'The :attribute doesn\'t exists',
            )
        );

        if ($validator->fails()) {

            $error_messages = implode(',', $validator->messages()->all());

            $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages'=>$error_messages);

        } else {

            $subProfile = SubProfile::where('user_id', $request->id)
                        ->where('id', $request->sub_profile_id)->first();

            if (!$subProfile) {

                return response()->json(['success'=>false, 'error_messages'=>tr('sub_profile_details_not_found')]);
                
            }

            if($request->key) {
                $query = AdminVideo::where('is_approved' , 1)
                    ->where('title', 'like', '%' . $request->key . '%')
                    ->whereNotIn('admin_videos.is_banner',[1])
                    ->where('status' , 1)->orderBy('created_at' , 'desc');

            } else {

                $query = AdminVideo::where('is_approved' , 1)
                ->where('title', 'like', '%' . $request->key . '%')
                ->whereNotIn('admin_videos.is_banner',[1])
                ->where('status' , 1)->orderBy('created_at' , 'desc')->skip(0)->take(6);
            }
                
            if ($request->sub_profile_id) {

                
                $flagVideos = getFlagVideos($request->sub_profile_id);

                if($flagVideos) {

                    $query->whereNotIn('admin_videos.id', $flagVideos);
                }

            }

            $videos = $query->get();

            $results = [];

            if (!empty($videos) && $videos != null) {

                foreach ($videos as $key => $value) {

                    $results[] = displayFullDetails($value->id, $request->sub_profile_id);

                }
            }

            $response_array = array('success' => true, 'data' => $results, 'title'=>$request->key);
        }

        $response = response()->json($response_array, 200);

        return $response;

    }

    /**
     * Function Name : Privacy
     * 
     * To display privacy & Policy of the site (Static page)
     *
     * @param object $request - As of now no attributes
     *
     * @return content,title and type of the page
     */
    public function privacy(Request $request) {

        $page_data['type'] = $page_data['heading'] = $page_data['content'] = "";

        $page = Page::where('type', 'privacy')->first();

        if($page) {

            $page_data['type'] = "privacy";
            $page_data['heading'] = $page->heading;
            $page_data ['content'] = $page->description;
        }

        $response_array = array('success' => true , 'page' => $page_data);

        return response()->json($response_array,200);

    }

    /**
     * Function Name : about()
     * 
     * To display about us page of the site (Static page)
     *
     * @param object $request - As of now no attributes
     *
     * @return content,title and type of the page
     */
    public function about(Request $request) {

        $page_data['type'] = $page_data['heading'] = $page_data['content'] = "";

        $page = Page::where('type', 'about')->first();

        if($page) {

            $page_data['type'] = 'about';

            $page_data['heading'] = $page->heading;

            $page_data ['content'] = $page->description;
        }

        $response_array = array('success' => true , 'page' => $page_data);

        return response()->json($response_array,200);

    }

    /**
     * Function Name : terms()
     * 
     * To display terms & condiitions of the site (Static page)
     *
     * @param object $request - As of now no attributes
     *
     * @return content,title and type of the page
     */
    public function terms(Request $request) {

        $page_data['type'] = $page_data['heading'] = $page_data['content'] = "";

        $page = Page::where('type', 'terms')->first();

        if($page) {

            $page_data['type'] = "Terms";

            $page_data['heading'] = $page->heading;

            $page_data ['content'] = $page->description;
        }

        $response_array = array('success' => true , 'page' => $page_data);

        return response()->json($response_array,200);

    }

    /**
     * Function Name : settings()
     * 
     * To enable/disable the push notification in mobile
     *
     * @param object $request - push notification status
     *
     * @return boolean 
     */
    public function settings(Request $request) {

        $validator = Validator::make(
            $request->all(),
            array(
                'status' => 'required',
            )
        );

        if ($validator->fails()) {

            $error_messages = implode(',', $validator->messages()->all());

            $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages'=>$error_messages);

        } else {

            $user = User::find($request->id);

            $user->push_status = $request->status;

            $user->save();

            if($request->status) {

                $message = tr('push_notification_enable');

            } else {

                $message = tr('push_notification_disable');

            }

            $response_array = array('success' => true, 'message' => $message , 'push_status' => $user->push_status);
        }

        $response = response()->json($response_array, 200);

        return $response;
    }

    /**
     * Function Name ; keyBasedDetails()
     * 
     * To get videos based on the key like wishlist, recent uploads, recommended & history (mobile Usage)
     *
     * @param object $request - key, skip, take and etc
     *
     * @return array of videos
     */
    public function keyBasedDetails(Request $request) {

        $validator = Validator::make(
            $request->all(),
            array(
                'key'=>'required',
                'skip' => 'required|numeric',
                'take'=> $request->has('take') ? 'required|numeric' : 'numeric',
            )
        );

        if ($validator->fails()) {

            $error_messages = implode(',', $validator->messages()->all());

            return response()->json(['success'=>false, 'error_messages'=>$error_messages]);
            
        } else {

            if (!$request->has('take')) {

                $request->request->add(['take' => Setting::get('admin_take_count')]);

            }

            if (!$request->has('sub_profile_id')) {

                $sub_profile = SubProfile::where('user_id', $request->id)->where('status', DEFAULT_TRUE)->first();

                if ($sub_profile) {

                    $request->request->add([ 

                        'sub_profile_id' => $sub_profile->id,

                    ]);

                    $id = $sub_profile->id;

                } else {

                    $response_array = ['success'=>false, 'error_messages'=>tr('sub_profile_details_not_found')];

                    return response()->json($response_array , 200);

                }

            } else {

                $subProfile = SubProfile::where('user_id', $request->id)
                            ->where('id', $request->sub_profile_id)->first();

                if (!$subProfile) {

                    $response_array = ['success'=>false, 'error_messages'=>tr('sub_profile_details_not_found')];
                    
                    return response()->json($response_array , 200);
                    
                } else {

                    $id = $subProfile->id;

                }

            } 

            switch ($request->key) {

                case WISHLIST:

                    $model = Helper::wishlist($request->sub_profile_id,NULL,$request->skip, $request->take);

                    $videos = [];

                    foreach ($model as $key => $data) {                    

                        $videos[] = displayFullDetails($data->admin_video_id, $request->sub_profile_id);;

                    }

                    $title = tr('mobile_wishlist_heading');

                    break;

                case CONTINUE_WATCHING:

                    $model = Helper::continue_watching_videos($request->sub_profile_id,$request->device_type,$request->skip);

                    $videos = [];

                    foreach ($model as $key => $value) {
                         
                            $videos[] = displayFullDetails($value->admin_video_id, $request->sub_profile_id);

                    }

                    $title = tr('continue_watching_videos');

                    break;

                case TRENDING:

                    $model = Helper::trending(NULL, $request->skip, $request->take, $request->sub_profile_id);

                    $videos = [];

                    foreach ($model as $key => $data) {                    

                        $videos[] = displayFullDetails($data->admin_video_id, $request->sub_profile_id);

                    }

                    $title = tr('mobile_trending_heading');

                    break;

                case RECENTLY_ADDED:

                    $model = Helper::recently_added(NULL, $request->skip, $request->take, $request->sub_profile_id);

                    $videos = [];

                    foreach ($model as $key => $data) {                    

                        $videos[] = displayFullDetails($data->admin_video_id, $request->sub_profile_id);;

                    }

                    $title = tr('mobile_recent_upload_heading');

                    break;

                case WATCHLIST:

                    $model = Helper::watch_list($request->sub_profile_id, NULL, $request->skip, $request->take);

                    $videos = [];

                    foreach ($model as $key => $data) {                    

                        $videos[] = displayFullDetails($data->admin_video_id, $request->sub_profile_id);

                    }

                    $title = tr('mobile_watch_again_heading');

                    break;

                case SUGGESTIONS:

                    $model = Helper::suggestion_videos(NULL, $request->skip, null, $request->sub_profile_id);

                    $videos = [];

                    foreach ($model as $key => $data) {                    

                        $videos[] = displayFullDetails($data->admin_video_id, $request->sub_profile_id);

                    }

                    $title = tr('mobile_suggestion_heading');

                    break;


                default:

                    $videos = [];

                    $title = "";

                    if (is_numeric($request->key)){

                       $sub_videos = sub_category_videos($request->key, null, $request->skip, $request->take);

                       foreach ($sub_videos as $key => $val) {

                            $video_detail = '';

                            $videos[] = $video_detail = displayFullDetails($val->admin_video_id, $request->sub_profile_id);


                            if(empty($title)) {

                                if($video_detail) {

                                    $title = $video_detail['sub_category_name'];
                                    
                                }
                            }

                        }

                    }
                    
                    break;
            }


            $response_array = ['title'=> $title,'data'=>$videos, 'success'=>true];

            $response = response()->json($response_array, 200);

        }

        return $response;

    }

    /**
     * Function Name ; details()
     * 
     * To get videos based on the key like wishlist, recent uploads, recommended & history (Web Usage)
     *
     * @param object $request - key, skip, take and etc
     *
     * @return array of videos
     */
    public function details(Request $request) {

        $validator = Validator::make(
            $request->all(),
            array(
                'key'=>'required',
                'skip' => 'required|numeric',
                'take'=> $request->has('take') ? 'required|numeric' : 'numeric',
            )
        );

        if ($validator->fails()) {

            $error_messages = implode(',', $validator->messages()->all());

            return response()->json(['success'=>false, 'error_messages'=>$error_messages]);
            
        } else {

            if (!$request->has('take')) {

                $request->request->add(['take' => Setting::get('admin_take_count')]);

            }

            if (!$request->has('sub_profile_id')) {

                $sub_profile = SubProfile::where('user_id', $request->id)->where('status', DEFAULT_TRUE)->first();

                if ($sub_profile) {

                    $request->request->add([ 

                        'sub_profile_id' => $sub_profile->id,

                    ]);

                    $id = $sub_profile->id;

                } else {

                    $response_array = ['success'=>false, 'error_messages'=>tr('sub_profile_details_not_found')];


                    return response()->json($response_array , 200);

                }

            } else {

                $subProfile = SubProfile::where('user_id', $request->id)
                            ->where('id', $request->sub_profile_id)->first();

                if (!$subProfile) {

                    $response_array = ['success'=>false, 'error_messages'=>tr('sub_profile_details_not_found')];
                    
                    return response()->json($response_array , 200);
                    
                } else {

                    $id = $subProfile->id;

                }

            } 

            switch ($request->key) {

                case WISHLIST:

                    $model = Helper::wishlist($request->sub_profile_id,NULL,$request->skip, $request->take);

                    $chunk = $model->chunk(4);

                    $videos = [];

                    foreach ($chunk as $key => $value) {

                        $group = [];

                        foreach ($value as $key => $data) {
                         
                            $group[] = displayFullDetails($data->admin_video_id, $request->sub_profile_id);

                        }

                        $videos[] = $group;

                    }


                    $title = tr('mobile_wishlist_heading');

                    break;


                case TRENDING:

                    $model = Helper::trending(NULL, $request->skip, $request->take, $request->sub_profile_id);

                    $chunk = $model->chunk(4);

                    $videos = [];

                    foreach ($chunk as $key => $value) {

                        $group = [];

                        foreach ($value as $key => $data) {
                         
                            $group[] = displayFullDetails($data->admin_video_id, $request->sub_profile_id);

                        }

                        $videos[] = $group;

                    }

                    $title = tr('mobile_trending_heading');

                    break;

                case RECENTLY_ADDED:

                    $model = Helper::recently_added(NULL, $request->skip, $request->take, $request->sub_profile_id);

                    $chunk = $model->chunk(4);

                    $videos = [];

                    foreach ($chunk as $key => $value) {

                        $group = [];

                        foreach ($value as $key => $data) {
                         
                            $group[] = displayFullDetails($data->admin_video_id, $request->sub_profile_id);

                        }

                        $videos[] = $group;

                    }

                    $title = tr('mobile_recent_upload_heading');

                    break;

                case WATCHLIST:

                    $model = Helper::watch_list($request->sub_profile_id, NULL, $request->skip, $request->take);

                    $chunk = $model->chunk(4);

                    $videos = [];

                    foreach ($chunk as $key => $value) {

                        $group = [];

                        foreach ($value as $key => $data) {
                         
                            $group[] = displayFullDetails($data->admin_video_id, $request->sub_profile_id);

                        }

                        $videos[] = $group;

                    }

                    $title = tr('mobile_watch_again_heading');

                    break;

                case SUGGESTIONS:

                    $model = Helper::suggestion_videos(NULL, $request->skip, null, $request->sub_profile_id);

                    $chunk = $model->chunk(4);

                    $videos = [];

                    foreach ($chunk as $key => $value) {

                        $group = [];

                        foreach ($value as $key => $data) {
                         
                            $group[] = displayFullDetails($data->admin_video_id, $request->sub_profile_id);

                        }

                        $videos[] = $group;

                    }

                    $title = tr('mobile_suggestion_heading');

                    break;
                
                case CONTINUE_WATCHING:

                    $model = Helper::continue_watching_videos($request->sub_profile_id,$request->device_type,$request->skip);

                    $chunk = $model->chunk(4);

                    $videos = [];

                    foreach ($chunk as $key => $value) {

                        $group = [];

                        foreach ($value as $key => $data) {
                         
                            $group[] = displayFullDetails($data->admin_video_id, $request->sub_profile_id);

                        }

                        $videos[] = $group;

                    }


                    $title = tr('continue_watching_videos');

                    break;

                default:

                    $videos = [];

                    $title = "";

                    if (is_numeric($request->key)){

                       $sub_videos = sub_category_videos($request->key, null, $request->skip, $request->take, $request->sub_profile_id);

                       // dd(count($sub_videos));

                       $chunk = $sub_videos->chunk(4);

                        foreach ($chunk as $key => $val) {

                            $group = [];

                            $video_detail = '';

                            foreach ($val as $key => $data) {
                             
                                $group[] = $video_detail = displayFullDetails($data->admin_video_id, $request->sub_profile_id);

                            }

                            if(empty($title)) {

                                if($video_detail) {


                                    $title = $video_detail['sub_category_name'];
                                    
                                }
                            }

                            $videos[] = $group;

                        }

                    }
                    
                    break;
            }


            $response_array = ['title'=> $title,'data'=>$videos, 'success'=>true];

            $response = response()->json($response_array, 200);
        }

        return $response;

    }


    /**
     * Function Name ; browse()
     * 
     * Based on category id get all the sub category videos
     *
     * @param object $request - key, skip, take and etc
     *
     * @return array of videos
     */
    public function browse(Request $request) {

        $validator = Validator::make(
            $request->all(),
            array(
                'key'=>'required'
            )
        );

        if ($validator->fails()) {

            $error_messages = implode(',', $validator->messages()->all());

            return response()->json(['success'=>false, 'error_messages'=>$error_messages]);
            
        } else {

            $title = '';

            $videos = [];

            if ($request->key) {

                $videos = [];

                $catgory = Category::find($request->key);

                $sub_categories = get_sub_categories($request->key);

                $sub_category = [];

                foreach ($sub_categories as $key => $value) {

                   $sub_videos = sub_category_videos($value->id, WEB, 0 , 0 ,$request->sub_profile_id);

                   $chunk = $sub_videos->chunk(4);

                   $vid = [];

                    foreach ($chunk as $key => $val) {

                        $group = [];

                        foreach ($val as $key => $data) {
                         
                            $group[] = displayFullDetails($data->admin_video_id, $request->sub_profile_id);

                        }

                        $vid[] = $group;

                    }

                   $videos[$value->name] = $vid;

                   $sub_category[$value->name] = $value->id;


                }   

                $title = $catgory->name;

            } 

            $response_array = ['title'=> $title,'data'=>$videos, 'success'=>true, 'sub_category'=>$sub_category];

            $response = response()->json($response_array, 200);

        }

        return $response;

    }


    /**
     * Function Name ; getCategories()
     * 
     * Get categories and split into chunks (6)
     *
     * @param object $request - As of now no attribute
     *
     * @return array of array category
     */
    public function getCategories(Request $request) {

        $categories = Category::where('categories.is_approved' , 1)
                    ->select('categories.id as id' , 'categories.name' , 'categories.picture' ,
                        'categories.is_series' ,'categories.status' , 'categories.is_approved')
                    ->leftJoin('admin_videos' , 'categories.id' , '=' , 'admin_videos.category_id')
                    ->where('admin_videos.status' , 1)
                    ->where('admin_videos.is_approved' , 1)
                    ->groupBy('admin_videos.category_id')
                    ->havingRaw("COUNT(admin_videos.id) > 0")
                    ->orderBy('name' , 'ASC')
                    ->get();

        if ($categories != null && !empty($categories)) {

            $model = ['success'=>true, 'data'=>$categories->chunk(6)];

        } else {

            $model = ['success'=>true, 'data'=>[]];

        }

        return response()->json($model);
    }

    /**
     * Function Name : activeProfiles()
     * 
     * Based on user_id get all the sub profiles and Based on sub profile id get individual sub profile id
     *
     * @param object $request - User ID, Sub Profile ID
     *
     * @return array of sub profiles / Single object sub profile
     */
    public function activeProfiles(Request $request) {

        $query = SubProfile::where('user_id', $request->id);

        if($request->sub_profile_id) {

            $query->whereNotIn('id', [$request->sub_profile_id]);

        }

        $model = $query->get();

        if ($model) {

            $no_of_account = $this->last_subscription($request)->getData();

            if ($no_of_account->data > count($model)) {

                if($request->device_type == DEVICE_ANDROID || $request->device_type == DEVICE_IOS) {

                    $items = [];

                    $plus = ['id'=>'add', 'picture'=>asset('images/plus.png'), "name"=>"Add Profile",'status'=>DEFAULT_FALSE];

                    foreach ($model as $key => $value) {
                       
                        array_push($items, $value);

                    }

                    array_push($items, $plus);

                    $model = $items;

                }

            }

            $response = ['success'=>true, 'data'=>$model];

        } else {

             $response = ['success'=>false, 'error_messages'=>tr('sub_profile_details_not_found')];

        }

         return response()->json($response);

    }

    /**
     * Function Name : last_subscription()
     * 
     * User Last subscription payment status
     *
     * @param object $request - User ID
     *
     * @return response of subscription object or empty object
     */
    public function last_subscription(Request $request) {

        $model = UserPayment::where('user_id', $request->id)->orderBy('created_at', 'desc')->first();

        $response = ['success'=>true, 'data'=>($model) ? ($model->subscription ? $model->subscription->no_of_account : 1) : 1];

        return response()->json($response);

    }
 
    /**
     * Function Name : addProfile()
     * 
     * Based on logged in user & Based on subscription user can add sub profile
     *
     * @param object $request - User ID
     *
     * @return response of subscription object or empty object
     */
    public function addProfile(Request $request) {

       try {

            DB::beginTransaction();

            $validator = Validator::make(
                $request->all(),
                array(
                    'id' => 'required',
                    'name'=>'required',
                    'picture' => 'mimes:jpeg,jpg,bmp,png',
                )
            );

            if ($validator->fails()) {

                $error_messages = implode(',', $validator->messages()->all());

                $response = ['success'=>false, 'message'=>$error_messages];

                throw new Exception($error_messages);

            } else {

                $UserPayment = UserPayment::where('user_id', $request->id)
                            ->orderBy('created_at', 'desc')
                            ->where('status', DEFAULT_TRUE)
                            ->first();

                if ($UserPayment) {

                    if ($UserPayment->subscription) {

                        if ($UserPayment->subscription->no_of_account <= ($UserPayment->user ? count($UserPayment->user->subProfile) : 0)) {

                            $response = ['success'=>false, 'error_messages'=>tr('account_exists')];

                            throw new Exception(tr('account_exists'));

                        }

                    }

                }

                $model = new SubProfile;

                $model->user_id = $request->id;

                $model->name = $request->name;

                $model->picture = asset('placeholder.png');

                if($request->hasFile('picture')) {

                    $model->picture = Helper::normal_upload_picture($request->file('picture'));

                } 

                $model->status = DEFAULT_FALSE;

                if($model->save()) {

                    $user = User::find($request->id);

                    $user->no_of_account += 1;

                    $user->save();

                } else {

                    throw new Exception(tr('sub_profile_not_save'));
                    
                }

                $response = ['success'=>true, 'data'=>$model];
            }

            DB::commit();

            return response()->json($response);

        } catch (Exception $e) {

            DB::rollback();

            $e = $e->getMessage();

            $response_array = ['success'=>false, 'error_messages'=>$e];

            return response()->json($response_array);

        }

    }

    /**
     * Function Name : edit_sub_profile()
     * 
     * Based on logged in user , Edit sub profiles using sub profile id
     *
     * @param object $request - User ID, name & sub profile id
     *
     * @return response of sub profile
     */
    public function edit_sub_profile(Request $request) {

        try {

            DB::beginTransaction();

            $validator = Validator::make(
                $request->all(),
                array(
                    'id' => 'required',
                    'name'=>'required',
                    'sub_profile_id'=>'required',
                    'picture' => 'mimes:jpeg,jpg,bmp,png',
                )
            );

            if ($validator->fails()) {

                $error_messages = implode(',', $validator->messages()->all());

                $response = ['success'=>false, 'message'=>$error_messages];

                throw new Exception($error_messages);

            } else {

                $model = SubProfile::find($request->sub_profile_id);

                $model->name = $request->name;

                if($request->hasFile('picture')) {

                    Helper::delete_picture($model->picture, "/uploads/"); // Delete the old pic

                    $model->picture = Helper::normal_upload_picture($request->file('picture'));
                } 

                //$model->status = DEF;

                $model->save();

                $response = ['success'=>true, 'data'=>$model];
            }

            DB::commit();

            return response()->json($response);

        } catch (Exception $e) {

            DB::rollback();

            $e = $e->getMessage();

            $response_array = ['success'=>false, 'error_messages'=>$e];

            return response()->json($response_array);

        }

    }


    /**
     * Function Name : view_sub_profile()
     * 
     * Based on logged in user , View sub profiles using sub profile id
     *
     * @param object $request - Sub profile id
     *
     * @return response of sub profile
     */
    public function view_sub_profile(Request $request) {

        $validator = Validator::make(
            $request->all(),
            array(
                'sub_profile_id'=>'required'
            )
        );

        if ($validator->fails()) {

            $error_messages = implode(',', $validator->messages()->all());

            $response = ['success'=>false, 'message'=>$error_messages];

        } else {

            $model = SubProfile::find($request->sub_profile_id);

            if ($model) {

                $response = ['success'=>true, 'data'=>$model];

            } else {

                $response = ['success'=>false, 'error_messages'=>tr('sub_profile_details_not_found')];

            }
 
        }

        return response()->json($response);

    }

    /**
     * Function Name : delete_sub_profile()
     * 
     * Based on logged in user , Delete sub profiles using sub profile id
     *
     * @param object $request - User Id , sub profile id
     *
     * @return response of boolean
     */
    public function delete_sub_profile(Request $request) {

        try {

            DB::beginTransaction();

            $user = User::find($request->id);

            if ($user) {

                if (count($user->subProfile) == 1) {

                    throw new Exception(tr('sub_profile_not_delete'));
                    
                }

            } else {


                throw new Exception(tr('no_user_detail_found'));
                
            }

            $validator = Validator::make(
                $request->all(),
                array(
                    'sub_profile_id'=>'required'
                )
            );

            if ($validator->fails()) {

                $error_messages = implode(',', $validator->messages()->all());


                throw new Exception($error_messages);

            } else {

                // This variable used to set next available sub profile as current profile

                $another_model_sub_profile_id = 0;

                $model = SubProfile::find($request->sub_profile_id);

                if ($model) {

                    $model_status = $model->status;

                    $model->delete();

                    $another_model = SubProfile::where('user_id', $request->id)->first();

                    if ($model_status == 1) {

                        if(count($another_model) > 0) {

                            $another_model->status = DEFAULT_TRUE;

                            $another_model->save();

                        }

                    }

                    $another_model_sub_profile_id = $another_model->id;
                    
                    $user->no_of_account -= 1;

                    if ($user->save()) {

                        $logged = UserLoggedDevice::where('user_id', $request->id)->first();

                        if ($logged) {

                            if ($logged->delete()) {

                                $user->logged_in_account -= 1;

                                $user->save();

                                $response = ['success'=>true, 'data' => $user ,  'sub_profile_id' => $another_model_sub_profile_id];

                            } else {

                                throw new Exception(tr('logged_in_device_not_delete'));
                                
                            }

                        } else {

                            $response = ['success'=>true , 'another_model_sub_profile_id' => $another_model_sub_profile_id];

                        }


                    } else {

                        throw new Exception(tr('user_details_not_save'));
                        
                    }

                } else {

                    throw new Exception(tr('sub_profile_details_not_found'));
                    
                }
     
            }

            DB::commit();

            return response()->json($response);

        } catch(Exception $e) {

            DB::rollback();

            $e = $e->getMessage();

            $response_array = ['success'=>false, 'error_messages'=>$e];

            return response()->json($response_array);

        }

    }


    /**
     * Function Name : active_plan()
     * 
     * Based on logged in user , Get Active plan 
     *
     * @param object $request - User Id 
     *
     * @return response of boolean with subscription details
     */
    public function active_plan(Request $request){

        $model = UserPayment::where('user_id', $request->id)->where('status', DEFAULT_TRUE)
                ->orderBy('created_at', 'desc')->first();

        if ($model) {

            $model->expiry_date = date('d-m-Y h:i A', strtotime($model->expiry_date));

            $response = ['success'=>true, 'data'=>$model, 'subscription' => $model->subscription];

        } else {

            $response = ['success'=>false, 'error_messages'=>tr('user_payment_not_found')];

        }

        return response()->json($response);

    }

    /**
     * Function Name : subscribed_plans()
     * 
     * Based on logged in user , get his/her subscribed plans
     *
     * @param object $request - User Id 
     *
     * @return response of boolean with plan details
     */
    public function subscribed_plans(Request $request){

        $model = UserPayment::where('user_id', $request->id)
                    ->where('status', DEFAULT_TRUE)
                    ->orderBy('created_at', 'desc')
                    ->get();

        $user = User::find($request->id);

        $plans = [];

        $amount = 0;

        if ($user) {

            if (!empty($model) && $model != null) {

                foreach ($model as $key => $value) {

                    $amount += $value->amount;
                    
                    $plans[] = [

                        'payment_id'=>$value->payment_id,

                        'plan_name'=>$value->subscription ? $value->subscription->title : '',

                        'no_of_month'=>$value->subscription ? $value->subscription->plan : '',

                        'no_of_account'=>$value->subscription ? $value->subscription->no_of_account : '',

                        'amount'=> $value->amount,

                        'expiry_date'=>date('d-m-Y h:i A', strtotime($value->expiry_date)),

                        'date'=>convertTimeToUSERzone($value->created_at, $user->timezone, 'd-m-Y'),

                        'payment_mode'=>$value->payment_mode

                    ];

                }

            }

            $response = ['success'=>true, 'plans'=>$plans, 'amount'=>$amount];

        } else {

            $response = ['success'=>false, 'error_messages'=>tr('no_user_detail_found')];

        }

        return response()->json($response);

    }

    /**
     * Function Name : subscription_index()
     * 
     * Get all subscription plans
     *
     * @return response of plan details
     */
    public function subscription_index(Request $request) {
        
        $query = Subscription::where('status' , 1);

         if ($request->id) {

            $user = User::find($request->id);

            if ($user) {

               if ($user->one_time_subscription == DEFAULT_TRUE) {

                   $query->where('amount','>', 0);

               }

            } 

        }

        $model = $query->get();

        $model = (!empty($model) && $model != null) ? $model : [];

        $response_array = ['success'=>true, 'data'=>$model];

        return response()->json($response_array,200);
    }


    /**
     * Function Name : zero_plan()
     * 
     * Save zero plan details based on logged in user (Only one time he can avail)
     *
     * @param object $request - plan id, user iid and etc
     *
     * @return response of plan details
     */
    public function zero_plan(Request $request) {

        try {

            DB::beginTransaction();

            $validator = Validator::make(
                $request->all(),
                array(
                    'plan_id'=>'required:exists:subscriptions,id'
                ),
                 array(
                    'exists' => 'The :attribute doesn\'t exists',
                )
            );

            if ($validator->fails()) {

                $error_messages = implode(',', $validator->messages()->all());

                $response = ['success'=>false, 'message'=>$error_messages];

                throw new Exception($error_messages);

            } else {

                if ($request->plan_id) {

                    // Load model
                    $plan = Subscription::find($request->plan_id);

                    if ($plan->amount <= 0) {

                        // save video payment for onetime

                        $model = new UserPayment;

                        $previous_payment = UserPayment::where('user_id' , $request->user_id)->where('status', DEFAULT_TRUE)->orderBy('id', 'desc')->first();

                        if ($previous_payment) {

                            if (strtotime($previous_payment->expiry_date) >= strtotime(date('Y-m-d H:i:s'))) {

                             $model->expiry_date = date('Y-m-d H:i:s', strtotime("+{$plan->plan} months", strtotime($previous_payment->expiry_date)));

                            } else {

                                $model->expiry_date = date('Y-m-d H:i:s',strtotime("+{$plan->plan} months"));

                            }

                        } else {
                            $model->expiry_date = date('Y-m-d H:i:s',strtotime("+{$plan->plan} months"));
                        }

                    
                        $model->subscription_id = $request->plan_id;

                        $model->user_id = $request->id;

                        $model->payment_id = "Free Plan";

                        $model->amount = 0;

                        $model->status =  DEFAULT_TRUE;

                        $model->save();

                        if ($model) {

                            if ($model->user) {

                                $model->user->user_type = DEFAULT_TRUE;

                                $model->user->one_time_subscription = DEFAULT_TRUE;

                                $model->user->amount_paid += 0;

                                $model->user->expiry_date = $model->expiry_date;

                                $model->user->no_of_days = 0;

                                $model->user->save();

                                $user = User::find($request->id);

                                $response_array = ['success' => true , 'model' => $model, 'plan'=>$plan, 'user'=>['token'=>$user->token]];

                            } else {

                                throw new Exception(tr('no_user_detail_found'));
                                
                            }

                        } else {

                            $response_array = ['success' => false , 'error' => Helper::error_message(146) , 'error_code' => 146];

                            throw new Exception(Helper::error_message(146));

                        }

                    } else {

                        throw new Exception(tr('zero_plan_not_found'));
                        
                    }

                } else {

                    $response_array = ['success' => false , 'error' => Helper::error_message(146) , 'error_code' => 146];

                    throw new Exception(Helper::error_message(146), 146);

                }

            }

            DB::commit();

            return response()->json($response_array, 200);

        } catch (Exception $e) {

            DB::rollback();

            $e = $e->getMessage();

            $response_array = ['success'=>false, 'error_messages'=>$e];

            return response()->json($response_array);
        }

    }

    /**
     * Function Name : site_settings()
     * 
     * Get all the settings table values (Key & Value)
     *
     * @return response of settings details
     */
    public function site_settings() {

        $settings = Settings::get();

        return response()->json($settings, 200); 
    }

    /**
     * Function Name : allPages()
     * 
     * List out all the static pages like abotu us, terms , privacy & policy and etc
     *
     * @return response of page details with chunks
     */
    public function allPages() {

        $all_pages = Page::all();

        $chunks = $all_pages->chunk(4);

        $chunks->toArray();

        return response()->json($chunks, 200);

    }

    /**
     * Function Name : getPage()
     * 
     * Get Page Based on type
     * 
     * @param string $id - Page Id
     *
     * @return response of page details
     */
    public function getPage($id) {

        $page = Page::where('id', $id)->first();

        return response()->json($page, 200);

    }

    /**
     * Function Name : check_social()
     * 
     * Check whether the social login buttons need to display or not
     * 
     * @return response of page details
     */
    public function check_social() {

        $facebook_client_id = envfile('FB_CLIENT_ID');
        $facebook_client_secret = envfile('FB_CLIENT_SECRET');
        $facebook_call_back = envfile('FB_CALL_BACK');

        $google_client_id = envfile('GOOGLE_CLIENT_ID');
        $google_client_secret = envfile('GOOGLE_CLIENT_SECRET');
        $google_call_back = envfile('GOOGLE_CALL_BACK');

        $fb_status = false;

        if (!empty($facebook_client_id) && !empty($facebook_client_secret) && !empty($facebook_call_back)) {

            $fb_status = true;

        }

        $google_status = false;

        if (!empty($google_client_id) && !empty($google_client_secret) && !empty($google_call_back)) {

            $google_status = true;

        }

        return response()->json(['fb_status'=>$fb_status, 'google_status'=>$google_status]);
    }

    /**
     * Function Name : genre_video()
     * 
     * Get Genre Video Details based on genre id
     *
     * @param object $request - Genre id
     * 
     * @return response of Genre video details
     */
    public function genre_video(Request $request) {

        $model = Genre::find($request->genre_id);

        if ($model) {

            $ios_video = $model->video;

            if(check_valid_url($model->video)) {

                if(Setting::get('streaming_url'))
                    $model->video = Setting::get('streaming_url').get_video_end($model->video);

                if(Setting::get('HLS_STREAMING_URL'))
                    $ios_video = Setting::get('HLS_STREAMING_URL').get_video_end($model->video);
            }

            $response_array = ['success' => true , 'model' => $model, 'ios_video'=>$ios_video];

        } else {

            $response_array = ['success' => false , tr('genre_not_found')];
        }

        return response()->json($response_array);

    }

    /**
     * Function Name : genre_list()
     * 
     * Get Genre list based on genre id
     *
     * @param object $request - Genre id
     * 
     * @return response of Genre video details with html repsonse
     */
    public function genre_list(Request $request) {

        $seasons = AdminVideo::where('genre_id', $request->genre_id)
                            // ->whereNotIn('admin_videos.genre_id', [$video->id])
                            ->where('admin_videos.status' , 1)
                            ->where('admin_videos.is_approved' , 1)
                            ->orderBy('admin_videos.created_at', 'desc')
                            ->skip(0)
                            ->take(4)
                            ->get();
        $model = [];
        
        if(!empty($seasons) && $seasons != null) {

            foreach ($seasons as $key => $value) {

                $model[] = [
                        'title'=>$value->title,
                        'description'=>$value->description,
                        'ratings'=>$value->ratings,
                        'publish_time'=>date('F j y', strtotime($value->publish_time)),
                        'duration'=>$value->duration,
                        'watch_count'=>$value->watch_count,
                        'default_image'=>$value->default_image,
                        'admin_video_id'=>$value->id,
                    ];
            }
        }

        $view = \View::make('admin.seasons.season_videos')->with('model', $model)->render();

        $response_array = ['success' => true , 'data' => $model ? $view : tr('no_genre')];

        return response()->json($response_array);

    }

    /**
     * Function Name : searchAll()
     * 
     * Search videos based on title
     *
     * @param object $request - Term (Search key)
     * 
     * @return response of searched videos
     */
    public function searchAll(Request $request) {

        $validator = Validator::make(
            $request->all(),
            array(
                'id'=>'required',
                'term' => 'required',
              //  'sub_profile_id'=>'required',
            ),
            array(
                'exists' => 'The :attribute doesn\'t exists',
            )
        );
    
        if ($validator->fails()) {

            $error_messages = implode(',', $validator->messages()->all());

            $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages'=>$error_messages);

            return response()->json($response_array);
        
        } else {

            $q = $request->term;

            if (!$request->sub_profile_id) {

                $sub_profile = SubProfile::where('user_id', $request->id)->where('status', DEFAULT_TRUE)->first();

                if ($sub_profile) {

                    $request->request->add([ 

                        'sub_profile_id' => $sub_profile->id,

                    ]);

                    $id = $sub_profile->id;

                } else {

                    $response_array = ['success'=>false, 'error_messages'=>tr('sub_profile_details_not_found')];

                    return response()->json($response_array , 200);

                }
            } else {

                $id = $request->sub_profile_id;
            }


            \Session::set('user_search_key' , $q);

            $items = array();
            
            $results = Helper::search_video($q, 1, 0, $id);

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

    /**
     * Function Name : notifications()
     * 
     * Display New uploaded videos notification 
     *
     * @param object $request - user id
     * 
     * @return response of searched videos
     */
    public function notifications(Request $request) {

        $count = Notification::where('status', 0)->where('user_id', $request->id)->count();

        $model = Notification::where('notifications.user_id', $request->id)
                ->select('admin_videos.default_image', 'notifications.admin_video_id', 'admin_videos.title', 'notifications.updated_at', 'admin_videos.status', 'admin_videos.id')
                ->leftJoin('admin_videos', 'admin_videos.id', '=', 'notifications.admin_video_id')
                ->where('admin_videos.status', 1)
                ->skip(0)->take(4)
                ->orderBy('notifications.updated_at', 'desc')->get();

        $datas = [];

        $user = User::find($request->id);

        if (!empty($model) && $model != null) {

            foreach ($model as $key => $value) {
                
                $datas[] = ['admin_video_id'=>$value->admin_video_id, 
                            'img'=>$value->default_image, 
                            'title'=>$value->title, 
                            'time'=>$value->updated_at->diffForHumans(),
                            'pay_per_view_status'=>watchFullVideo($request->id, $user ? $user->user_type : '', $value->adminVideo)];

            }
        }

        $response_array = ['success'=>true, 'count'=>$count, 'data'=>$datas];

        return response()->json($response_array);

    }

    /**
     * Function Name : red_notifications()
     * 
     * Once click in bell all the notification status will change into read 
     *
     * @param object $request - As of no attribute
     * 
     * @return response of boolean
     */
    public function red_notifications(Request $request) {

        $model = Notification::where('status', 0)->get();

        foreach ($model as $key => $value) {

            $value->status = 1;

            $value->save();

        }

        return response()->json(true);

    }

    /**
     * Function Name : stripe_payment()
     * 
     * User pay the subscription plan amount through stripe payment
     *
     * @param object $request - User id, Subscription id
     * 
     * @return response of success/failure message
     */
    public function stripe_payment(Request $request) {

        try {

            DB::beginTransaction();

            $validator = Validator::make($request->all(), 
                array(
                    'subscription_id' => 'required|exists:subscriptions,id',
                    'coupon_code'=>'exists:coupons,coupon_code,status,1',
                    'total_amount'=>'numeric',

                ),  array(

                    'exists' => 'The :attribute doesn\'t exists',

                ));

            if($validator->fails()) {

                $error_messages = implode(',', $validator->messages()->all());

                throw new Exception($error_messages, 101);

            } else {

                $subscription = Subscription::find($request->subscription_id);

                if ($subscription) {

                    $total = $subscription->amount;

                    $user = User::find($request->id);

                    if ($user) {

                        $check_card_exists = User::where('users.id' , $request->id)
                                        ->leftJoin('cards' , 'users.id','=','cards.user_id')
                                        ->where('cards.id' , $user->card_id)
                                        ->where('cards.is_default' , DEFAULT_TRUE);

                        if($check_card_exists->count() != 0) {

                            $user_card = $check_card_exists->first();

                            if ($total <= 0) {

                                
                                $previous_payment = UserPayment::where('user_id' , $request->id)
                                            ->where('status', DEFAULT_TRUE)->orderBy('created_at', 'desc')->first();


                                $user_payment = new UserPayment;

                                if($previous_payment) {

                                    if (strtotime($previous_payment->expiry_date) >= strtotime(date('Y-m-d H:i:s'))) {

                                     $user_payment->expiry_date = date('Y-m-d H:i:s', strtotime("+{$subscription->plan} months", strtotime($previous_payment->expiry_date)));

                                    } else {

                                        $user_payment->expiry_date = date('Y-m-d H:i:s',strtotime("+{$subscription->plan} months"));

                                    }


                                } else {
                                   
                                    $user_payment->expiry_date = date('Y-m-d H:i:s',strtotime("+".$subscription->plan." months"));
                                }


                                $user_payment->payment_id = "free plan";

                                $user_payment->user_id = $request->id;

                                $user_payment->subscription_id = $request->subscription_id;

                                $user_payment->status = 1;

                                $user_payment->sub_amount = $total;

                                if($request->coupon_code){

                                    // Coupon code find the coupon detail
                                    $coupon_detail = Coupon::where('coupon_code',$request->coupon_code)->first();
                                   
                                    if($coupon_detail){

                                        $user_payment->coupon_amount = $coupon_detail->amount;

                                        $user_payment->coupon_code = $request->coupon_code;

                                        $user_payment->amount = $request->total_amount;
                                    }

                                }

                                $user_payment->payment_mode = CARD;

                                if ($user_payment->save()) {

                                    $user->one_time_subscription = 1;

                                    $user->user_type = 1;

                                    $user->save();
                                    
                                    $data = ['id' => $user->id , 'token' => $user->token, 'no_of_account'=>$subscription->no_of_account , 'payment_id' => $user_payment->payment_id];

                                    $response_array = ['success' => true, 'message'=>tr('payment_success') , 'data' => $data];

                                } else {

                                    throw new Exception(tr(Helper::get_error_message(902)), 902);

                                }


                            } else {

                                $stripe_secret_key = Setting::get('stripe_secret_key');

                                $customer_id = $user_card->customer_id;

                                if($stripe_secret_key) {

                                    \Stripe\Stripe::setApiKey($stripe_secret_key);

                                } else {

                                    throw new Exception(Helper::get_error_message(902), 902);

                                }

                                try{

                                   $user_charge =  \Stripe\Charge::create(array(
                                      "amount" => $total * 100,
                                      "currency" => "usd",
                                      "customer" => $customer_id,
                                    ));

                                   $payment_id = $user_charge->id;
                                   $amount = $user_charge->amount/100;
                                   $paid_status = $user_charge->paid;

                                    if($paid_status) {

                                        $previous_payment = UserPayment::where('user_id' , $request->id)
                                            ->where('status', DEFAULT_TRUE)->orderBy('created_at', 'desc')->first();

                                        $user_payment = new UserPayment;

                                        if($previous_payment) {

                                            $expiry_date = $previous_payment->expiry_date;
                                            $user_payment->expiry_date = date('Y-m-d H:i:s', strtotime($expiry_date. "+".$subscription->plan." months"));

                                        } else {
                                            
                                            $user_payment->expiry_date = date('Y-m-d H:i:s',strtotime("+".$subscription->plan." months"));
                                        }


                                        $user_payment->payment_id  = $payment_id;

                                        $user_payment->user_id = $request->id;

                                        $user_payment->subscription_id = $request->subscription_id;

                                        $user_payment->status = 1;

                                        $user_payment->sub_amount = $amount;

                                        if($request->coupon_code){

                                            // Coupon code find the coupon detail
                                            $coupon_detail = Coupon::where('coupon_code',$request->coupon_code)->first();
                                           
                                            if($coupon_detail){

                                                $user_payment->coupon_amount = $coupon_detail->amount;

                                                $user_payment->coupon_code = $request->coupon_code;

                                                $user_payment->amount = $request->total_amount;
                                            }
                                        }


                                        if ($user_payment->save()) {

                                            $user->user_type = 1;

                                            $user->save();
                                            
                                            $data = ['id' => $user->id , 'token' => $user->token, 'no_of_account'=>$subscription->no_of_account , 'payment_id' => $user_payment->payment_id];

                                            $response_array = ['success' => true, 'message'=>tr('payment_success') , 'data' => $data];

                                        } else {

                                             throw new Exception(tr(Helper::get_error_message(902)), 902);

                                        }


                                    } else {

                                        $response_array = array('success' => false, 'error_messages' => Helper::get_error_message(903) , 'error_code' => 903);

                                        throw new Exception(Helper::get_error_message(903), 903);

                                    }

                                
                                } catch (\Stripe\StripeInvalidRequestError $e) {

                                    Log::info(print_r($e,true));

                                    $response_array = array('success' => false , 'error_messages' => $e->getMessage() ,'error_code' => 903);

                                    return response()->json($response_array , 200);

                                }

                            }

                        } else {
     
                            throw new Exception(Helper::get_error_message(901), 901);
                            
                        }

                    } else {

                        throw new Exception(tr('no_user_detail_found'));
                        
                    }

                } else {

                    throw new Exception(Helper::get_error_message(901), 901);

                }         

                
            }

            DB::commit();

            return response()->json($response_array , 200);

        } catch (Exception $e) {

            DB::rollback();

            $error = $e->getMessage();

            $code = $e->getCode();

            $response_array = ['success'=>false, 'error_messages'=>$error, 'error_code'=>$code];

            return response()->json($response_array);
        }
    
    }

    /**
     * Function Name : ppv_end()
     * 
     * Once video end (complete) at the time this api will ping and change pay per view status as one. Status 1 - The user is completely watched the video
     *
     * @param object $request - Admin video id
     * 
     * @return response of success/failure message
     */
    public function ppv_end(Request $request) {

        $validator = Validator::make($request->all(), 
            array(
                'admin_video_id' => 'required|exists:admin_videos,id',
            ),  array(
                'exists' => 'The :attribute doesn\'t exists',
            ));

        if($validator->fails()) {

            $errors = implode(',', $validator->messages()->all());
            
            $response_array = ['success' => false, 'error_messages' => $errors, 'error_code' => 101];

            return response()->json($response_array);

        } else {

            // Load Payperview
            $payperview = PayPerView::where('user_id', $request->id)
                            ->where('video_id',$request->admin_video_id)
                            ->where('status',0)
                            ->orderBy('created_at', 'desc')
                            ->first();

            if ($payperview) {

                $payperview->status = DEFAULT_TRUE;

                $payperview->save();

            }

            $response_array = ['success'=>true];

            return response()->json($response_array);

        }

    }

    /**
     * Function Name : stripe_ppv()
     * 
     * Pay the payment for Pay per view through stripe
     *
     * @param object $request - Admin video id
     * 
     * @return response of success/failure message
     */
    public function stripe_ppv(Request $request) {

        try {

            DB::beginTransaction();

            $validator = Validator::make($request->all(), 
                array(
                    'admin_video_id' => 'required|exists:admin_videos,id',
                    'coupon_code'=>'exists:coupons,coupon_code,status,1',
                    'total_amount'=>'numeric',
                ),  array(
                    'exists' => 'The :attribute doesn\'t exists',
                ));

            if($validator->fails()) {

                $errors = implode(',', $validator->messages()->all());
                
                $response_array = ['success' => false, 'error_messages' => $errors, 'error_code' => 101];

                throw new Exception($errors);

            } else {

                $userModel = User::find($request->id);

                if ($userModel) {

                    if ($userModel->card_id) {

                        $user_card = Card::find($userModel->card_id);

                        if ($user_card && $user_card->is_default) {

                            $video = AdminVideo::find($request->admin_video_id);

                            if($video) {

                                $total = $video->amount;

                                if ($total <= 0) {

                                    $payment_id = FREE_PLAN;

                                    $user_payment = new PayPerView;
                                    $user_payment->payment_id  = FREE_PLAN;
                                    $user_payment->user_id = $request->id;
                                    $user_payment->video_id = $request->admin_video_id;
                                    $user_payment->status = DEFAULT_FALSE;
                                    $user_payment->ppv_amount = $total;

                                    if ($video->type_of_user == 1) {

                                        $user_payment->type_of_user = "Normal User";

                                    } else if($video->type_of_user == 2) {

                                        $user_payment->type_of_user = "Paid User";

                                    } else if($video->type_of_user == 3) {

                                        $user_payment->type_of_user = "Both Users";
                                    }


                                    if ($video->type_of_subscription == 1) {

                                        $user_payment->type_of_subscription = "One Time Payment";

                                    } else if($video->type_of_subscription == 2) {

                                        $user_payment->type_of_subscription = "Recurring Payment";

                                    }
                                    if($request->coupon_code){

                                        // Coupon code find the coupon detail
                                        $coupon_detail = Coupon::where('coupon_code',$request->coupon_code)->first();
                                       
                                        if($coupon_detail){

                                            $user_payment->coupon_amount = $coupon_detail->amount;

                                            $user_payment->coupon_code = $request->coupon_code;

                                            $user_payment->amount = $request->total_amount;
                                        }

                                    }

                                    $user_payment->payment_mode = CARD;

                                    $user_payment->save();

                                    // Commission Spilit 
                                    if(is_numeric($video->uploaded_by)) {

                                        if($video->amount > 0) { 

                                            // Do Commission spilit  and redeems for moderator

                                            Log::info("ppv_commission_spilit started");

                                            PaymentRepo::ppv_commission_split($video->id , $user_payment->id , $video->uploaded_by);

                                            Log::info("ppv_commission_spilit END"); 
                                            
                                        }

                                        

                                        \Log::info("ADD History - add_to_redeem");

                                    } 


                                    $data = ['id'=> $request->id, 'token'=> $userModel->token , 'payment_id' => $payment_id];

                                    $response_array = array('success' => true, 'message'=>tr('payment_success'),'data'=> $data);

                                } else {

                                    // Get the key from settings table

                                    $stripe_secret_key = Setting::get('stripe_secret_key');

                                    $customer_id = $user_card->customer_id;
                                    
                                    if($stripe_secret_key) {

                                        \Stripe\Stripe::setApiKey($stripe_secret_key);

                                    } else {

                                        $response_array = array('success' => false, 'error_messages' => Helper::get_error_message(902) , 'error_code' => 902);

                                        throw new Exception(Helper::get_error_message(902));
                                        
                                    }

                                    try {

                                       $user_charge =  \Stripe\Charge::create(array(
                                          "amount" => $total * 100,
                                          "currency" => "usd",
                                          "customer" => $customer_id,
                                        ));

                                       $payment_id = $user_charge->id;
                                       $amount = $user_charge->amount/100;
                                       $paid_status = $user_charge->paid;
                                       
                                       if($paid_status) {

                                            $user_payment = new PayPerView;
                                            $user_payment->payment_id  = $payment_id;
                                            $user_payment->user_id = $request->id;
                                            $user_payment->video_id = $request->admin_video_id;
                                            $user_payment->status = DEFAULT_FALSE;
                                            $user_payment->ppv_amount = $amount;

                                            if ($video->type_of_user == 1) {

                                                $user_payment->type_of_user = "Normal User";

                                            } else if($video->type_of_user == 2) {

                                                $user_payment->type_of_user = "Paid User";

                                            } else if($video->type_of_user == 3) {

                                                $user_payment->type_of_user = "Both Users";
                                            }


                                            if ($video->type_of_subscription == 1) {

                                                $user_payment->type_of_subscription = "One Time Payment";

                                            } else if($video->type_of_subscription == 2) {

                                                $user_payment->type_of_subscription = "Recurring Payment";

                                            }

                                            if($request->coupon_code){

                                                // Coupon code find the coupon detail
                                                $coupon_detail = Coupon::where('coupon_code',$request->coupon_code)->first();
                                               
                                                if($coupon_detail){

                                                    $user_payment->coupon_amount = $coupon_detail->amount;

                                                    $user_payment->coupon_code = $request->coupon_code;

                                                    $user_payment->amount = $request->total_amount;
                                                }
                                            }

                                            $user_payment->save();

                                            // Commission Spilit 

                                            if(is_numeric($video->uploaded_by)) {

                                                if($video->amount > 0) { 

                                                    // Do Commission spilit  and redeems for moderator

                                                    Log::info("ppv_commission_spilit started");

                                                    PaymentRepo::ppv_commission_split($video->id , $user_payment->id , $video->uploaded_by);

                                                    Log::info("ppv_commission_spilit END");
                                                    
                                                }

                                        
                                                \Log::info("ADD History - add_to_redeem");

                                            } 

                                            $data = ['id'=> $request->id, 'token'=> $userModel->token , 'payment_id' => $payment_id];

                                            $response_array = array('success' => true, 'message'=>tr('payment_success'),'data'=> $data);

                                        } else {

                                            $response_array = array('success' => false, 'error_messages' => Helper::get_error_message(902) , 'error_code' => 902);

                                            throw new Exception(tr('no_video_found'));

                                        }
                                    
                                    } catch (\Stripe\StripeInvalidRequestError $e) {

                                        Log::info(print_r($e,true));

                                        $response_array = array('success' => false , 'error_messages' => $e->getMessage() ,'error_code' => 903);

                                       return response()->json($response_array , 200);
                                    
                                    }

                                }

                            
                            } else {

                                $response_array = array('success' => false , 'error_messages' => tr('no_video_found'));

                                throw new Exception(tr('no_video_found'));
                                
                            }

                        } else {

                            $response_array = array('success' => false , 'error_messages' => tr('no_default_card_available'));

                            throw new Exception(tr('no_default_card_available'));

                        }

                    } else {

                        $response_array = array('success' => false , 'error_messages' => tr('no_default_card_available'));

                        throw new Exception(tr('no_default_card_available'));

                    }

                } else {

                    throw new Exception(tr('no_user_detail_found'));
                    

                }

            }

            DB::commit();

            return response()->json($response_array,200);

        } catch (Exception $e) {

            DB::rollback();

            $e = $e->getMessage();

            $response_array = ['success'=>false, 'error_messages'=>$e];

            return response()->json($response_array);

        }
        
    }

    /**
     * Function Name : paypal_ppv()
     * 
     * Pay the payment for Pay per view through paypal
     *
     * @param object $request - Admin video id
     * 
     * @return response of success/failure message
     */
    public function paypal_ppv(Request $request) {

        try {

            DB::beginTransaction();

            $validator = Validator::make(
                $request->all(),
                array(
                    'admin_video_id'=>'required|exists:admin_videos,id',
                    'payment_id'=>'required',
                    'coupon_code'=>'exists:coupons,coupon_code,status,1',
                    'total_amount'=>'numeric',

                ),  array(
                    'exists' => 'The :attribute doesn\'t exists',
                ));

            if ($validator->fails()) {
                // Error messages added in response for debugging
                $errors = implode(',',$validator->messages()->all());

                $response_array = ['success' => false,'error_messages' => $errors,'error_code' => 101];

                throw new Exception($errors);

            } else {

                $video = AdminVideo::find($request->admin_video_id);

                $user_payment = new PayPerView;
                
                $user_payment->payment_id  = $request->payment_id;

                $user_payment->user_id = $request->id;

                $user_payment->video_id = $request->admin_video_id;

                $user_payment->status = DEFAULT_FALSE;

                $user_payment->ppv_amount = $video->amount;

                $user_payment->payment_mode = PAYPAL;

                if ($video->type_of_user == 1) {

                    $user_payment->type_of_user = "Normal User";

                } else if($video->type_of_user == 2) {

                    $user_payment->type_of_user = "Paid User";

                } else if($video->type_of_user == 3) {

                    $user_payment->type_of_user = "Both Users";
                }


                if ($video->type_of_subscription == 1) {

                    $user_payment->type_of_subscription = "One Time Payment";

                } else if($video->type_of_subscription == 2) {

                    $user_payment->type_of_subscription = "Recurring Payment";

                }

                if($request->coupon_code){

                    // Coupon code find the coupon detail
                    $coupon_detail = Coupon::where('coupon_code',$request->coupon_code)->first();
                   
                    if($coupon_detail){

                        $user_payment->coupon_amount = $coupon_detail->amount;

                        $user_payment->coupon_code = $request->coupon_code;

                        $user_payment->amount = $request->total_amount;
                    }
                }
                
                $user_payment->save();

                if($user_payment) {

                    if(is_numeric($video->uploaded_by)) {

                        if($video->amount > 0) { 

                            // Do Commission spilit  and redeems for moderator

                            Log::info("ppv_commission_spilit started");

                            PaymentRepo::ppv_commission_split($video->id , $user_payment->id , $video->uploaded_by);

                            Log::info("ppv_commission_spilit END"); 
                            
                        }

                        \Log::info("ADD History - add_to_redeem");

                    } 

                } 

                $viewerModel = User::find($request->id);

                $response_array = ['success'=>true, 'message'=>tr('payment_success'), 
                                    'data'=>['id'=>$request->id,
                                     'token'=>$viewerModel ? $viewerModel->token : '']];

            }

            DB::commit();

            return response()->json($response_array, 200);

        } catch (Exception $e) {

            DB::rollback();

            $e = $e->getMessage();

            $response_array = ['success'=>false, 'error_messages'=>$e];

            return response()->json($response_array);
        }

    }

    /**
     * Function Name : card_details()
     * 
     * List of card details based on logged in user id
     *
     * @param object $request - user id
     * 
     * @return list of cards
     */
    public function card_details(Request $request) {

        $cards = Card::select('user_id as id','id as card_id','customer_id',
                'last_four', 'card_token', 'is_default', 
            \DB::raw('DATE_FORMAT(created_at , "%e %b %y") as created_date'))
            ->where('user_id', $request->id)->get();

        $cards = (!empty($cards) && $cards != null) ? $cards : [];

        $response_array = ['success'=>true, 'data'=>$cards];

        return response()->json($response_array, 200);
    }

    /**
     * Function Name : payment_card_add()
     * 
     * Add Payment card based on logged in user id
     *
     * @param object $request - user id
     * 
     * @return card details objet
     */
    public function payment_card_add(Request $request) {

        $validator = Validator::make($request->all(), 
            array(
                'number' => 'required|numeric',
                'card_token'=>'required',
            )
            );

        if($validator->fails()) {

            $errors = implode(',', $validator->messages()->all());
            
            $response_array = ['success' => false, 'error_messages' => $errors, 'error_code' => 101];

            return response()->json($response_array);

        } else {

            $userModel = User::find($request->id);

            $last_four = substr($request->number, -4);

            $stripe_secret_key = \Setting::get('stripe_secret_key');

            if($stripe_secret_key) {

                \Stripe\Stripe::setApiKey($stripe_secret_key);

            } else {

                $response_array = ['success'=>false, 'error_messages'=>tr('add_card_is_not_enabled')];

                return response()->json($response_array);
            }

            try {

                // Get the key from settings table
                
                $customer = \Stripe\Customer::create([
                        "card" => $request->card_token,
                        "email" => $userModel->email
                    ]);

                if($customer) {

                    $customer_id = $customer->id;

                    $cards = new Card;
                    $cards->user_id = $userModel->id;
                    $cards->customer_id = $customer_id;
                    $cards->last_four = $last_four;
                    $cards->card_token = $customer->sources->data ? $customer->sources->data[0]->id : "";

                    // Check is any default is available
                    $check_card = Card::where('user_id', $userModel->id)->first();

                    if($check_card)
                        $cards->is_default = 0;
                    else
                        $cards->is_default = 1;
                    
                    $cards->save();

                    if($userModel && $cards->is_default) {

                        $userModel->payment_mode = 'card';

                        $userModel->card_id = $cards->id;

                        $userModel->save();
                    }

                    $data = [
                            'user_id'=>$request->id, 
                            'id'=>$request->id, 
                            'token'=>$userModel->token,
                            'card_id'=>$cards->id,
                            'customer_id'=>$cards->customer_id,
                            'last_four'=>$cards->last_four, 
                            'card_token'=>$cards->card_token, 
                            'is_default'=>$cards->is_default
                            ];

                    $response_array = array('success' => true,'message'=>tr('add_card_success'), 
                        'data'=> $data);

                    return response()->json($response_array);

                } else {

                    $response_array = ['success'=>false, 'error_messages'=>tr('Could not create client ID')];

                    throw new Exception(tr('Could not create client ID'));
                    
                }
            
            } catch(Exception $e) {

                $response_array = ['success'=>false, 'error_messages'=>$e->getMessage()];

                return response()->json($response_array);

            }

        }

    }    

    /**
     * Function Name : default_card()
     * 
     * Change the card as default card
     *
     * @param object $request - user id, card id
     * 
     * @return card details object
     */
    public function default_card(Request $request) {

        $validator = Validator::make(
            $request->all(),
            array(
                'card_id' => 'required|integer|exists:cards,id,user_id,'.$request->id,
            ),
            array(
                'exists' => 'The :attribute doesn\'t belong to user:'.$request->id
            )
        );

        if($validator->fails()) {

            $error_messages = implode(',', $validator->messages()->all());

            $response_array = array('success' => false, 'error_messages' => $error_messages, 'error_code' => 101);

        } else {

            $user = User::find($request->id);
            
            $old_default = Card::where('user_id' , $request->id)->where('is_default', DEFAULT_TRUE)->update(array('is_default' => DEFAULT_FALSE));

            $card = Card::where('id' , $request->card_id)->update(array('is_default' => DEFAULT_TRUE));

            if($card) {

                if($user) {

                    $user->card_id = $request->card_id;

                    $user->save();
                }

                $response_array = Helper::null_safe(array('success' => true, 'data'=>['id'=>$request->id,'token'=>$user->token]));

            } else {

                $response_array = array('success' => false , 'error_messages' => tr('something_error'));

            }
        }
        return response()->json($response_array , 200);
    
    }

    /**
     * Function Name : delete_card()
     * 
     * Delete the card who has logged in (Based on User Id, Card Id)
     *
     * @param object $request - user id, card id
     * 
     * @return success/failure message
     */
    public function delete_card(Request $request) {
    
        $card_id = $request->card_id;

        $validator = Validator::make(
            $request->all(),
            array(
                'card_id' => 'required|integer|exists:cards,id,user_id,'.$request->id,
            ),
            array(
                'exists' => 'The :attribute doesn\'t belong to user:'.$request->id
            )
        );

        if ($validator->fails()) {
            
            $error_messages = implode(',', $validator->messages()->all());
            
            $response_array = array('success' => false , 'error_messages' => $error_messages , 'error_code' => 101);
        
        } else {

            $user = User::find($request->id);


            if ($user->card_id == $card_id) {

                $response_array = array('success' => false, 'error_messages'=> tr('card_default_error'));

            } else {

                Card::where('id',$card_id)->delete();

                if($user) {

                    $cards = Card::where('user_id' , $request->id)->count();

                    if ($cards > 1) {


                    } else {

                        if($check_card = Card::where('user_id' , $request->id)->first()) {

                            $check_card->is_default =  DEFAULT_TRUE;

                            $user->card_id = $check_card->id;

                            $check_card->save();

                        } else { 

                            $user->payment_mode = COD;

                            $user->card_id = DEFAULT_FALSE;
                        }

                    }

                    $user->save();
                }

                $response_array = array('success' => true, 
                        'message'=>tr('card_deleted'), 
                        'data'=> ['id'=>$request->id,'token'=>$user->token, 'position'=>$request->position]);

            }
            
        }
    
        return response()->json($response_array , 200);
    }

    /**
     * Function Name : subscription_plans()
     * 
     * List out all the subscription plans (Mobile Usage)
     *
     * @param object $request - As of now no attributes
     * 
     * @return list of subscriptions
     */
    public function subscription_plans(Request $request) {

        $query = Subscription::select('id as subscription_id',
                'title', 'description', 'plan','amount', 'no_of_account',
                'status', 'popular_status','created_at' , DB::raw("'$' as currency"))
                ->where('status' , DEFAULT_TRUE);

        if ($request->id) {

            $user = User::find($request->id);

            if ($user) {

               if ($user->one_time_subscription == DEFAULT_TRUE) {

                   $query->where('amount','>', 0);

               }

            } 

        }

        $model = $query->orderBy('amount' , 'asc')->get();

        $model = (!empty($model) && $model != null) ? $model : [];

        $response_array = ['success'=>true, 'data'=>$model];

        return response()->json($response_array, 200);

    }

    /**
     * Function Name : subscribedPlans()
     * 
     * List out all the subscribed pans based on the user id
     *
     * @param object $request - As of now no attributes
     * 
     * @return list of subscribed plans
     */
    public function subscribedPlans(Request $request){

        $model = UserPayment::where('user_id' , $request->id)
                        ->leftJoin('subscriptions', 'subscriptions.id', '=', 'subscription_id')
                        ->select('user_id as id',
                                'subscription_id',
                                'user_payments.id as user_subscription_id',
                                'subscriptions.title as title',
                                'subscriptions.description as description',
                                'subscriptions.plan',
                                'user_payments.amount as amount',
                                'no_of_account',
                                'popular_status',
                                'payment_mode',
                                \DB::raw('DATE_FORMAT(user_payments.expiry_date , "%e %b %Y") as expiry_date'),
                                'user_payments.created_at as created_at',
                                DB::raw("'$' as currency"))
                        ->orderBy('user_payments.updated_at', 'desc')
                        ->get();

        $model = (!empty($model) && $model != null) ? $model : [];

        $response_array = ['success'=>true, 'data'=>$model];

        return response()->json($response_array);

    }

    /**
     * Function Name : pay_now()
     * 
     * Pay the payment of subscription plan using paypal (Mobile Usage) 
     *
     * @param object $request - payment id, subscription id
     * 
     * @return resposne of success/failure message
     */
    public function pay_now(Request $request) {

        try {

            DB::beginTransaction();

            $validator = Validator::make(
                $request->all(),
                array(
                    'subscription_id'=>'required|exists:subscriptions,id',
                    'payment_id'=>'required',
                    'coupon_code'=>'exists:coupons,coupon_code',
                    'total_amount'=>'numeric',
                ),  array(
                    'exists' => 'The :attribute doesn\'t exists',
                ));

            if ($validator->fails()) {
                // Error messages added in response for debugging
                $errors = implode(',',$validator->messages()->all());

                $response_array = ['success' => false,'error_messages' => $errors,'error_code' => 101];

                throw new Exception($errors);

            } else {

                $subscription = Subscription::find($request->subscription_id);

                $model = UserPayment::where('user_id' , $request->id)->where('status', DEFAULT_TRUE)->orderBy('id', 'desc')->first();

                $user_payment = new UserPayment;

                if ($model) {

                    if (strtotime($model->expiry_date) >= strtotime(date('Y-m-d H:i:s'))) {

                     $user_payment->expiry_date = date('Y-m-d H:i:s', strtotime("+{$subscription->plan} months", strtotime($model->expiry_date)));

                    } else {

                        $user_payment->expiry_date = date('Y-m-d H:i:s',strtotime("+{$subscription->plan} months"));

                    }

                } else {

                    $user_payment->expiry_date = date('Y-m-d H:i:s',strtotime("+{$subscription->plan} months"));

                }

                $user_payment->payment_id  = $request->payment_id;

                $user_payment->user_id = $request->id;

                $user_payment->sub_amount = $subscription->amount;

                $user_payment->subscription_id = $request->subscription_id;


                $user_payment->payment_mode = PAYPAL;

                if($request->coupon_code){

                    // Coupon code find the coupon detail
                    $coupon_detail = Coupon::where('coupon_code',$request->coupon_code)->where('status',1)->first();
                   
                    if($coupon_detail){

                        $user_payment->coupon_amount = $coupon_detail->amount;

                        $user_payment->coupon_code = $request->coupon_code;

                        $user_payment->amount = $request->total_amount;
                    }

                }

                $user_payment->save();

                if($user_payment) {

                    if ($user_payment->user) {

                        if ($user_payment->amount <= 0) {

                            $user_payment->user->one_time_subscription = DEFAULT_TRUE;

                        }

                        $user_payment->user->user_type = DEFAULT_TRUE;

                        $user_payment->user->save();

                    } else {

                        throw new Exception(tr('no_user_detail_found'));
                        
                    }

                } else {

                    throw new Exception(tr('user_payment_not_save'));
                    
                }

                $response_array = ['success'=>true, 'message'=>tr('payment_success'), 
                        'data'=>[
                            'id'=>$request->id,
                            'token'=>$user_payment->user ? $user_payment->user->token : '',
                            'no_of_account'=>$subscription->no_of_account
                            ]];

            }

            DB::commit();

            return response()->json($response_array, 200);

        } catch (Exception $e) {

            DB::rollback();

            $e = $e->getMessage();

            $response_array = ['success'=>false, 'error_messages'=>$e];

            return response()->json($response_array);
        }

    }

    /**
     * Function Name : likevideo()
     * 
     * Like videos in each single video based on logged in user id
     *
     * @param object $request - video id & sub profile id
     * 
     * @return resposne of success/failure message with count of like and dislike
     */
    public function likevideo(Request $request) {

        $validator = Validator::make($request->all() , [
            'admin_video_id' => 'required|exists:admin_videos,id',
            'sub_profile_id'=>'required|exists:sub_profiles,id',
            ], array(
                'exists' => 'The :attribute doesn\'t exists',
            ));

         if ($validator->fails()) {

            $errors = implode(',', $validator->messages()->all());

            $response_array = array('success' => false , 'error_messages'=> $errors ,  'error_code' => 101);

        } else {

            $model = LikeDislikeVideo::where('admin_video_id', $request->admin_video_id)
                    ->where('user_id',$request->id)
                    ->where('sub_profile_id',$request->sub_profile_id)
                    ->first();

            $like_count = LikeDislikeVideo::where('admin_video_id', $request->admin_video_id)
                ->where('like_status', DEFAULT_TRUE)
                ->count();

            $dislike_count = LikeDislikeVideo::where('admin_video_id', $request->admin_video_id)
                ->where('dislike_status', DEFAULT_TRUE)
                ->count();

            if (!$model) {

                $model = new LikeDislikeVideo;

                $model->admin_video_id = $request->admin_video_id;

                $model->user_id = $request->id;

                $model->sub_profile_id = $request->sub_profile_id;

                $model->like_status = DEFAULT_TRUE;

                $model->dislike_status = DEFAULT_FALSE;

                $model->save();

                $response_array = ['success'=>true, 'like_count'=>$like_count+1, 'dislike_count'=>$dislike_count, 'delete'=>0];

            } else {

                if($model->dislike_status) {

                    $model->like_status = DEFAULT_TRUE;

                    $model->dislike_status = DEFAULT_FALSE;

                    $model->save();

                    $response_array = ['success'=>true, 'like_count'=>$like_count+1, 'dislike_count'=>$dislike_count-1];


                } else {

                    $model->delete();

                    $response_array = ['success'=>true, 'like_count'=>$like_count-1, 'dislike_count'=>$dislike_count, 'delete'=>1];

                }

            }

        }

        return response()->json($response_array);

    }

    /**
     * Function Name : dislikevideo()
     * 
     * DisLike videos in each single video based on logged in user id
     *
     * @param object $request - video id & sub profile id
     * 
     * @return resposne of success/failure message with count of like and dislike
     */
    public function dislikevideo(Request $request) {

        $validator = Validator::make($request->all() , [
            'admin_video_id' => 'required|exists:admin_videos,id',
            'sub_profile_id'=>'required|exists:sub_profiles,id',
            ], array(
                'exists' => 'The :attribute doesn\'t exists',
            ));

         if ($validator->fails()) {

            $error_messages = implode(',', $validator->messages()->all());

            $response_array = array('success' => false, 'error_messages'=>$error_messages , 'error_code' => 101);

        } else {

            $model = LikeDislikeVideo::where('admin_video_id', $request->admin_video_id)
                    ->where('user_id',$request->id)
                    ->where('sub_profile_id',$request->sub_profile_id)
                    ->first();
           
            $like_count = LikeDislikeVideo::where('admin_video_id', $request->admin_video_id)
                ->where('like_status', DEFAULT_TRUE)
                ->count();

            $dislike_count = LikeDislikeVideo::where('admin_video_id', $request->admin_video_id)
                ->where('dislike_status', DEFAULT_TRUE)
                ->count();

            if (!$model) {

                $model = new LikeDislikeVideo;

                $model->admin_video_id = $request->admin_video_id;

                $model->user_id = $request->id;

                $model->sub_profile_id = $request->sub_profile_id;

                $model->like_status = DEFAULT_FALSE;

                $model->dislike_status = DEFAULT_TRUE;

                $model->save();

                $response_array = ['success'=>true, 'like_count'=>$like_count, 'dislike_count'=>$dislike_count+1, 'delete'=>0];

            } else {

                if($model->like_status) {

                    $model->like_status = DEFAULT_FALSE;

                    $model->dislike_status = DEFAULT_TRUE;

                    $model->save();

                    $response_array = ['success'=>true, 'like_count'=>$like_count-1, 'dislike_count'=>$dislike_count+1];

                } else {

                    $model->delete();

                    $response_array = ['success'=>true, 'like_count'=>$like_count, 'dislike_count'=>$dislike_count-1, 'delete'=>1];

                }

            }

        }

        return response()->json($response_array);

    }

    /**
     * Function Name : spam_videos()
     * 
     * List of spam videos
     *
     * @param object $request - sub profile id
     * 
     * @return array of spam videos
     */
    public function spam_videos(Request $request) {

        $validator = Validator::make($request->all() , [
            'sub_profile_id'=>'required|exists:sub_profiles,id',
            ], array(
                'exists' => 'The :attribute doesn\'t exists',
            ));

        if ($validator->fails()) {

            $error_messages = implode(',', $validator->messages()->all());

            $response_array = array('success' => false, 'error_messages'=>$error_messages , 'error_code' => 101);

        } else {


            $subProfile = SubProfile::where('user_id', $request->id)
                        ->where('id', $request->sub_profile_id)->first();

            if (!$subProfile) {

                $response_array = ['success'=>false, 'error_messages'=>tr('sub_profile_details_not_found')];
                
                return response()->json($response_array , 200);
                
            } 

            if ($request->device_type != DEVICE_WEB) {

                $query = Flag::where('flags.user_id', $request->id)
                    ->where('flags.sub_profile_id', $request->sub_profile_id)
                    ->leftJoin('admin_videos' , 'flags.video_id' , '=' , 'admin_videos.id')
                    ->where('admin_videos.is_approved' , 1)
                    ->where('admin_videos.status' , 1);

                if ($request->skip) {

                    $model = $query->skip($request->skip)->take(Setting::get('admin_take_count'))->get();

                } else {

                    $model = $query->get();
                }

                $flag_video = [];

                if (!empty($model) && $model != null) {

                    foreach ($model as $key => $value) {
                        
                        $flag_video[] = displayFullDetails($value->video_id, $request->id);

                    }
                }

            } else {

                $model = Flag::where('flags.user_id', $request->id)
                    ->select('flags.*', 'admin_videos.id as admin_video_id')
                    ->where('flags.sub_profile_id', $request->sub_profile_id)
                    ->leftJoin('admin_videos' , 'flags.video_id' , '=' , 'admin_videos.id')
                    ->where('admin_videos.is_approved' , 1)
                    ->where('admin_videos.status' , 1)
                    ->skip($request->skip)->take($request->take)->get();

                $chunk = $model->chunk(4);

                $flag_video = [];

                foreach ($chunk as $key => $value) {


                    $group = [];

                    foreach ($value as $key => $data) {
                     
                        $group[] = displayFullDetails($data->admin_video_id, $request->sub_profile_id);


                    }

                    $flag_video[] = $group;

                }


            }

            $response_array = ['success'=>true, 'data'=>$flag_video];
        }

        return response()->json($response_array);

    }

    /**
     * Function Name : add_spam()
     * 
     * Spam videos based on each single video based on logged in user id, If they flagged th video they wont see in any of the pages except spam videos page
     *
     * @param object $request - sub profile id, video id
     * 
     * @return spam video details
     */
    public function add_spam(Request $request) {

        $validator = Validator::make($request->all(), [
            'admin_video_id' => 'required|exists:admin_videos,id',
            'sub_profile_id'=>'required|exists:sub_profiles,id',
            'reason' => 'required',
        ], array(
                'exists' => 'The :attribute doesn\'t exists',
            ));

        
        if ($validator->fails()) {

            $error_messages = implode(',', $validator->messages()->all());

            $response_array = array('success' => false, 'error_messages'=>$error_messages , 'error_code' => 101);

            return response()->json($response_array);
        }

        $subProfile = SubProfile::where('user_id', $request->id)
                        ->where('id', $request->sub_profile_id)->first();

        if (!$subProfile) {

            $response_array = ['success'=>false, 'error_messages'=>tr('sub_profile_details_not_found')];

            return response()->json($response_array);
            
        }

        $spam_video = Flag::where('user_id', $request->id)->where('video_id', $request->admin_video_id)->where('sub_profile_id', $request->sub_profile_id)->first();

        if (!$spam_video) {

            
            $data = $request->all();

            
            $data['user_id'] = $request->id;
            $data['video_id'] =$request->admin_video_id;

            $data['sub_profile_id'] = $request->sub_profile_id;
            
            $data['status'] = DEFAULT_TRUE;

            
            if (Flag::create($data)) {

                return response()->json(['success'=>true, 'message'=>tr('report_video_success_msg')]);

            } else {
                
                return response()->json(['success'=>true, 'message'=>tr('admin_published_video_failure')]);
            }

        } else {

            return response()->json(['success'=>true, 'message'=>tr('report_video_success_msg')]);

        }

    }

    /**
     * Function Name : reasons()
     * 
     * List of reasons to display while spam video
     *
     * @return array of reasons
     */
    public function reasons() {

        $reasons = getReportVideoTypes();

        return response()->json(['success'=>true, 'data'=>$reasons]);
    }

    /**
     * Function Name : remove_spam()
     * 
     * Remove Spam videos based on each single video based on logged in user id, You can see the videos in all the pages
     *
     * @param object $request - sub profile id, video id
     * 
     * @return spam video details
     */
    public function remove_spam(Request $request) {

        $validator = Validator::make($request->all(), [
            'admin_video_id' => 'exists:admin_videos,id',
            'sub_profile_id'=>'required|exists:sub_profiles,id',
        ], array(
                'exists' => 'The :attribute doesn\'t exists',
            ));
        
        if ($validator->fails()) {

            $error_messages = implode(',', $validator->messages()->all());

            $response_array = array('success' => false, 'error_messages'=>$error_messages , 'error_code' => 101);

            return response()->json($response_array);
        }

        if($request->status == 1) {

            $model = Flag::where('user_id', $request->id)
                ->where('sub_profile_id', $request->sub_profile_id)->delete();

            return response()->json(['success'=>true, 'message'=>tr('unmark_report_video_success_msg')]);

        } else {
        
            $model = Flag::where('user_id', $request->id)
                ->where('sub_profile_id', $request->sub_profile_id)
                ->where('video_id', $request->admin_video_id)
                ->first();

            if ($model) {

                $model->delete();

                return response()->json(['success'=>true, 'message'=>tr('unmark_report_video_success_msg')]);

            } else {
                
                return response()->json(['success'=>true, 'message'=>tr('admin_published_video_failure')]);
            }
        }
    }

    /**
     * Function Name : watch_count()
     * 
     * Each and every video once the user click the video player, the count will increase
     *
     * @param object $request - video id
     * 
     * @return spam video details
     */
    public function watch_count(Request $request) {

        if($video = AdminVideo::where('id',$request->admin_video_id)
                ->where('status',1)
                ->first()) {

            // $video->watch_count += 1;

            // $video->save();

            Log::info($video->watch_count);

            return response()->json([
                    'success'=>true, 
                    'data'=>[ 'watch_count' => number_format_short($video->watch_count)]]);

        } else {

            return response()->json(['success'=>false, 'error_messages'=>tr('no_video_found')]);

        }

    }

    /**
     * Function Name : plan_detail()
     *
     * Display plan detail based on plan id
     *
     * @param object $param - User id, token and plan id
     *
     * @return response of object
     */
    public function plan_detail(Request $request) {

        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:subscriptions,id',            
        ], array(
                'exists' => 'The :attribute doesn\'t exists',
            ));
        
        if ($validator->fails()) {

            $error_messages = implode(',', $validator->messages()->all());

            $response_array = array('success' => false, 'error_messages'=>$error_messages , 'error_code' => 101);

            return response()->json($response_array);
        }
        
        $model = Subscription::find($request->plan_id);

        if ($model) {

            return response()->json(['success'=>true, 'data'=>$model]);

        } else {
            
            return response()->json(['success'=>false, 'message'=>tr('subscription_not_found')]);
        }

    } 

    /**
     * Function Name : logout()
     *
     * Delete logged device while logout user
     *
     * @param interger $request - User Id
     *
     * @return boolean  succes/failure message
     */
    public function logout(Request $request) {

        try {

            DB::beginTransaction();

            $model = UserLoggedDevice::where('user_id', $request->id)->first();

            $response_array = ['success'=>true];

            if ($model) {

                if ($model->delete()) {

                    $user = User::find($request->id);

                    if ($user) {

                        $user->logged_in_account -= 1;

                        if ($user->save()) {

                            $response_array = ['success'=>true];
                                
                        } else {

                            throw new Exception(tr('user_details_not_save'));

                        }

                    } else {

                        throw new Exception(tr('no_user_detail_found'));
                        
                    }

                } else {

                    throw new Exception(tr('logged_in_device_not_delete'));
                    
                }

            }

            DB::commit();

            return response()->json($response_array);

        } catch(Exception $e) {

            $message = $e->getMessage();

            $response_array = ['success'=>false, 'error_messages'=>$e];

            return response()->json($response_array);
        }

    }

    /**
     * Function Name : check_token_valid()
     *
     * To check the token is valid for the user or not
     * 
     * @param object $request - User id and token
     *
     * @return Object with success message
     */
    public function check_token_valid(Request $request) {

        return response()->json(['data'=>$request->all(), 'success'=>true]);

    }

    /**
     * Function Nmae : ppv_list()
     * 
     * to list out  all the paid videos by logged in user using PPV
     *
     * @param object $request - User id, token 
     *
     * @return response of array with message
     */
    public function ppv_list(Request $request) {

        $model = PayPerView::select('pay_per_views.id as pay_per_view_id',
                'video_id as admin_video_id',
                'admin_videos.title',
                'pay_per_views.amount',
                'pay_per_views.status as video_status',
                'admin_videos.default_image as picture',
                'pay_per_views.type_of_subscription',
                'pay_per_views.type_of_user',
                'pay_per_views.payment_id',
                'pay_per_views.payment_mode',
                 DB::raw('DATE_FORMAT(pay_per_views.created_at , "%e %b %y") as paid_date'))
                ->leftJoin('admin_videos', 'admin_videos.id', '=', 'pay_per_views.video_id')
                ->where('pay_per_views.user_id', $request->id)
                ->where('pay_per_views.amount', '>', 0)
                ->get();

        $data = [];

        foreach ($model as $key => $value) {
            
            $data[] = ['pay_per_view_id'=>$value->pay_per_view_id,
                    'admin_video_id'=>$value->admin_video_id,
                    'title'=>$value->title,
                    'amount'=>$value->amount,
                    'video_status'=>$value->video_status,
                    'paid_date'=>$value->paid_date,
                    'currency'=>Setting::get('currency'),
                    'picture'=>$value->picture,
                    'type_of_subscription'=>$value->type_of_subscription,
                    'type_of_user'=>$value->type_of_user,
                    'payment_id'=>$value->payment_id,
                    'payment_mode'=>$value->payment_mode];

        }

        return response()->json(['success'=>true,'data'=>$data]);

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
    public function continue_watching_videos(Request $request) {

        try {

            $validator = Validator::make(
                $request->all(),
                array(
                    'skip' => 'required|numeric',
                    'device_type'=>'in:'.DEVICE_WEB.','.DEVICE_IOS.','.DEVICE_ANDROID,
                )
            );

            if ($validator->fails()) {

                $error_messages = implode(',', $validator->messages()->all());

                throw new Exception($error_messages);
                
            } else {

                if (!$request->has('sub_profile_id')) {

                    $sub_profile = SubProfile::where('user_id', $request->id)->where('status', DEFAULT_TRUE)->first();

                    if ($sub_profile) {
                        
                        $sub_profile_id = $sub_profile->id;

                    } else  {

                        throw new Exception(tr('sub_profile_details_not_found'));
                        
                    }

                } else  {

                    $subProfile = SubProfile::where('user_id', $request->id)
                                    ->where('id', $request->sub_profile_id)->first();

                    if (!$subProfile) {

                        throw new Exception(tr('sub_profile_details_not_found'));
                        
                    }

                    $sub_profile_id = $request->sub_profile_id;
                 
                }
            

                $video_list = Helper::continue_watching_videos($sub_profile_id,$request->device_type,$request->skip);

                $videos = [];

                if (count($video_list) > 0) {

                    foreach ($video_list as $key => $value) {
                        
                        $videos[] = displayFullDetails($value->admin_video_id, $request->sub_profile_id);

                    }
                }

               // $total = count($videos);

                $response_array = array('success' => true, 'data' => $videos);

                return response()->json($response_array, 200);
            }

        } catch (Exception $e) {

            $e = $e->getMessage();

            $response_array = ['success'=>false, 'error_messages'=>$e];

            return response()->json($response_array);

        }

    }


    /**
     * Function Name : save_continue_watching_video
     *
     * To save every few seconds in continue wattching videos
     *
     * @param object $request - Userid, token, sub_profile_id & admin video id, duarion
     *
     * @return response of success / failure
     */
    public function save_continue_watching_video(Request $request) {

        try {

            Log::info("test");

            $validator = Validator::make(
                $request->all(),
                array(
                    'admin_video_id' => 'required|exists:admin_videos,id',
                    'duration'=>'required',
                )
            );

            if ($validator->fails()) {

                $error_messages = implode(',', $validator->messages()->all());

                throw new Exception($error_messages);
                
            } else {

                if (!$request->has('sub_profile_id')) {

                    $sub_profile = SubProfile::where('user_id', $request->id)->where('status', DEFAULT_TRUE)->first();

                    if ($sub_profile) {
                        
                        $sub_profile_id = $sub_profile->id;

                    } else  {

                        throw new Exception(tr('sub_profile_details_not_found'));
                        
                    }

                } else  {

                    $subProfile = SubProfile::where('user_id', $request->id)
                                    ->where('id', $request->sub_profile_id)->first();

                    if (!$subProfile) {

                        throw new Exception(tr('sub_profile_details_not_found'));
                        
                    }

                    $sub_profile_id = $request->sub_profile_id;
                 
                }

                $model = ContinueWatchingVideo::where('sub_profile_id', $sub_profile_id)->where('admin_video_id', $request->admin_video_id)->first();

                if (!$model) {

                    $model = new ContinueWatchingVideo;

                }

                $video = AdminVideo::where('is_approved' , 1)->where('status' , 1)->where('id', $request->admin_video_id)->first();

                if ($video) {

                    $duration = "";

                    $explode_duration = explode(':', $request->duration);

                    if (count($explode_duration) == 3) {

                        $duration = $request->duration;

                    }

                    if (count($explode_duration) == 2) {

                        $duration = "00:".$explode_duration[0].':'.$explode_duration[1];

                    }

                    if (!$duration) {

                        throw new Exception(tr('duration_not_proper_format'));
                        
                    }

                    $model->user_id = $request->id;

                    $model->sub_profile_id = $sub_profile_id;

                    $model->admin_video_id = $video->id;

                    $model->status = DEFAULT_TRUE; 

                    $model->is_genre = $video->genre_id > 0 ? DEFAULT_TRUE : DEFAULT_FALSE;


                    if ($model->is_genre) {

                        $genre = Genre::where('status', DEFAULT_TRUE)->where('is_approved', DEFAULT_TRUE)->where('id', $video->genre_id)->first();

                        if (!$genre) {

                            throw new Exception(tr('genre_not_found'));
                            
                        }

                        $model->position = $video->position;

                        $model->genre_position = $genre->position;

                    } else {

                        $model->position = 0;

                        $model->genre_position = 0;
                    }

                    $model->duration = $duration;

                    $model->save();

                } else {

                    throw new Exception(tr('video_not_approved_by_admin'));

                }  

                $response_array = array('success' => true, 'data' => $model);

                return response()->json($response_array, 200);
            }

        } catch (Exception $e) {

            $e = $e->getMessage();

            $response_array = ['success'=>false, 'error_messages'=>$e];

            return response()->json($response_array);

        }

    }

    /**
     * Function Name : onCompleteVideo 
     *
     * Delete the row if it is completed based on type of video
     *
     * @param object $request - User id, token, sub_profile_id & admin_video_id
     *
     * @return response of success/failure message
     */
    public function on_complete_video(Request $request) {

        try {  

            $validator = Validator::make(
                $request->all(),
                array(
                    'admin_video_id' => 'required|exists:admin_videos,id',
                )
            );

            if ($validator->fails()) {

                $error_messages = implode(',', $validator->messages()->all());

                throw new Exception($error_messages);
                
            } else {

                if (!$request->has('sub_profile_id')) {

                    $sub_profile = SubProfile::where('user_id', $request->id)->where('status', DEFAULT_TRUE)->first();

                    if ($sub_profile) {
                        
                        $sub_profile_id = $sub_profile->id;

                    } else  {

                        throw new Exception(tr('sub_profile_details_not_found'));
                        
                    }

                } else  {

                    $subProfile = SubProfile::where('user_id', $request->id)
                                    ->where('id', $request->sub_profile_id)->first();

                    if (!$subProfile) {

                        throw new Exception(tr('sub_profile_details_not_found'));
                        
                    }

                    $sub_profile_id = $request->sub_profile_id;
                 
                }

                $model = ContinueWatchingVideo::where('sub_profile_id', $sub_profile_id)
                    ->where('admin_video_id', $request->admin_video_id)->first();

                if (!$model) {

                   throw new Exception(tr('continue_watching_video_not_found'));

                }

                $video = AdminVideo::where('is_approved' , DEFAULT_TRUE)
                        ->where('status' , DEFAULT_TRUE)
                        ->where('id', $request->admin_video_id)
                        ->first();

                if ($video) {

                    if($model->is_genre) {

                        if ($video->genre_id > 0) {

                            $video_position = $model->position + 1;

                            $genre_position = $model->genre_position;

                            $available = $this->check_next_genre($video_position, $genre_position, $video, $request->sub_profile_id)->getData();
                                
                            if ($available->admin_video_id) {

                                $model->admin_video_id = $available->admin_video_id;

                                $model->position = $available->position;

                                $model->genre_position = $available->genre_position;

                                $model->duration = $available->duration;

                                $model->save();

                            } else {

                                $model->delete();

                            }

                        } else {

                            // throw new Exception(tr('genre_not_found'));

                            $model->delete();

                        }

                    } else {

                        // throw new Exception(tr('video_not_approved_by_admin'));

                        $model->delete();

                    }  

                } else {

                  // throw new Exception(tr('video_not_approved_by_admin'));

                    $model->delete();

                     
                }

                $response_array = array('success' => true);

                return response()->json($response_array, 200);
            
            }

        } catch (Exception $e) {

            $e = $e->getMessage();

            $response_array = ['success'=>false, 'error_messages'=>$e];

            return response()->json($response_array);

        }
                            
    }

   /*
    * Function Name: coupon_apply_subscription()
    *
    * Description: Apply the coupon code in particular with reduce the total subscription amount to coupon amount
    *
    * @created Maheswari
    *
    * @edited Maheswari
    *
    * @param subscription_id,coupon_code
    *
    * @return Json success message with reduce amounts
    */
    public function coupon_apply_subscription(Request $request){
    

        $validator = Validator::make($request->all(),array(

            'subscription_id'=>'required|exists:subscriptions,id,status,1',

            'coupon_code'=>'required|exists:coupons,coupon_code,status,1',
        ));

        if($validator->fails()){

            $error_messages = implode(',', $validator->messages()->all());

            $response_array = ['success'=>false,'error_messages'=>$error_messages];
        }

        if($request->subscription_id){

            // Get the particular subscription detail in approve status 
            $subscription_detail = Subscription::where('id',$request->subscription_id)->where('status',1)->first();

            if($subscription_detail){

                $sub_amount = $subscription_detail->amount;

                // Get the coupon detail related  on coupon code
                $coupon_detail = Coupon::where('coupon_code',$request->coupon_code)->where('status',1)->first();

                if($coupon_detail){

                    $coupon_amount = $coupon_detail->amount;
                    // Get the current date
                    $current_date = date('Y-m-d');
                   
                    if( $coupon_detail->expiry_date >= $current_date){

                        // Check amount type percentage or not
                        if($coupon_detail->amount_type == PERCENTAGE){
                         
                            // Reduce the amount
                            $total_subscription_amount =  ($coupon_amount/100) * $sub_amount;

                        } else{

                            // This condition is amount_type absoulte check the subscription  amount and coupon amount
                            if($sub_amount > $coupon_amount){

                                $total_subscription_amount = $sub_amount - $coupon_amount;

                            } else {

                                $total_subscription_amount  = DEFAULT_FALSE;
                            }

                        }
                            
                        $response_array = ['success'=>true,
                        'coupon_code'=>$coupon_detail->coupon_code ,
                        'subscription_amount'=>$sub_amount,
                        'coupon_amount_type'=>$coupon_detail->amount_type,
                        'coupon_amount'=>$coupon_amount,
                        'total_amount'=>$sub_amount > $total_subscription_amount ? $total_subscription_amount: DEFAULT_FALSE,
                        ];
            

                    } else {

                        $response_array = ['success'=>false, 'error_messages'=>tr('coupon_date_expired_error')];
                    }

                } else {

                      $response_array = ['success'=>false, 
                    'error_messages'=>tr('coupon_not_found_error')];
                }
            }else {

                $response_array = ['success'=>false, 
                'error_messages'=>tr('no_subscription_found')];
            }
        } else {

            $response_array = ['success'=>false, 
            'error_messages'=>tr('no_subscription_found')];
        }

        return response()->json($response_array);
    }



    /**
     * Function Name : check_next_genre()
     *
     * To check next genre available or not
     *
     * @param object $request - User id, token , genre_id and etc
     *
     * @return response of Boolean 
     */
    public function check_next_genre($video_position, $genre_position, $video, $sub_profile_id) {

        $continous_watching_videos = continueWatchingVideos($sub_profile_id);

        $next_video = AdminVideo::where('is_approved' , DEFAULT_TRUE)
                    ->where('status' , DEFAULT_TRUE)
                    ->where('genre_id', $video->genre_id)
                    ->where('position', $video_position)
                    ->first();

        if (!$next_video) {

            $genre_position += 1;

            $next_genre = Genre::select('admin_videos.id as admin_video_id',
                        'genres.position as genre_position',
                        'admin_videos.position as video_position')->where('genres.status', DEFAULT_TRUE)
                        ->where('genres.is_approved', DEFAULT_TRUE)
                        ->leftJoin('admin_videos' , 'admin_videos.genre_id' , '=' , 'genres.id')
                        ->where('genres.sub_category_id', $video->sub_category_id)
                        ->where('genres.position', $genre_position)
                        ->where('admin_videos.status', DEFAULT_TRUE)
                        ->where('admin_videos.is_approved', DEFAULT_TRUE)
                        ->havingRaw("COUNT(admin_videos.id) > 0")
                        ->orderBy("admin_videos.position", 'asc')
                        ->whereNotIn('admin_videos.id', $continous_watching_videos)
                        ->first();

            if ($next_genre) {

                $response_array = ['admin_video_id' => $next_genre->admin_video_id,
                    'position'=>$next_genre->video_position,
                    'genre_position'=>$next_genre->genre_position,
                    'duration' => "00:00:00"];

            } else {

                $response_array = ['admin_video_id'=>''];

            }

        } else {

            if (!in_array($next_video->id, $continous_watching_videos)) {

                $response_array = ['admin_video_id' => $next_video->id,
                        'position'=>$next_video->position,
                        'genre_position'=>$genre_position,
                        'duration' => "00:00:00"];

            } else {

                $response_array = ['admin_video_id'=>''];
                
            }
        }


        return response()->json($response_array);

    }

    /**
     * Function Name : email_notification()
     *
     * To enable/disable email notifications
     *
     * @param object $request - User id, token & Email Notification status
     *
     * @return response of success/failure message
     */
    public function email_notification(Request $request) {

        $user = User::find($request->id);

        if($user) {

            $user->email_notification = $request->notification;

            if ($user->save()) {

                $response_array = ['success'=>true, 'message'=>tr('notification_status_updated')];

            } else {

                $response_array = ['success'=>false, 'error_messages'=>tr('user_details_not_save')];
            }

        } else {

            $response_array = ['success'=>false, 'error_messages'=>tr('no_user_detail_found')];
        }

        return response()->json($response_array);
    }
                      
    /**
    * Function Name: coupon_apply_videos()
    *
    * Description: Apply the coupons for  ppv videos in save ppv payments list
    *
    * @created Maheswari
    *
    * @edited Maheswari
    *
    * @param Vidoe id, Coupon_code
    *
    * @return json response for success message with total amount
    */
    public function coupon_apply_videos(Request $request){
        
        $validator = Validator::make($request->all(),[

            'video_id'=>'required|exists:admin_videos,id,status,1',
            'coupon_code'=>'required|exists:coupons,coupon_code,status,1',

        ]);

        if($validator->fails()){

            $error_messages = implode('',$validator->messages()->all());

            $response_array = ['success'=>false,'error_messages'=>$error_messages];
        }

        $video_detail = AdminVideo::where('id',$request->video_id)->where('status',1)->first();

        if($video_detail){
           
            if($video_detail->amount>0){
                
                $video_amount = $video_detail->amount;

                $coupon_detail = Coupon::where('coupon_code',$request->coupon_code)->where('status',1)->first();

                if($coupon_detail){

                    $coupon_amount = $coupon_detail->amount;

                    $current_date = date('Y-m-d');

                    if($coupon_detail->expiry_date >= $current_date){

                        // Check amount type percentage or not
                        if($coupon_detail->amount_type == PERCENTAGE){
                            
                            // Reduce the amount with percentage type
                            $total_video_amount =  ($coupon_amount/100) * $video_amount;

                        } else{

                            // This condition is amount_type absoulte check the video  amount and coupon amount
                            if($video_amount > $coupon_amount){
                                
                                $total_video_amount = $video_amount - $coupon_amount;

                            } else {

                                $total_video_amount  = DEFAULT_FALSE;

                            }

                        }
                       
                        $response_array = ['success'=>true,
                        'coupon_code'=>$coupon_detail->coupon_code ,
                        'video_amount'=>$video_amount,
                        'coupon_amount_type'=>$coupon_detail->amount_type,
                        'coupon_amount'=>$coupon_amount,
                        'total_amount'=>$video_amount > $total_video_amount ? $total_video_amount : DEFAULT_FALSE,
                        ];
                   

                    } else {

                        $response_array = ['success'=>false,'error_messages'=>tr('coupon_date_expired_error')];
                    }

                } else{

                    $response_array = ['success'=>false,'error_messages'=>tr('coupon_not_found_error')];
                }

            } else {

                $response_array = ['success'=>false,'error_messages'=>tr('ppv_not_found_error')];
            }

        } else{

            $response_array = ['success'=>false,'error_messages'=>tr('video_not_found_error')];
        }

        return response()->json($response_array);
    } 
}
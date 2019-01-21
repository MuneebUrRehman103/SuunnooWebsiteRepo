<?php

use Illuminate\Support\Facades\Redis;

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

// Report Video type


//Demo User

if(!defined('DEMO_USER')) define('DEMO_USER', 'user@streamview.com');

// REDEEMS

if(!defined('REDEEM_OPTION_ENABLED')) define('REDEEM_OPTION_ENABLED', 1);

if(!defined('REDEEM_OPTION_DISABLED')) define('REDEEM_OPTION_DISABLED', 0);

// Redeeem Request Status

if(!defined('REDEEM_REQUEST_SENT')) define('REDEEM_REQUEST_SENT', 0);
if(!defined('REDEEM_REQUEST_PROCESSING')) define('REDEEM_REQUEST_PROCESSING', 1);
if(!defined('REDEEM_REQUEST_PAID')) define('REDEEM_REQUEST_PAID', 2);
if(!defined('REDEEM_REQUEST_CANCEL')) define('REDEEM_REQUEST_CANCEL', 3);

if(!defined('REPORT_VIDEO_KEY')) define('REPORT_VIDEO_KEY', 'REPORT_VIDEO');
if (!defined('IMAGE_RESOLUTIONS_KEY')) define('IMAGE_RESOLUTIONS_KEY', 'IMAGE_RESOLUTIONS');
if (!defined('VIDEO_RESOLUTIONS_KEY')) define('VIDEO_RESOLUTIONS_KEY', 'VIDEO_RESOLUTIONS');
if(!defined('DELETE_STATUS')) define('DELETE_STATUS', -1);

// User Type
if(!defined('NORMAL_USER')) define('NORMAL_USER', 1);
if(!defined('PAID_USER')) define('PAID_USER', 2);
if(!defined('BOTH_USERS')) define('BOTH_USERS', 3);

// Subscription Type
if(!defined('ONE_TIME_PAYMENT')) define('ONE_TIME_PAYMENT', 1);
if(!defined('RECURRING_PAYMENT')) define('RECURRING_PAYMENT', 2);

// REQUEST STATE

if(!defined('REQUEST_STEP_1')) define('REQUEST_STEP_1', 1);
if(!defined('REQUEST_STEP_2')) define('REQUEST_STEP_2', 2);
if(!defined('REQUEST_STEP_3')) define('REQUEST_STEP_3', 3);
if(!defined('REQUEST_STEP_FINAL')) define('REQUEST_STEP_FINAL', 4);


if(!defined('USER')) define('USER', 0);

if(!defined('Moderator')) define('Moderator',1);

if(!defined('NONE')) define('NONE', 0);

if(!defined('MAIN_VIDEO')) define('MAIN_VIDEO', 1);
if(!defined('TRAILER_VIDEO')) define('TRAILER_VIDEO', 2);


if(!defined('DEFAULT_TRUE')) define('DEFAULT_TRUE', 1);
if(!defined('DEFAULT_FALSE')) define('DEFAULT_FALSE', 0);

if(!defined('ADMIN')) define('ADMIN', 'admin');
if(!defined('MODERATOR')) define('MODERATOR', 'moderator');

if(!defined('VIDEO_TYPE_UPLOAD')) define('VIDEO_TYPE_UPLOAD', 1);
if(!defined('VIDEO_TYPE_YOUTUBE')) define('VIDEO_TYPE_YOUTUBE', 2);
if(!defined('VIDEO_TYPE_OTHER')) define('VIDEO_TYPE_OTHER', 3);


if(!defined('VIDEO_UPLOAD_TYPE_s3')) define('VIDEO_UPLOAD_TYPE_s3', 1);
if(!defined('VIDEO_UPLOAD_TYPE_DIRECT')) define('VIDEO_UPLOAD_TYPE_DIRECT', 2);

if(!defined('NO_INSTALL')) define('NO_INSTALL' , 0);

if(!defined('SYSTEM_CHECK')) define('SYSTEM_CHECK' , 1);

if(!defined('INSTALL_COMPLETE')) define('INSTALL_COMPLETE' , 2);


if(!defined('ADMIN')) define('ADMIN', 'admin');
if(!defined('MODERATOR')) define('MODERATOR', 'moderator');

// Payment Constants
if(!defined('COD')) define('COD',   'cod');
if(!defined('PAYPAL')) define('PAYPAL', 'paypal');
if(!defined('CARD')) define('CARD',  'card');


if(!defined('RATINGS')) define('RATINGS', '0,1,2,3,4,5');

if(!defined('DEVICE_ANDROID')) define('DEVICE_ANDROID', 'android');
if(!defined('DEVICE_IOS')) define('DEVICE_IOS', 'ios');
if(!defined('DEVICE_WEB')) define('DEVICE_WEB', 'web');

if(!defined('USERS')) define('USERS',0);
if(!defined('MODERATORS')) define('MODERATORS',1);
if(!defined('CUSTOM_USERS')) define('CUSTOM_USERS',2);

if(!defined('ALL_USER')) define('ALL_USER', 'ALL_USER');
if(!defined('NORMAL_USERS')) define('NORMAL_USERS', 'NORMAL_USERS');
if(!defined('PAID_USERS')) define('PAID_USERS','PAID_USERS' );
if(!defined('SELECT_USERS')) define('SELECT_USERS', 'SELECT_USERS');

if(!defined('ALL_MODERATOR')) define('ALL_MODERATOR', 'ALL_MODERATOR');
if(!defined('SELECT_MODERATOR')) define('SELECT_MODERATOR','SELECT_MODERATOR');



if(!defined('WISHLIST_EMPTY')) define('WISHLIST_EMPTY' , 0);
if(!defined('WISHLIST_ADDED')) define('WISHLIST_ADDED' , 1);
if(!defined('WISHLIST_REMOVED')) define('WISHLIST_REMOVED' , 2);

if(!defined('RECENTLY_ADDED')) define('RECENTLY_ADDED' , 'recent');
if(!defined('TRENDING')) define('TRENDING' , 'trending');
if(!defined('SUGGESTIONS')) define('SUGGESTIONS' , 'suggestion');
if(!defined('WISHLIST')) define('WISHLIST' , 'wishlist');
if(!defined('WATCHLIST')) define('WATCHLIST' , 'watchlist');
if(!defined('BANNER')) define('BANNER' , 'banner');
if(!defined('CONTINUE_WATCHING')) define('CONTINUE_WATCHING', 'continue');


// Template Types

if(!defined('USER_WELCOME')) define('USER_WELCOME', 'user_welcome');
if(!defined('ADMIN_USER_WELCOME')) define('ADMIN_USER_WELCOME', 'admin_user_welcome');
if(!defined('FORGOT_PASSWORD')) define('FORGOT_PASSWORD', 'forgot_password');
if(!defined('MODERATOR_WELCOME')) define('MODERATOR_WELCOME', 'moderator_welcome');
if(!defined('PAYMENT_EXPIRED')) define('PAYMENT_EXPIRED', 'payment_expired');
if(!defined('PAYMENT_GOING_TO_EXPIRY')) define('PAYMENT_GOING_TO_EXPIRY', 'payment_going_to_expiry');
if(!defined('NEW_VIDEO')) define('NEW_VIDEO', 'new_video');
if(!defined('EDIT_VIDEO')) define('EDIT_VIDEO', 'edit_video');


if(!defined('WEB')) define('WEB' , 1);
//coupon
if(!defined('PERCENTAGE')) define('PERCENTAGE',0);

if(!defined('ABSOULTE')) define('ABSOULTE',1);

if(!defined('FREE_PLAN')) define('FREE_PLAN','free plan');

Route::get('/text/arc','Application@test');

Route::get('/payment/failure' , 'ApplicationController@payment_failure')->name('payment.failure');

Route::get('/clear-cache', function() {

    $exitCode = Artisan::call('config:cache');

   return back();

})->name('clear-cache');



Route::get('/generate/index' , 'ApplicationController@generate_index');

Route::get('/test' , 'ApplicationController@test');

Route::post('/test' , 'ApplicationController@test')->name('test');

Route::get('/email/verification' , 'ApplicationController@email_verify')->name('email.verify');

Route::get('/check/token', 'ApplicationController@check_token_expiry')->name('check_token_expiry');

// Installation

Route::get('/configuration', 'InstallationController@install')->name('installTheme');

Route::get('/system/check', 'InstallationController@system_check_process')->name('system-check');

Route::post('/configuration', 'InstallationController@theme_check_process')->name('install.theme');

Route::post('/install/settings', 'InstallationController@settings_process')->name('install.settings');

// Elastic Search Test

Route::get('/addIndex', 'ApplicationController@addIndex')->name('addIndex');

Route::get('/addAll', 'ApplicationController@addAllVideoToEs')->name('addAll');

// CRON

Route::get('/publish/video', 'ApplicationController@cron_publish_video')->name('publish');

Route::get('/notification/payment', 'ApplicationController@send_notification_user_payment')->name('notification.user.payment');

Route::get('/payment/expiry', 'ApplicationController@user_payment_expiry')->name('user.payment.expiry');

// Static Pages

Route::get('/privacy', 'UserApiController@privacy')->name('user.privacy');

Route::get('/terms_condition', 'UserApiController@terms')->name('user.terms');

Route::get('/static/terms', 'UserApiController@terms')->name('user.terms');

Route::get('/contact', 'UserController@contact')->name('user.contact');

Route::get('/privacy_policy', 'ApplicationController@privacy')->name('user.privacy_policy');

Route::get('/terms', 'ApplicationController@terms')->name('user.terms-condition');

Route::get('/about', 'ApplicationController@about')->name('user.about');

// Video upload 

Route::post('select/sub_category' , 'ApplicationController@select_sub_category')->name('select.sub_category');

Route::post('select/genre' , 'ApplicationController@select_genre')->name('select.genre');

Route::get('/admin-control', 'ApplicationController@admin_control')->name('admin_control');

Route::post('save_admin_control', 'ApplicationController@save_admin_control')->name('save_admin_control');


Route::group(['prefix' => 'admin'], function(){

    Route::get('login', 'Auth\AdminAuthController@showLoginForm')->name('admin.login');

    Route::post('login', 'Auth\AdminAuthController@login')->name('admin.login.post');

    Route::get('logout', 'Auth\AdminAuthController@logout')->name('admin.logout');

    // Registration Routes...

    Route::get('register', 'Auth\AdminAuthController@showRegistrationForm');

    Route::post('register', 'Auth\AdminAuthController@register');

    Route::get('user_approve', 'AdminController@user_approve')->name('admin.user_approve');

    // Password Reset Routes...
    Route::get('password/reset/{token?}', 'Auth\AdminPasswordController@showResetForm');

    Route::post('password/email', 'Auth\AdminPasswordController@sendResetLinkEmail');

    Route::post('password/reset', 'Auth\AdminPasswordController@reset');

    Route::get('/', 'AdminController@dashboard')->name('admin.dashboard');

    Route::get('/profile', 'AdminController@profile')->name('admin.profile');

	Route::post('/profile/save', 'AdminController@profile_process')->name('admin.save.profile');

	Route::post('/change/password', 'AdminController@change_password')->name('admin.change.password');

    // users

    Route::get('/users', 'AdminController@users')->name('admin.users');

    Route::get('/add/user', 'AdminController@add_user')->name('admin.add.user');

    Route::get('/edit/user', 'AdminController@edit_user')->name('admin.edit.user');

    Route::post('/add/user', 'AdminController@add_user_process')->name('admin.save.user');

    Route::get('/delete/user', 'AdminController@delete_user')->name('admin.delete.user');

    Route::get('/view/user/{id}', 'AdminController@view_user')->name('admin.view.user');

    Route::get('/view/subProfiles/{id}', 'AdminController@subProfiles')->name('admin.view.sub_profiles');

    Route::get('/user/upgrade/{id}', 'AdminController@user_upgrade')->name('admin.user.upgrade');

    Route::any('/upgrade/disable', 'AdminController@user_upgrade_disable')->name('user.upgrade.disable');

    Route::get('/redeems/{id?}', 'AdminController@user_redeem_requests')->name('admin.users.redeems');

    Route::post('/redeems/pay', 'AdminController@user_redeem_pay')->name('admin.users.redeem.pay');

    Route::get('/user/verify/{id?}', 'AdminController@user_verify_status')->name('admin.users.verify');

    // User History - admin

    Route::get('/user/history/{id}', 'AdminController@view_history')->name('admin.user.history');

    Route::get('/delete/history/{id}', 'AdminController@delete_history')->name('admin.delete.history');
    
    // User Wishlist - admin

    Route::get('/user/wishlist/{id}', 'AdminController@view_wishlist')->name('admin.user.wishlist');

    Route::get('/delete/wishlist/{id}', 'AdminController@delete_wishlist')->name('admin.delete.wishlist');

    // Spam Videos
    Route::get('/spam-videos', 'AdminController@spam_videos')->name('admin.spam-videos');

    Route::get('/view-users/{id}', 'AdminController@view_users')->name('admin.view-users');

    // Moderators

    Route::get('/moderators', 'AdminController@moderators')->name('admin.moderators');

    Route::get('/add/moderator', 'AdminController@add_moderator')->name('admin.add.moderator');

    Route::get('/edit/moderator/{id}', 'AdminController@edit_moderator')->name('admin.edit.moderator');

    Route::post('/add/moderator', 'AdminController@add_moderator_process')->name('admin.save.moderator');

    Route::get('/delete/moderator/{id}', 'AdminController@delete_moderator')->name('admin.delete.moderator');
    
    Route::get('/moderator/approve/{id}', 'AdminController@moderator_approve')->name('admin.moderator.approve');

    Route::get('/moderator/decline/{id}', 'AdminController@moderator_decline')->name('admin.moderator.decline');

    Route::get('/view/moderator/{id}', 'AdminController@moderator_view_details')->name('admin.moderator.view');

    // Categories

    Route::get('/categories', 'AdminController@categories')->name('admin.categories');

    Route::get('/add/category', 'AdminController@add_category')->name('admin.add.category');

    Route::get('/edit/category/{id}', 'AdminController@edit_category')->name('admin.edit.category');

    Route::post('/add/category', 'AdminController@add_category_process')->name('admin.save.category');

    Route::get('/delete/category', 'AdminController@delete_category')->name('admin.delete.category');

    Route::get('/view/category/{id}', 'AdminController@view_category')->name('admin.view.category');

    Route::get('/category/approve', 'AdminController@approve_category')->name('admin.category.approve');

    // Admin Sub Categories

    Route::get('/subCategories/{category}', 'AdminController@sub_categories')->name('admin.sub_categories');

    Route::get('/add/subCategory/{category}', 'AdminController@add_sub_category')->name('admin.add.sub_category');

    Route::get('/edit/subCategory/{category_id}/{sub_category_id}', 'AdminController@edit_sub_category')->name('admin.edit.sub_category');

    Route::post('/add/subCategory', 'AdminController@add_sub_category_process')->name('admin.save.sub_category');

    Route::get('/delete/subCategory/{id}', 'AdminController@delete_sub_category')->name('admin.delete.sub_category');

    Route::get('/view/subCategory/{id}', 'AdminController@view_sub_category')->name('admin.view.sub_category');

    Route::get('/subCategory/approve', 'AdminController@approve_sub_category')->name('admin.sub_category.approve');


     // Admin Sub Categories

    Route::get('/genres/{sub_category}', 'AdminController@genres')->name('admin.genres');

    Route::get('/add/genre/{sub_category}', 'AdminController@add_genre')->name('admin.add.genre');

    Route::get('/edit/genre/{sub_category_id}/{genre_id}', 'AdminController@edit_genre')->name('admin.edit.edit_genre');

    // Genre

    Route::post('/save/genre' , 'AdminController@save_genre')->name('admin.save.genre');

    Route::get('/genre/approve', 'AdminController@approve_genre')->name('admin.genre.approve');

    Route::get('/delete/genre/{id}', 'AdminController@delete_genre')->name('admin.delete.genre');

    Route::get('/view/genre/{id}', 'AdminController@view_genre')->name('admin.view.genre');

    Route::post('genre/change/position', 'AdminController@genre_position')->name('admin.save.genre.position');

    // Videos

    Route::get('/videos', 'AdminController@videos')->name('admin.videos');

    Route::get('/add/video', 'AdminController@add_video')->name('admin.add.video');

    Route::get('/edit/video/{id}', 'AdminController@edit_video')->name('admin.edit.video');

    Route::post('/edit/video/process', 'AdminController@edit_video_process')->name('admin.save.edit.video');

    Route::get('/view/video', 'AdminController@view_video')->name('admin.view.video');

    Route::post('/add/video', 'AdminController@add_video_process')->name('admin.save.video');

    Route::post('/save_video_payment/{id}', 'AdminController@save_video_payment')->name('admin.save.video-payment');

    Route::get('/delete/video/{id}', 'AdminController@delete_video')->name('admin.delete.video');

    Route::get('/video/approve/{id}', 'AdminController@approve_video')->name('admin.video.approve');

    Route::get('/video/publish-video/{id}', 'AdminController@publish_video')->name('admin.video.publish-video');

    Route::get('/video/decline/{id}', 'AdminController@decline_video')->name('admin.video.decline');

    Route::post('/video/change/position', 'AdminController@video_position')->name('admin.save.video.position');

    // Slider Videos

    Route::get('/slider/video/{id}', 'AdminController@slider_video')->name('admin.slider.video');

    // Banner Videos

    Route::get('/banner/videos', 'AdminController@banner_videos')->name('admin.banner.videos');

    Route::get('/add/banner/video', 'AdminController@add_banner_video')->name('admin.add.banner.video');

    Route::get('/change/banner/video/{id}', 'AdminController@change_banner_video')->name('admin.change.video');
    
    // User Payment details
    Route::get('user/payments' , 'AdminController@user_payments')->name('admin.user.payments');

    Route::get('user/video-payments' , 'AdminController@video_payments')->name('admin.user.video-payments');

    Route::get('/remove_payper_view/{id}', 'AdminController@remove_payper_view')->name('admin.remove_pay_per_view');

    // Settings

    Route::get('settings' , 'AdminController@settings')->name('admin.settings');

    Route::post('save_common_settings' , 'AdminController@save_common_settings')->name('admin.save.common-settings');

    Route::get('payment/settings' , 'AdminController@payment_settings')->name('admin.payment.settings');

    Route::get('theme/settings' , 'AdminController@theme_settings')->name('admin.theme.settings');
    
    Route::post('settings' , 'AdminController@settings_process')->name('admin.save.settings');

    Route::get('settings/email' , 'AdminController@email_settings')->name('admin.email.settings');

    Route::post('settings/email' , 'AdminController@email_settings_process')->name('admin.email.settings.save');

    Route::post('settings/other','AdminController@other_settings')->name('admin.other.settings.save');

    Route::get('help' , 'AdminController@help')->name('admin.help');

    // Pages

    Route::get('/static-pages/index' , 'AdminController@static_pages_index')->name('static_pages.index');

    Route::get('/static-pages/add' , 'AdminController@static_pages_add')->name('static_pages.add');

    Route::post('/static-pages/add' , 'AdminController@static_pages_save')->name('static_pages.add.save');

    Route::get('/static-pages/edit/{id}' , 'AdminController@static_pages_edit')->name('static_pages.edit');

    Route::post('/static-pages/edit' , 'AdminController@static_pages_save')->name('static_pages.edit.save');

    Route::get('/static-pages/delete/{id}' , 'AdminController@static_pages_delete')->name('static_pages.delete');

    
    // Custom Push

    Route::get('/custom/push', 'AdminController@custom_push')->name('admin.push');

    Route::post('/custom/push', 'AdminController@custom_push_process')->name('admin.send.push');


    // Languages
    Route::get('/languages/index', 'LanguageController@languages_index')->name('admin.languages.index'); 

    Route::get('/languages/download/{folder}', 'LanguageController@languages_download')->name('admin.languages.download'); 

    Route::get('/languages/create', 'LanguageController@languages_create')->name('admin.languages.create');
    
    Route::get('/languages/edit/{id}', 'LanguageController@languages_edit')->name('admin.languages.edit');

    Route::get('/languages/status/{id}', 'LanguageController@languages_status')->name('admin.languages.status');   

    Route::post('/languages/save', 'LanguageController@languages_save')->name('admin.languages.save');

    Route::get('/languages/delete/{id}', 'LanguageController@languages_delete')->name('admin.languages.delete');

    Route::get('/languages/set_default_language/{name}', 'LanguageController@set_default_language')->name('admin.languages.set_default_language');


    // subscriptions

    Route::get('/subscriptions', 'AdminController@subscriptions')->name('admin.subscriptions.index');

    Route::get('/user_subscriptions/{id}', 'AdminController@user_subscriptions')->name('admin.subscriptions.plans');

    Route::get('/subscription/save/{s_id}/u_id/{u_id}', 'AdminController@user_subscription_save')->name('admin.subscription.save');

    Route::get('/subscriptions/create', 'AdminController@subscription_create')->name('admin.subscriptions.create');

    Route::get('/subscriptions/edit/{id}', 'AdminController@subscription_edit')->name('admin.subscriptions.edit');

    Route::post('/subscriptions/create', 'AdminController@subscription_save')->name('admin.subscriptions.save');

    Route::get('/subscriptions/delete/{id}', 'AdminController@subscription_delete')->name('admin.subscriptions.delete');

    Route::get('/subscriptions/view/{id}', 'AdminController@subscription_view')->name('admin.subscriptions.view');

    Route::get('/subscriptions/status/{id}', 'AdminController@subscription_status')->name('admin.subscriptions.status');

    Route::get('/subscriptions/popular/status/{id}', 'AdminController@subscription_popular_status')->name('admin.subscriptions.popular.status');

    Route::get('/subscriptions/users/{id}', 'AdminController@subscription_users')->name('admin.subscriptions.users');

    // Coupons

    // Get the add coupon forms
    Route::get('/coupons/add','AdminController@coupon_create')->name('admin.add.coupons');

    // Get the edit coupon forms
    Route::get('/coupons/edit/{id}','AdminController@coupon_edit')->name('admin.edit.coupons');

    // Save the coupon details
    Route::post('/coupons/save','AdminController@coupon_save')->name('admin.save.coupon');

    // Get the list of coupon details
    Route::get('/coupons/list','AdminController@coupon_index')->name('admin.coupon.list');

    //Get the particular coupon details
    Route::get('/coupons/view/{id}','AdminController@coupon_view')->name('admin.coupon.view');

    // Delete the coupon details
    Route::get('/coupons/delete/{id}','AdminController@coupon_delete')->name('admin.delete.coupon');

    //Coupon approve and decline status
    Route::get('/coupon/status','AdminController@coupon_status_change')->name('admin.coupon.status');

    //mail form
    Route::get('/email/form','AdminController@create_mailcamp')->name('admin.add.mailcamp');

    Route::post('/email/form/action','AdminController@email_send_process')->name('admin.email.success');


    // Email Templates,

    Route::get('/create/template', 'AdminController@create_template')->name('admin.create.template');

    Route::get('/edit/template', 'AdminController@edit_template')->name('admin.edit.template');

    Route::post('/save/template', 'AdminController@save_template')->name('admin.save.template');

    Route::get('/view/template', 'AdminController@view_template')->name('admin.view.template');

    Route::get('/templates', 'AdminController@templates')->name('admin.templates');


});

Route::get('/embed', 'UserController@embed_video')->name('embed_video');

Route::get('/g_embed', 'UserController@genre_embed_video')->name('genre_embed_video');

Route::get('/', 'UserController@index')->name('user.dashboard');

Route::get('/single', 'UserController@single_video');

Route::get('/user/searchall' , 'ApplicationController@search_video')->name('search');

Route::any('/user/search' , 'ApplicationController@search_all')->name('search-all');

// Categories and single video 

Route::get('categories', 'UserController@all_categories')->name('user.categories');

Route::get('category/{id}', 'UserController@category_videos')->name('user.category');

Route::get('subcategory/{id}', 'UserController@sub_category_videos')->name('user.sub-category');

Route::get('genre/{id}', 'UserController@genre_videos')->name('user.genre');

Route::get('video/{id}', 'UserController@single_video')->name('user.single');


// Social Login

Route::post('/social', array('as' => 'SocialLogin' , 'uses' => 'SocialAuthController@redirect'));

Route::get('/callback/{provider}', 'SocialAuthController@callback');

Route::get('/user_session_language/{lang}', 'ApplicationController@set_session_language')->name('user_session_language');


Route::group(['middleware' => 'cors'], function(){

    Route::get('login', 'Auth\AuthController@showLoginForm')->name('user.login.form');

    Route::post('login', 'Auth\AuthController@login')->name('user.login.post');

    Route::get('logout', 'Auth\AuthController@logout')->name('user.logout');

    // Registration Routes...
    Route::get('register', 'Auth\AuthController@showRegistrationForm')->name('user.register.form');

    Route::post('register', 'Auth\AuthController@register')->name('user.register.post');

    // Password Reset Routes...
    Route::get('password/reset/{token?}', 'Auth\PasswordController@showResetForm');

    Route::post('password/email', 'Auth\PasswordController@sendResetLinkEmail');

    Route::post('password/reset', 'Auth\PasswordController@reset');

    Route::get('profile', 'UserController@profile')->name('user.profile');

    Route::get('update/profile', 'UserController@update_profile')->name('user.update.profile');

    Route::post('update/profile', 'UserController@profile_save')->name('user.profile.save');

    Route::get('/profile/password', 'UserController@profile_change_password')->name('user.change.password');

    Route::post('/profile/password', 'UserController@profile_save_password')->name('user.profile.password');

    // Delete Account

    Route::get('/delete/account', 'UserController@delete_account')->name('user.delete.account');

    Route::post('/delete/account', 'UserController@delete_account_process')->name('user.delete.account.process');


    Route::get('history', 'UserController@history')->name('user.history');

    Route::get('deleteHistory', 'UserController@delete_history')->name('user.delete.history');

    Route::post('addHistory', 'UserController@add_history')->name('user.add.history');

    // Report Spam Video

    Route::post('markSpamVideo', 'UserController@save_report_video')->name('user.add.spam_video');

    Route::get('unMarkSpamVideo/{id}', 'UserController@remove_report_video')->name('user.remove.report_video');

    Route::get('spamVideos', 'UserController@spam_videos')->name('user.spam-videos');

    Route::get('pay-per-videos', 'UserController@payper_videos')->name('user.pay-per-videos');

    // Wishlist

    Route::post('addWishlist', 'UserController@add_wishlist')->name('user.add.wishlist');

    Route::get('deleteWishlist', 'UserController@delete_wishlist')->name('user.delete.wishlist');

    Route::get('wishlist', 'UserController@wishlist')->name('user.wishlist');

    // Comments

    Route::post('addComment', 'UserController@add_comment')->name('user.add.comment');

    Route::get('comments', 'UserController@comments')->name('user.comments');
    
    // Paypal Payment
   // Route::get('/paypal/{id}','PaypalController@pay')->name('paypal');

        // Paypal Payment
    Route::get('paypal/{id}/{user_id}','PaypalController@pay')->name('paypal');

    Route::get('/user/payment/status','PaypalController@getPaymentStatus')->name('paypalstatus');

    Route::get('/videoPaypal/{id}/{user_id}','PaypalController@videoSubscriptionPay')->name('videoPaypal');

    Route::get('/user/payment/video-status','PaypalController@getVideoPaymentStatus')->name('videoPaypalstatus');

    Route::get('/trending', 'UserController@trending')->name('user.trending');

});


Route::group(['prefix' => 'moderator'], function(){

    Route::get('login', 'Auth\ModeratorAuthController@showLoginForm')->name('moderator.login');

    Route::post('login', 'Auth\ModeratorAuthController@login')->name('moderator.login.post');

    Route::get('logout', 'Auth\ModeratorAuthController@logout')->name('moderator.logout');

    // Registration Routes...
    Route::get('register', 'Auth\ModeratorAuthController@showRegistrationForm');

    Route::post('register', 'Auth\ModeratorAuthController@register');

    // Password Reset Routes...
    Route::get('password/reset/{token?}', 'Auth\ModeratorPasswordController@showResetForm');

    Route::post('password/email', 'Auth\ModeratorPasswordController@sendResetLinkEmail');

    Route::post('password/reset', 'Auth\ModeratorPasswordController@reset');

    Route::get('/', 'ModeratorController@dashboard')->name('moderator.dashboard');

    Route::post('/save_video_payment/{id}', 'ModeratorController@save_video_payment')->name('moderator.save.video-payment');


    Route::get('user/video-payments' , 'ModeratorController@video_payments')->name('moderator.user.video-payments');

    Route::get('/remove_payper_view/{id}', 'ModeratorController@remove_payper_view')->name('moderator.remove_pay_per_view');

    Route::get('revenues', 'ModeratorController@revenues')->name('moderator.revenues');

        // Redeems

    Route::get('redeems/', 'ModeratorController@redeems')->name('moderator.redeems');

    Route::get('send/redeem', 'ModeratorController@send_redeem_request')->name('moderator.redeems.send.request');

    Route::get('redeem/request/cancel/{id?}', 'ModeratorController@redeem_request_cancel')->name('moderator.redeems.request.cancel');



    Route::get('/profile', 'ModeratorController@profile')->name('moderator.profile');

	Route::post('/profile/save', 'ModeratorController@profile_process')->name('moderator.save.profile');

	Route::post('/change/password', 'ModeratorController@change_password')->name('moderator.change.password');


    // Categories

    Route::get('/categories', 'ModeratorController@categories')->name('moderator.categories');

    Route::get('/add/category', 'ModeratorController@add_category')->name('moderator.add.category');

    Route::get('/edit/category/{id}', 'ModeratorController@edit_category')->name('moderator.edit.category');

    Route::post('/add/category', 'ModeratorController@add_category_process')->name('moderator.save.category');

    Route::get('/delete/category', 'ModeratorController@delete_category')->name('moderator.delete.category');

    Route::get('/view/category/{id}', 'ModeratorController@view_category')->name('moderator.view.category');

    // Admin Sub Categories

    Route::get('/subCategories/{category}', 'ModeratorController@sub_categories')->name('moderator.sub_categories');

    Route::get('/add/subCategory/{category}', 'ModeratorController@add_sub_category')->name('moderator.add.sub_category');

    Route::get('/edit/subCategory/{category_id}/{sub_category_id}', 'ModeratorController@edit_sub_category')->name('moderator.edit.sub_category');

    Route::post('/add/subCategory', 'ModeratorController@add_sub_category_process')->name('moderator.save.sub_category');

    Route::get('/delete/subCategory/{id}', 'ModeratorController@delete_sub_category')->name('moderator.delete.sub_category');

    // Genre

    Route::post('/save/genre' , 'ModeratorController@save_genre')->name('moderator.save.genre');

    Route::get('/delete/genre/{id}', 'ModeratorController@delete_genre')->name('moderator.delete.genre');

    // Videos

    Route::get('/videos', 'ModeratorController@videos')->name('moderator.videos');

    Route::get('/add/video', 'ModeratorController@add_video')->name('moderator.add.video');

    Route::get('/edit/video/{id}', 'ModeratorController@edit_video')->name('moderator.edit.video');

    Route::post('/edit/video', 'ModeratorController@edit_video_process')->name('moderator.save.edit.video');

    Route::get('/view/video', 'ModeratorController@view_video')->name('moderator.view.video');

    Route::post('/add/video', 'ModeratorController@add_video_process')->name('moderator.save.video');

    Route::get('/delete/video', 'ModeratorController@delete_video')->name('moderator.delete.video');

});


Route::group(['prefix' => 'userApi', 'middleware' => 'cors'], function(){

    Route::post('/register','UserApiController@register');
    
    Route::post('/login','UserApiController@login');

    Route::get('/userDetails','UserApiController@user_details');

    Route::post('/updateProfile', 'UserApiController@update_profile');

    Route::post('/forgotpassword', 'UserApiController@forgot_password');

    Route::post('/changePassword', 'UserApiController@change_password');

    Route::get('/tokenRenew', 'UserApiController@token_renew');

    Route::post('/deleteAccount', 'UserApiController@delete_account');

    Route::post('/settings', 'UserApiController@settings');


    // Categories And SubCategories

    Route::post('/categories' , 'UserApiController@get_categories');

    Route::post('/subCategories' , 'UserApiController@get_sub_categories');


    // Videos and home

    Route::post('/home' , 'UserApiController@home');
    
    Route::post('/common' , 'UserApiController@common');

    Route::post('/categoryVideos' , 'UserApiController@get_category_videos');

    Route::post('/subCategoryVideos' , 'UserApiController@get_sub_category_videos');

    Route::post('/singleVideo' , 'UserApiController@single_video');

    
    Route::post('/apiSearchVideo' , 'UserApiController@api_search_video')->name('api-search-video');

    Route::post('/searchVideo' , 'UserApiController@search_video')->name('search-video');

    Route::post('/test_search_video' , 'UserApiController@test_search_video');


    // Rating and Reviews

    Route::post('/userRating', 'UserApiController@user_rating');

    // Wish List

    Route::post('/addWishlist', 'UserApiController@add_wishlist');

    Route::post('/getWishlist', 'UserApiController@get_wishlist');

    Route::post('/deleteWishlist', 'UserApiController@delete_wishlist');

    // History

    Route::post('/addHistory', 'UserApiController@add_history');

    Route::post('getHistory', 'UserApiController@get_history');

    Route::post('/deleteHistory', 'UserApiController@delete_history');

    Route::get('/clearHistory', 'UserApiController@clear_history');

    Route::post('/details', 'UserApiController@details');

    Route::post('/active-categories', 'UserApiController@getCategories');

    Route::post('/browse', 'UserApiController@browse');

    Route::post('/active-profiles', 'UserApiController@activeProfiles');

    Route::post('/add-profile', 'UserApiController@addProfile');

    Route::post('/view-sub-profile','UserApiController@view_sub_profile');

    Route::post('/edit-sub-profile','UserApiController@edit_sub_profile');

    Route::post('/delete-sub-profile', 'UserApiController@delete_sub_profile');

    Route::post('/active_plan', 'UserApiController@active_plan');

    Route::post('/subscription_index', 'UserApiController@subscription_index');

    Route::post('/zero_plan', 'UserApiController@zero_plan');

    Route::get('/site_settings' , 'UserApiController@site_settings');

    Route::post('/allPages', 'UserApiController@allPages');

    Route::get('/getPage/{id}', 'UserApiController@getPage');

    Route::get('check_social', 'UserApiController@check_social');

    Route::post('/get-subscription', 'UserApiController@last_subscription');

    Route::post('/genre-video', 'UserApiController@genre_video');

    Route::post('/genre-list', 'UserApiController@genre_list');

    Route::get('/searchall' , 'UserApiController@searchAll');

    Route::post('/notifications', 'UserApiController@notifications');

    Route::post('/red-notifications', 'UserApiController@red_notifications');

    Route::post('subscribed_plans', 'UserApiController@subscribed_plans');


    Route::post('stripe_payment_video', 'UserApiController@stripe_payment_video');

    Route::post('card_details', 'UserApiController@card_details');

    Route::post('payment_card_add', 'UserApiController@payment_card_add');

    Route::post('default_card', 'UserApiController@default_card');

    Route::post('delete_card', 'UserApiController@delete_card');

    Route::post('subscription_plans', 'UserApiController@subscription_plans');

    Route::post('subscribedPlans', 'UserApiController@subscribedPlans');

    Route::post('/stripe_payment', 'UserApiController@stripe_payment');
    
    Route::post('pay_now', 'UserApiController@pay_now');

    Route::post('/like_video', 'UserApiController@likeVideo');

    Route::post('/dis_like_video', 'UserApiController@disLikeVideo');

    Route::post('/add_spam', 'UserApiController@add_spam');

    Route::get('/spam-reasons', 'UserApiController@reasons');

    Route::post('remove_spam', 'UserApiController@remove_spam');

    Route::post('spam_videos', 'UserApiController@spam_videos');

    Route::post('stripe_ppv', 'UserApiController@stripe_ppv');

    Route::post('ppv_end', 'UserApiController@ppv_end');

    Route::post('paypal_ppv', 'UserApiController@paypal_ppv');

    Route::post('keyBasedDetails', 'UserApiController@keyBasedDetails');

    Route::post('plan_detail', 'UserApiController@plan_detail');

    Route::post('logout', 'UserApiController@logout');

    Route::post('check_token_valid', 'UserApiController@check_token_valid');

    Route::post('ppv_list', 'UserApiController@ppv_list');


    // Continue watching Video

    Route::post('continue/videos', 'UserApiController@continue_watching_videos');

    Route::any('save/watching/video', 'UserApiController@save_continue_watching_video');

    Route::post('/oncomplete/video', 'UserApiController@on_complete_video');

    // Enable / Disable Notifications

    Route::post('/email/notification', 'UserApiController@email_notification');

    // Coupons route
    // Coupon apply option for subscription
    Route::post('coupon/apply/subscription','UserApiController@coupon_apply_subscription');

    Route::post('coupon/apply/vidoes','UserApiController@coupon_apply_videos');
});

Route::get('convert', 'TestVideo@convert');

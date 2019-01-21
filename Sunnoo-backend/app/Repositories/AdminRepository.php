<?php

namespace App\Repositories;

use App\Repositories\UserRepository as UserRepo;
use App\Repositories\ProviderRepository as ProviderRepo;
use App\Repositories\ModeratorRepository as ModeratorRepo;
use App\Repositories\CommonRepository as CommonRepo;
use App\Helpers\Helper;
use Validator;
use App\Language;
use App\User;
use Hash;
use Log;

class AdminRepository
{

	public static function languages_save($request) {

		$validator = Validator::make($request->all(),[
                'folder_name' => 'required|max:4',
                'language'=>'required|max:64',
        ]);
        
        if($validator->fails()) {

            $error_messages = implode(',', $validator->messages()->all());

            return  ['success' => false , 'error' => $error_messages];

        } else {

            $model = ($request->id != '') ? Language::find($request->id) : new Language;

            $lang = ($request->id != '') ? $model->folder_name : '';

            $model->folder_name = $request->folder_name;

            $model->language = $request->language;

            $model->status = DEFAULT_TRUE;

            if($request->hasFile('file')) {

            	// Read File Length

            	$originallength = readFileLength(base_path().'/resources/lang/en/messages.php');

            	$length = readFileLength($_FILES['file']['tmp_name']);

            	if ($originallength != $length) {
            		return ['success' => false, 'error'=> Helper::get_error_message(162), 'error_code'=>162];
            	}

            	if ($model->id != '') {
            		$boolean = ($lang != $request->folder_name) ? DEFAULT_TRUE : DEFAULT_FALSE;

                	Helper::delete_language_files($lang, $boolean);
                }

               	Helper::upload_language_file($model->folder_name, $request->file);

            } else {

            	if($lang != $request->folder_name)  {
            		$current_path=base_path('resources/lang/'.$lang);
        			$new_path=base_path('resources/lang/'.$request->folder_name);
            		rename($current_path,$new_path);
            	}
            }

            $model->save();

            if($model) {
            	$response_array = ['success' => true, 'message'=> $request->id != '' ? tr('language_update_success') : tr('language_create_success')];
            } else {
                $response_array = ['success' => false , 'error' => tr('something_error')];
            }
        }
        return $response_array;
	}
}

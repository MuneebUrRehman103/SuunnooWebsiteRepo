<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use App\Repositories\AdminRepository as AdminRepo;

use App\Language;

use App\Settings;

class LanguageController extends Controller
{
    public function languages_index() {
        // Load Lanugages
        $model = Language::get();

        return view('admin.languages.index')->withPage('languages')->with('data', $model)->with('sub_page','');
    }

    public function languages_download($folder) {
        //PDF file is stored under project/public/download/info.pdf
        $file= base_path(). "/resources/lang/".$folder.'/messages.php';

        $headers = array(
                  'Content-Type: application/x-php',
                );

        return response()->download($file, 'messages.php', $headers);
    }



    public function languages_create(Request $request) {

        return view('admin.languages.create')->withPage('languages')->with('sub_page','create_language');
    }

    public function languages_edit($id) {

        // Load Coupon Model
        $model = Language::where('id',$id)->first();

        return view('admin.languages.edit')->withPage('languages')->with('model', $model)->with('sub_page','edit_language');
    }


    public function languages_save(Request $request) {
        $model = AdminRepo::languages_save($request);
        if ($model['success'] == true) {
            return redirect(route('admin.languages.index'))->with('flash_success', $model['message']);
        } else {
             return back()->with('flash_error', $model['error']);
        }
    
    }

    public function languages_delete($id) {

        $model = Language::where('id', $id)->first();

        if($model) {

            if ($model->delete()) {

                $settings = Settings::where('key', 'default_lang')->first();

                if($settings) {

                    $settings->value = 'en';

                    if($settings->save()) {

                        return back()->with('flash_success',tr('language_delete_success'));

                    }

                }  

            }
        }
        
        return back()->with('flash_error',tr('admin_not_error'));
   
    }


    public function languages_status($id) {

        if($data = Language::where('id' , $id)->first()) {

            $data->status  = $data->status ? 0 : 1;

            $data->save();

            return back()->with('flash_success' , $data->status ? tr('language_active_success') : tr('language_inactive_success'));

        } else {

            return back()->with('flash_error',tr('admin_not_error'));
            
        }
    }

    public function set_default_language($name) {
        // Load Setting Table
        $model = Settings::where('key','default_lang')->first();

        if ($model) {

            $model->value = $name;

            $model->save();

            if($model) {
                $fp = fopen(base_path() .'/config/new_config.php' , 'w');
                fwrite($fp, "<?php return array( 'locale' => '".$name."', 'fallback_locale' => '".$name."');?>");
                fclose($fp);

                \Log::info("Key : ".config('app.locale'));

                return back()->with('flash_success' , tr('set_default_language_success'))->with('flash_language', true);
            }
        }  

        return back()->with('flash_error',tr('admin_not_error'));
    }
}

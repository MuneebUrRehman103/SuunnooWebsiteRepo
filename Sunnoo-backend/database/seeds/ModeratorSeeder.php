<?php

use Illuminate\Database\Seeder;

use App\Helpers\Helper;

class ModeratorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //DB::table('moderators')->delete();
    	DB::table('moderators')->insert([
    		[
		        'name' => 'Moderator',
		        'email' => 'moderator@streamview.com',
		        'password' => \Hash::make('123456'),
		        'token' => Helper::generate_token(),
                'token_expiry' => Helper::generate_token_expiry(),
                'is_activated'=>1,
		        'picture' =>"http://adminview.streamhash.com/placeholder.png",
		        'created_at' => date('Y-m-d H:i:s'),
		        'updated_at' => date('Y-m-d H:i:s')
		    ]
		]);
    }
}

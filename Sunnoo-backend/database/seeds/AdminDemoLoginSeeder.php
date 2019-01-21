<?php

use Illuminate\Database\Seeder;

class AdminDemoLoginSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if(Schema::hasTable('settings')) {
            
    		$login_data = DB::table('settings')->where('key' ,'demo_admin_email')->first();
    		$password_data = DB::table('settings')->where('key' ,'demo_admin_password')->first();

    		if(!$login_data &&  !$password_data) {

	         	DB::table('settings')->insert([

	         		[
				        'key' => 'demo_admin_email',
				        'value' => '',
				        'created_at' => date('Y-m-d H:i:s'),
				        'updated_at' => date('Y-m-d H:i:s')
				    ],

				    [
				        'key' => 'demo_admin_password',
				        'value' => "",
				        'created_at' => date('Y-m-d H:i:s'),
				        'updated_at' => date('Y-m-d H:i:s')
				    ],
		    		
				]);
			}
		}
    }
}

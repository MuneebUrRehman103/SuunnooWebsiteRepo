<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Jobs\ConvertVideoForStreaming;
class TestVideo extends Controller
{
   public function convert(){
	$this->dispatch(new ConvertVideoForStreaming());
  }
}

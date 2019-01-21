<?php

namespace App\Jobs;

use App\Jobs\Job;
use FFMpeg;
use FFMpeg\Format\Video\X264;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use File;
use App\AdminVideo;
use App\Helpers\Helper;
use App\Notification;
use Log; 

class StreamviewCompressVideo extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $inputFile;
    protected $local_url;
    protected $videoId;
    protected $video_type;
    protected $file_name;
    protected $send_notification;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($inputFile, $local_url, $video_type, $videoId, $file_name, $send_notification)
    {
       Log::info("Inside Construct");
       $this->inputFile = $inputFile;
       $this->local_url = $local_url;
       $this->videoId = $videoId;
       $this->video_type = $video_type;
       $this->file_name = $file_name;
       $this->send_notification = $send_notification;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("Inside Queue Videos : ". 'Success');
        // Load Video Model
        $video = AdminVideo::where('id', $this->videoId)->first();
        $attributes = readFileName($this->inputFile);
        Log::info("attributes : ". print_r($attributes, true));
        if($attributes) {
            // Get Video Resolutions
            $resolutions = $video->video_resolutions ? explode(',', $video->video_resolutions) : [];
            $array_resolutions = $video_resize_path = $pathnames = [];
            $compress = false;

            // create some video formats...
        $lowBitrateFormat  = (new X264)->setKiloBitrate(500);
        $midBitrateFormat  = (new X264)->setKiloBitrate(1500);
        $highBitrateFormat = (new X264)->setKiloBitrate(3000);

	// open the uploaded video from the right disk...
        FFMpeg::open($this->inputFile)
            ->exportForHLS()
            ->addFormat($lowBitrateFormat)
            ->addFormat($midBitrateFormat)
            ->addFormat($highBitrateFormat)
            ->save(public_path().'/uploads/videos/original/'.$this->file_name . '.m3u8');

/*
            foreach ($resolutions as $key => $solution) {
                $exp = explode('x', $solution);
                Log::info("Resoltuion : ". print_r($exp, true));
                // Explode $solution value
                $getwidth = (count($exp) == 2) ? $exp[0] : 0;
                if ($getwidth < $attributes['width']) {
                    $compress = true;
                    $FFmpeg = new \FFmpeg;
                    $FFmpeg
                    ->input($this->inputFile)
                    ->size($solution)
                    ->vcodec('h264')
                    ->constantRateFactor('28')
                    ->output(public_path().'/uploads/videos/original/'.$solution.$this->local_url)
                    ->ready();

                    Log::info('Output'.public_path().'/uploads/videos/original/'.$solution.$this->local_url);
                    $array_resolutions[] = $solution;
                    Log::info('Url'.Helper::web_url().'/uploads/videos/original/'.$solution.$this->local_url);
                    $video_resize_path[] = Helper::web_url().'/uploads/videos/original/'.$solution.$this->local_url;
                    $pathnames[] = $solution.$this->local_url;
                }
            }
*/
            Log::info("Before saving Compress Video : ".$this->video_type);
            if ($this->video_type == MAIN_VIDEO) {
                $video->compress_status = 1;
                $video->video_resolutions = ($array_resolutions) ? implode(',', $array_resolutions) : null;
                $video->video_resize_path = ($video_resize_path) ? implode(',', $video_resize_path) : null;
            } else {
                $video->trailer_compress_status = 1;
                $video->trailer_video_resolutions = ($array_resolutions) ? implode(',', $array_resolutions) : null;
                $video->trailer_resize_path = ($video_resize_path) ? implode(',', $video_resize_path) : null;
            }
            if ($video->compress_status == 1 && $video->trailer_compress_status == 1) {
                $video->is_approved = DEFAULT_TRUE;
                Log::info("Send Notification ".$this->send_notification);
                if ($this->send_notification) {
                    Notification::save_notification($video->id);
                }
            }

/*
            Log::info("AArray Resolutions : ".print_r($array_resolutions, true));
            if ($array_resolutions) {
                $myfile = fopen(public_path().'/uploads/smil/'.$this->file_name.'.smil', "w");
                $txt = '<smil>
                  <head>
                    <meta base="'.\Setting::get('streaming_url').'" />
                  </head>
                  <body>
                    <switch>';
                    $txt .= '<video src="'.$this->local_url.'" height="'.$attributes['height'].'" width="'.$attributes['width'].'" />';
                    foreach ($pathnames as $i => $value) {
                        $resoltionsplit = explode('x', $array_resolutions[$i]);
                        if (count($resoltionsplit))
                        $txt .= '<video src="'.$value.'" height="'.$resoltionsplit[1].'" width="'.$resoltionsplit[0].'" />';
                    }
                $txt .= '</switch>
                  </body>
                </smil>';
                fwrite($myfile, $txt);
                fclose($myfile);
            }
*/
            Log::info("Compress Video : ".$this->video_type);
            Log::info("Compress Status : ".$video->compress_status);
            Log::info("Trailer Compress Status : ".$video->trailer_compress_status);
            $video->save();
        } else {
            $video->compress_status = DEFAULT_TRUE;
            $video->trailer_compress_status = DEFAULT_TRUE;
            $video->save();
        }
    }
}

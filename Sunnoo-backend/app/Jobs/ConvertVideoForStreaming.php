<?php

namespace App\Jobs;

use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use FFMpeg;
use FFMpeg\Format\Video\X264;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;

class ConvertVideoForStreaming implements ShouldQueue {

//    use InteractsWithQueue, SerializesModels;
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** use Illuminate\Bus\Queueable; * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $lowBitrateFormat  = (new X264)->setKiloBitrate(500);
	$midBitrateFormat  = (new X264)->setKiloBitrate(1500);
	$highBitrateFormat = (new X264)->setKiloBitrate(3000);
	
	FFMpeg::fromDisk('videos_disk')
		->open('trailer.mp4')
		->exportForHLS()
		->toDisk('streamable_videos')
		->addFormat($lowBitrateFormat)
		->addFormat($midBitrateFormat)
		->addFormat($highBitrateFormat)
		->save('converted.m3u8');
    }
}

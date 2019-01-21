@extends('layouts.moderator')

@section('title', tr('view_video'))

@section('content-header', tr('view_video'))

@section('styles')

<style>
hr {
    margin-bottom: 10px;
    margin-top: 10px;
}
</style>

@endsection

@section('breadcrumb')
    <li><a href="{{route('moderator.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
    <li><a href="{{route('moderator.videos')}}"><i class="fa fa-video-camera"></i> {{tr('videos')}}</a></li>
    <li class="active">{{tr('video')}}</li>
@endsection 


@section('content')

    <?php $url = $trailer_url = ""; ?>        

    <div class="row">

        @include('notification.notify')
        <div class="col-lg-12">
            <div class="box box-primary">
            <div class="box-header with-border">
                <div class='pull-left'>
                    <h3 class="box-title"> <b>{{$video->title}}</b></h3>
                    <br>
                    <span style="margin-left:0px" class="description">Created Time - {{$video->video_date}}</span>
                </div>
                <div class='pull-right'>
                    @if ($video->compress_status == 0 || $video->trailer_compress_status == 0) <span class="label label-danger">{{tr('compress')}}</span>@endif
                    <a href="{{route('moderator.edit.video' , array('id' => $video->video_id))}}" class="btn btn-sm btn-warning"><i class="fa fa-pencil"></i> {{tr('edit')}}</a>
                </div>
                <div class="clearfix"></div>
            </div>
            <!-- /.box-header -->
            <div class="box-body">

              <div class="row">
                  <div class="col-lg-12 row">

                    <div class="col-lg-4">
                        <div class="box-body box-profile">
                        <h4>{{tr('details')}}</h4>
                            <ul class="list-group list-group-unbordered">
                                <li class="list-group-item">
                                  <b><i class="fa fa-suitcase margin-r-5"></i>{{tr('category')}}</b> <a class="pull-right">{{$video->category_name}}</a>
                                </li>
                                <li class="list-group-item">
                                  <b><i class="fa fa-suitcase margin-r-5"></i>{{tr('sub_category')}}</b> <a class="pull-right">{{$video->sub_category_name}}</a>
                                </li>
                                <li class="list-group-item">
                                  <b><i class="fa fa-video-camera margin-r-5"></i>{{tr('video_type')}}</b> <a class="pull-right">
                                    @if($video->video_type == 1)
                                        {{tr('video_upload_link')}}
                                    @endif
                                    @if($video->video_type == 2)
                                        {{tr('youtube')}}
                                    @endif
                                    @if($video->video_type == 3)
                                        {{tr('other_link')}}
                                    @endif
                                    </a>
                                </li>
                                @if ($video->video_upload_type == 1 || $video->video_upload_type == 2)
                                <li class="list-group-item">
                                  <b><i class="fa fa-video-camera margin-r-5"></i>{{tr('video_upload_type')}}</b> <a class="pull-right"> 
                                        @if($video->video_upload_type == 1)
                                            {{tr('s3')}}
                                        @endif
                                        @if($video->video_upload_type == 2)
                                            {{tr('direct')}}
                                        @endif 
                                    </a>
                                </li>
                                @endif
                                <li class="list-group-item">
                                  <b><i class="fa fa-clock-o margin-r-5"></i>{{tr('duration')}}</b> <a class="pull-right">{{$video->duration}}</a>
                                </li>
                                <li class="list-group-item">
                                  <b><i class="fa fa-star margin-r-5"></i>{{tr('ratings')}}</b> <a class="pull-right">
                                      <span class="starRating-view">
                                        <input id="rating5" type="radio" name="ratings" value="5" @if($video->ratings == 5) checked @endif>
                                        <label for="rating5">5</label>

                                        <input id="rating4" type="radio" name="ratings" value="4" @if($video->ratings == 4) checked @endif>
                                        <label for="rating4">4</label>

                                        <input id="rating3" type="radio" name="ratings" value="3" @if($video->ratings == 3) checked @endif>
                                        <label for="rating3">3</label>

                                        <input id="rating2" type="radio" name="ratings" value="2" @if($video->ratings == 2) checked @endif>
                                        <label for="rating2">2</label>

                                        <input id="rating1" type="radio" name="ratings" value="1" @if($video->ratings == 1) checked @endif>
                                        <label for="rating1">1</label>
                                    </span>
                                  </a>
                                </li>

                                 <li class="list-group-item">
                                  <b><i class="fa fa-eye margin-r-5"></i>{{tr('viewers_cnt')}}</b> <a class="pull-right">{{$video->watch_count ? $video->watch_count : 0}}</a>
                                </li>

                                 <li class="list-group-item">
                                  <b><i class="fa fa-money margin-r-5"></i>{{tr('watch_count_revenue')}}</b> <a class="pull-right">${{$video->redeem_amount ? $video->redeem_amount : 0}}</a>
                                </li>

                                <li class="list-group-item">
                                  <b><i class="fa fa-money margin-r-5"></i>{{tr('ppv_revenue')}}</b> <a class="pull-right">${{$video->user_amount ? $video->user_amount : 0}}</a>
                                </li>

                                
                                <li class="list-group-item">
                                    <b><i class="fa fa-thumbs-up margin-r-5"></i>{{tr('likes')}}</b> <a class="pull-right">{{number_format_short($video->getScopeLikeCount->count())}}&nbsp;</a>
                                </li> 

                                <li class="list-group-item">
                                    <b><i class="fa fa-thumbs-down margin-r-5"></i>{{tr('dislikes')}}</b> <a class="pull-right">{{number_format_short($video->getScopeDisLikeCount->count())}}&nbsp;</a>
                                </li>    
                                <li class="list-group-item">
                                    <b><i class="fa fa-male margin-r-5"></i>{{tr('age')}}</b> <a class="pull-right">{{$video->age}}&nbsp;</a>
                                </li> 
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <strong><i class="fa fa-file-picture-o margin-r-5"></i> {{tr('images')}}</strong>

                        <div class="row margin-bottom" style="margin-top: 10px;">
                            <div class="col-lg-6">
                              <img alt="Photo" src="{{isset($video->default_image) ? $video->default_image : ''}}" class="img-responsive" style="width:100%;height:250px;">
                            </div>
                            <!-- /.col -->
                            <div class="col-lg-6">
                              <div class="row">
                                 @foreach($video_images as $i => $image)
                                <div class="col-lg-6">
                                  <img alt="Photo" src="{{$image->image}}" class="img-responsive" style="width:100%;height:130px">
                                  <br>
                                </div>
                                @endforeach
                                @if ($video->banner_image == 1) 
                                    <img alt="Photo" src="{{$video->banner_image}}" class="img-responsive" style="width:100%;height:130px">
                                @endif
                                <!-- /.col -->
                              </div>
                            </div>
                              <!-- /.row -->
                        </div>

                    </div>
                    
                  </div>
                </div>

              <hr>

              <div class="row">
                  <div class="col-lg-6">
                      <strong><i class="fa fa-file-text-o margin-r-5"></i> {{tr('description')}}</strong>

                      <p style="margin-top: 10px;">{{$video->description}}.</p>
                </div>
                 <div class="col-lg-6">
                      <strong><i class="fa fa-file-text-o margin-r-5"></i> {{tr('reviews')}}</strong>

                      <p style="margin-top: 10px;">{{$video->reviews}}.</p>
                </div>
            </div>

              <hr>

              @if($video->details)

              <div class="row">
                <div class="col-lg-12">

                          <strong><i class="fa fa-file-text-o margin-r-5"></i> {{tr('details')}}</strong>

                          <p style="margin-top: 10px;"><?= $video->details ?></p>
                </div>
             </div>

              <hr>

              @endif

              @if(Setting::get('is_payper_view'))

              @if($video->amount > 0)

              <h4 style="margin-left: 15px;font-weight: bold;">{{tr('pay_per_view')}}</h4>
              <div class="row">

                <div class="col-lg-12">
                    <div class="col-lg-4">
                        <strong><i class="fa fa-file-text-o margin-r-5"></i> {{tr('type_of_user')}}</strong>

                        <p style="margin-top: 10px;">
                            @if($video->type_of_user == NORMAL_USER)
                                {{tr('normal_user')}}
                            @elseif($video->type_of_user == PAID_USER)
                                {{tr('paid_user')}}
                            @elseif($video->type_of_user == BOTH_USERS) 
                                {{tr('both_user')}}
                            @else
                                -
                            @endif
                        </p>
                    </div>
                    <div class="col-lg-4">
                        <strong><i class="fa fa-file-text-o margin-r-5"></i> {{tr('type_of_subscription')}}</strong>

                        <p style="margin-top: 10px;">
                            @if($video->type_of_subscription == ONE_TIME_PAYMENT)
                                {{tr('one_time_payment')}}
                            @elseif($video->type_of_subscription == RECURRING_PAYMENT)
                                {{tr('recurring_payment')}}
                            @else
                                -
                            @endif
                        </p>
                    </div>
                    <div class="col-lg-4">
                        <strong><i class="fa fa-file-text-o margin-r-5"></i> {{tr('amount')}}</strong>

                        <p style="margin-top: 10px;">
                           {{$video->amount}}
                        </p>
                    </div>
                </div>
              </div>

              @endif

              <hr>
              @endif
              <div class="row">
                  <div class="col-lg-12">

                        @if($video->trailer_video)
                       <div class="col-lg-6">

                            <strong><i class="fa fa-video-camera margin-r-5"></i> {{tr('trailer_video')}}</strong>
			    <br>

                            <br>

                            <?php /*<b>{{tr('embed_link')}} : </b> <a href="{{route('embed_video', array('v_t'=>1, 'u_id'=>$video->unique_id))}}" target="_blank">{{route('embed_video', array('v_t'=>1, 'u_id'=>$video->unique_id))}}</a> */?>

                            <div class="clearfix"></div>

                            <br>

                             <div class="image" id="trailer_video_setup_error" style="display:none">
                                <img src="{{asset('error.jpg')}}" alt="{{Setting::get('site_name')}}" style="width: 100%">
                            </div>

                            <div class="">
                                @if($video->video_upload_type == 1)
                                <?php $trailer_url = $video->trailer_video; ?>
                                    <div id="trailer-video-player"></div>
                                @else

                                    @if(check_valid_url($video->trailer_video))

                                        <?php $trailer_url = (Setting::get('streaming_url')) ? Setting::get('streaming_url').get_video_end($video->trailer_video) : $video->trailer_video; ?>

                                        <div id="trailer-video-player"></div>

                                    @else
                                        <div class="image">
                                            <img src="{{asset('error.jpg')}}" alt="{{Setting::get('site_name')}}" style="width: 100%">
                                        </div>
                                    @endif

                                @endif
                            </div>
                            <div class="embed-responsive embed-responsive-16by9" id="flash_error_display_trailer" style="display: none;">
                               <div style="width: 100%;background: black; color:#fff;height:350px;">
                                     <div style="text-align: center;padding-top:25%">{{tr('flash_miss_error')}} <a target="_blank" href="http://get.adobe.com/flashplayer/" class="underline">{{tr('adobe')}}</a>.</div>
                               </div>
                            </div>
                        </div>
                        @endif
                        <div class="col-lg-6">

                            <strong><i class="fa fa-video-camera margin-r-5"></i> {{tr('full_video')}}</strong>

                            <br>

                            <br>

                            <?php /*<b>{{tr('embed_link')}} : </b> <a href="{{route('embed_video', array('v_t'=>2, 'u_id'=>$video->unique_id))}}" target="_blank">{{route('embed_video', array('v_t'=>2, 'u_id'=>$video->unique_id))}}</a> */ ?>

                            <div class="clearfix"></div>

                            <br>

                            <div class="image" id="main_video_setup_error" style="display:none">
                                <img src="{{asset('error.jpg')}}" alt="{{Setting::get('site_name')}}" style="width: 100%">
                            </div>

                            <div class="">
                                    @if($video->video_upload_type == 1)
                                    <?php $url = $video->video; ?>
                                    <div id="main-video-player"></div>
                                @else
                                    @if(check_valid_url($video->video))

                                        <?php $url = (Setting::get('streaming_url')) ? Setting::get('streaming_url').get_video_end($video->video) : $video->video; ?>
                                        <div id="main-video-player"></div>
                                    @else
                                        <div class="image">
                                            <img src="{{asset('error.jpg')}}" alt="{{Setting::get('site_name')}}" style="width: 100%">
                                        </div>
                                    @endif

                                @endif
                            </div>
                            <div class="embed-responsive embed-responsive-16by9" id="flash_error_display_main" style="display: none;">
                               <div style="width: 100%;background: black; color:#fff;height:350px;">
                                     <div style="text-align: center;padding-top:25%">{{tr('flash_miss_error')}}<a target="_blank" href="http://get.adobe.com/flashplayer/" class="underline">{{tr('adobe')}}</a>.</div>
                               </div>
                            </div>
                        </div>
                    </div>
                </div>
            <!-- /.box-body -->
            </div>
        </div>
    </div>
    </div>
@endsection

@section('scripts')
    
     <script src="{{asset('jwplayer/jwplayer.js')}}"></script>

    <script>jwplayer.key="{{Setting::get('JWPLAYER_KEY')}}";</script>

    <script type="text/javascript">
        
        jQuery(document).ready(function(){

                  var is_mobile = false;

                  var isMobile = {
                      Android: function() {
                          return navigator.userAgent.match(/Android/i);
                      },
                      BlackBerry: function() {
                          return navigator.userAgent.match(/BlackBerry/i);
                      },
                      iOS: function() {
                          return navigator.userAgent.match(/iPhone|iPad|iPod/i);
                      },
                      Opera: function() {
                          return navigator.userAgent.match(/Opera Mini/i);
                      },
                      Windows: function() {
                          return navigator.userAgent.match(/IEMobile/i);
                      },
                      any: function() {
                          return (isMobile.Android() || isMobile.BlackBerry() || isMobile.iOS() || isMobile.Opera() || isMobile.Windows());
                      }
                  };


                  function getBrowser() {

                      // Opera 8.0+
                      var isOpera = (!!window.opr && !!opr.addons) || !!window.opera || navigator.userAgent.indexOf(' OPR/') >= 0;

                      // Firefox 1.0+
                      var isFirefox = typeof InstallTrigger !== 'undefined';

                      // Safari 3.0+ "[object HTMLElementConstructor]" 
                      var isSafari = /constructor/i.test(window.HTMLElement) || (function (p) { return p.toString() === "[object SafariRemoteNotification]"; })(!window['safari'] || safari.pushNotification);

                      // Internet Explorer 6-11
                      var isIE = /*@cc_on!@*/false || !!document.documentMode;

                      // Edge 20+
                      var isEdge = !isIE && !!window.StyleMedia;

                      // Chrome 1+
                      var isChrome = !!window.chrome && !!window.chrome.webstore;

                      // Blink engine detection
                      var isBlink = (isChrome || isOpera) && !!window.CSS;

                      var b_n = '';

                      switch(true) {

                          case isFirefox :

                                  b_n = "Firefox";

                                  break;
                          case isChrome :

                                  b_n = "Chrome";

                                  break;

                          case isSafari :

                                  b_n = "Safari";

                                  break;
                          case isOpera :

                                  b_n = "Opera";

                                  break;

                          case isIE :

                                  b_n = "IE";

                                  break;

                          case isEdge : 

                                  b_n = "Edge";

                                  break;

                          case isBlink : 

                                  b_n = "Blink";

                                  break;

                          default :

                                  b_n = "Unknown";

                                  break;

                      }

                      return b_n;

                  }


                  if(isMobile.any()) {

                      var is_mobile = true;

                  }


                  var browser = getBrowser();


                  if ((browser == 'Safari') || (browser == 'Opera') || is_mobile) {

                    var video = "{{$ios_video}}";

                    var trailer_video = "{{$ios_trailer_video}}";

                  } else {

                    var video = "{{$videoStreamUrl}}";

                    var trailer_video = "{{$trailerstreamUrl}}";

                  }

                console.log("Video " +video);
                    
                console.log("Trailer "+trailer_video);

                @if($url)

                    var playerInstance = jwplayer("main-video-player");


                    @if($videoStreamUrl || $ios_video) 

                        playerInstance.setup({
                            file: video,
                            image: "{{$video->default_image}}",
                            width: "100%",
                            aspectratio: "16:9",
                            primary: "flash",
                            controls : true,
                            "controlbar.idlehide" : false,
                            controlBarMode:'floating',
                            "controls": {
                              "enableFullscreen": false,
                              "enablePlay": false,
                              "enablePause": false,
                              "enableMute": true,
                              "enableVolume": true
                            },
                            // autostart : true,
                            "sharing": {
                                "sites": ["reddit","facebook","twitter"]
                              },
                              tracks : [{
                                  file : "{{$video->video_subtitle}}",
                                  kind : "captions",
                                  default : true,
                                }]
                        });
                    @else 
                        var videoPath = "{{$videoPath}}";
                        var videoPixels = "{{$video_pixels}}";

                        var path = [];

                        var splitVideo = videoPath.split(',');

                        var splitVideoPixel = videoPixels.split(',');


                        for (var i = 0 ; i < splitVideo.length; i++) {
                            path.push({file : splitVideo[i], label : splitVideoPixel[i]});
                        }
                        playerInstance.setup({
                            sources: path,
                            image: "{{$video->default_image}}",
                            width: "100%",
                            type:'mp4',
                            aspectratio: "16:9",
                            primary: "flash",
                            controls : true,
                            "controlbar.idlehide" : false,
                            controlBarMode:'floating',
                            "controls": {
                              "enableFullscreen": false,
                              "enablePlay": false,
                              "enablePause": false,
                              "enableMute": true,
                              "enableVolume": true
                            },
                            // autostart : true,
                            "sharing": {
                                "sites": ["reddit","facebook","twitter"]
                              },
                              tracks : [{
                                  file : "{{$video->video_subtitle}}",
                                  kind : "captions",
                                  default : true,
                                }]
                        });

                        
                    
                    @endif

                    playerInstance.on('setupError', function() {

                                jQuery("#main-video-player").css("display", "none");
                               // jQuery('#trailer_video_setup_error').hide();
                               

                                var hasFlash = false;
                                try {
                                    var fo = new ActiveXObject('ShockwaveFlash.ShockwaveFlash');
                                    if (fo) {
                                        hasFlash = true;
                                    }
                                } catch (e) {
                                    if (navigator.mimeTypes
                                            && navigator.mimeTypes['application/x-shockwave-flash'] != undefined
                                            && navigator.mimeTypes['application/x-shockwave-flash'].enabledPlugin) {
                                        hasFlash = true;
                                    }
                                }

                                if (hasFlash == false) {
                                    jQuery('#flash_error_display_main').show();
                                    return false;
                                }

                                jQuery('#main_video_setup_error').css("display", "block");

                                confirm('The video format is not supported in this browser. Please option some other browser.');

                            });

                @endif

                @if($trailer_url)

                    var playerInstance = jwplayer("trailer-video-player");

                    @if($trailerstreamUrl || $ios_trailer_video)

                            playerInstance.setup({
                                file : trailer_video,
                                image: "{{$video->default_image}}",
                                width: "100%",
                                aspectratio: "16:9",
                                primary: "flash",
                                tracks : [{
                                  file : "{{$video->trailer_subtitle}}",
                                  kind : "captions",
                                  default : true,
                                }]
                            });

                    @else

                            var trailerVideoPath = "{{$trailer_video_path}}";
                            var trailerVideoPixels = "{{$trailer_pixels}}";

                            var trailerPath = [];

                            var splitTrailer = trailerVideoPath.split(',');

                            var splitTrailerPixel = trailerVideoPixels.split(',');


                            for (var i = 0 ; i < splitTrailer.length; i++) {

                                trailerPath.push({file : splitTrailer[i], label : splitTrailerPixel[i]});
                            }

                            playerInstance.setup({
                                sources : trailerPath,
                                image: "{{$video->default_image}}",
                                width: "100%",

                                aspectratio: "16:9",
                                primary: "flash",
                                tracks : [{
                                  file : "{{$video->trailer_subtitle}}",
                                  kind : "captions",
                                  default : true,
                                }]
                            });

                    @endif

                    playerInstance.on('setupError', function() {

                                jQuery("#trailer-video-player").css("display", "none");
                               // jQuery('#trailer_video_setup_error').hide();
                               

                                var hasFlash = false;
                                try {
                                    var fo = new ActiveXObject('ShockwaveFlash.ShockwaveFlash');
                                    if (fo) {
                                        hasFlash = true;
                                    }
                                } catch (e) {
                                    if (navigator.mimeTypes
                                            && navigator.mimeTypes['application/x-shockwave-flash'] != undefined
                                            && navigator.mimeTypes['application/x-shockwave-flash'].enabledPlugin) {
                                        hasFlash = true;
                                    }
                                }

                                if (hasFlash == false) {
                                    jQuery('#flash_error_display_trailer').show();
                                    return false;
                                }

                                jQuery('#trailer_video_setup_error').css("display", "block");

                                confirm('The video format is not supported in this browser. Please option some other browser.');
                            
                            });
                @endif
        });

    </script>

@endsection


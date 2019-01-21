@extends('layouts.admin')

@section('title', tr('view_genre'))

@section('content-header', tr('view_genre'))

@section('breadcrumb')
    <li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
    <li><a href="{{route('admin.categories')}}"><i class="fa fa-suitcase"></i>{{tr('categories')}}</a></li>
    <li><a href="{{route('admin.sub_categories', array('category' => $genre->category_id))}}"><i class="fa fa-suitcase"></i> {{tr('sub_categories')}}</a></li>
    <li><a href="{{route('admin.genres' , array('sub_category' => $genre->sub_category_id))}}"><i class="fa fa-suitcase"></i> {{tr('genres')}}</a></li>
    <li class="active">{{tr('view_genre')}}</li>
@endsection 

@section('content')

    @include('notification.notify')

    <div class="row">

        <div class="col-lg-12">

            <div class="box box-widget">

                <div class="box-header with-border">
                    <div class="user-block">
                        <span style="margin-left:0px" class="username"><a href="#">{{$genre->genre_name}}</a></span>
                        <span style="margin-left:0px" class="description">{{tr('created_time')}} - {{$genre->genre_date}}</span>
                    </div>
                    
                    
                    <div class="box-tools">

                        <!-- <button title="Mark as read" data-toggle="tooltip" class="btn btn-box-tool" type="button">
                            <i class="fa fa-circle-o"></i>
                        </button> -->

                        <a href="{{route('admin.edit.edit_genre' , array('sub_category_id' => $genre->sub_category_id,'genre_id' => $genre->genre_id))}}">
                        <button class="btn btn-success btn-sm" type="button">
                            <i class="fa fa-pencil"></i>
                        </button>
                        </a>

                        <!-- <button data-widget="remove" class="btn btn-box-tool" type="button">
                            <i class="fa fa-times"></i>
                        </button> -->
                    </div>

                </div>

                <div class="box-body">

                    <br>

                    <br>

                    <b>{{tr('embed_link')}} : </b> <a href="{{route('genre_embed_video', $genre->unique_id)}}" target="_blank">{{route('genre_embed_video', $genre->unique_id)}}</a>

                    <div class="clearfix"></div>

                     <br>

                   <!--  <video class="img-responsive pad" id="myVideo" width="800" height="350" controls style="background-color:black !important;margin-bottom:15px">
                      <source src="{{$genre->video}}" type="video/mp4">
                    </video> -->

                    <div class="row">

                        <div class="col-lg-6">

                            <strong><i class="fa fa-video-camera margin-r-5"></i> {{tr('trailer_video')}}</strong>

                            <div class="image" id="main_video_setup_error" style="display:none">
                                <img src="{{asset('error.jpg')}}" alt="{{Setting::get('site_name')}}" style="width: 100%">
                            </div>

                            <div class="">
                               <?php /* @if($genre->video_type == 1) -->*/?>
                                    <?php $url = $genre->video; ?>
                                    <div id="main-video-player"></div>
                               <?php /* @else
                                    @if(check_valid_url($genre->video))

                                        <?php // $url = get_video_end($genre->video) : $genre->video; ?>
                                        <div id="main-video-player"></div>
                                    @else
                                        <div class="image">
                                            <img src="{{asset('error.jpg')}}" alt="{{Setting::get('site_name')}}" style="width: 100%">
                                        </div>
                                    @endif

                                @endif  */?>
                            </div>
                            <div class="embed-responsive embed-responsive-16by9" id="flash_error_display_main" style="display: none;">
                               <div style="width: 100%;background: black; color:#fff;height:350px;">
                                     <div style="text-align: center;padding-top:25%">{{tr('flash_miss_error')}}<a target="_blank" href="http://get.adobe.com/flashplayer/" class="underline">{{tr('adobe')}}</a>.</div>
                               </div>
                            </div>
                        </div>
                 
                    </div>

                   
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


                console.log('Inside Video');
                    
                console.log('Inside Video Player');


                    var playerInstance = jwplayer("main-video-player");

                   // alert(playerInstance);

                     playerInstance.setup({
                            file: "{{$genre->video}}",
                            image: "{{$genre->default_image}}",
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
                                  file : "{{$genre->subtitle}}",
                                  kind : "captions",
                                  default : true,
                                }]
                        });


                    playerInstance.on('error', function() {

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

              
        });

    </script>

@endsection

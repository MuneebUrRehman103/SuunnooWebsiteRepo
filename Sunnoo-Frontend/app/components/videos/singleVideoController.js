angular.module('streamViewApp')
.controller('singleVideoController', ['$scope', '$http', '$rootScope', '$window', '$state', '$stateParams',
	function ($scope, $http, $rootScope, $window, $state, $stateParams) {
        $scope.user_id = (memoryStorage.user_id != '' && memoryStorage.user_id != undefined ) ? memoryStorage.user_id : false;
        $scope.access_token = (memoryStorage.access_token != undefined && memoryStorage.access_token != '') ? memoryStorage.access_token : '';
        if ($scope.user_id && $scope.access_token) {
      		$scope.video = '';
      		$scope.displayPopup = false;
      		$scope.showPopup = function() {
      			$scope.displayPopup = true;
      		}

      		$scope.closePopup = function() {
      			$scope.displayPopup = false;
      		}

      		function copyTextToClipboard(text) {
      		   var textArea = document.createElement( "textarea" );
      		   textArea.value = text;
      		   document.body.appendChild( textArea );
      		   textArea.select();
      		   $("#embed_link").select();
      		   try {
                      var successful = document.execCommand( 'copy' );
                      var msg = successful ? 'successful' : 'unsuccessful';
                      console.log('Copying text command was ' + msg);
      		   } catch (err) {
                      console.log('Oops, unable to copy');
      		   }
      		   document.body.removeChild( textArea );
      		}

      		$scope.copyFromTextBox = function() {
      			var embed_link = $("#embed_link").val();
      			copyTextToClipboard(embed_link);
      		};
      		//console.log($scope.video);
    		$scope.sub_profile_id = memoryStorage.sub_profile_id;
    		$scope.user_type = (memoryStorage.user_type == undefined || memoryStorage.user_type == 0 ) ? true : false;
    		$scope.height = $(window).height();
            $scope.page_not_changed = true;
    		$.ajax({
    			type : "post",
    			url : apiUrl + "userApi/singleVideo",
    			data : {id : memoryStorage.user_id, token : memoryStorage.access_token, 
                    admin_video_id : $stateParams.id, device_type : 'web'},
    			async : false,
    			success : function (data) {
    				if (data.success) {
    					$scope.video = data;
              $scope.embed_link = apiUrl+"embed?v_t=2&u_id="+data.video.unique_id;

              if ($scope.video.pay_per_view_status) {
              } else {
                  console.log($scope.video.pay_per_view_status);
                  $scope.page_not_changed = false;
                  $state.go('profile.pay_per_view', {id : $scope.video.video.admin_video_id}, {reload:true});
              }

              if ($scope.video.pay_per_view_status && $scope.video.video.amount <= 0) {
                  if ($scope.user_type) {
                      $scope.page_not_changed = false;
                      $state.go('profile.subscriptions', {sub_profile_id : memoryStorage.sub_profile_id}, {reload:true});
                  }
              }
    				} else {
    					UIkit.notify({message : data.error_messages, timeout : 3000, pos : 'top-center', status : 'danger'});
    					return false;
    				}
    			},
    			error : function (data) {
    				UIkit.notify({message : 'Something Went Wrong, Please Try again later', timeout : 3000, pos : 'top-center', status : 'danger'});
    			},
    		});

            if ($scope.page_not_changed) {
                // save video in continous
                memoryStorage.continous_watch_video_id = $stateParams.id;
                memoryStorage.continous_sub_profile_id = memoryStorage.sub_profile_id;
                localStorage.setItem('sessionStorage', JSON.stringify(memoryStorage));
                function save_video_in_continous(sub_profile_id, admin_video_id) {
                    $.ajax({
                        type : "post",
                        url : apiUrl + "userApi/save/watching/video",
                        data : {id : memoryStorage.user_id, 
                            token : memoryStorage.access_token, 
                            admin_video_id : admin_video_id, 
                            sub_profile_id : sub_profile_id},
                        async : false,
                        success : function (data) {
                            if (data.success) {
                                console.log(data);
                            } else {
                                console.log(data.error_messages);
                                return false;
                            }
                        },
                        error : function (data) {
                            UIkit.notify({message : 'Something Went Wrong, Please Try again later', timeout : 3000, pos : 'top-center', status : 'danger'});
                        },
                    });
                }

                function on_complete_video_delete(sub_profile_id, admin_video_id) {
                    $.ajax({
                        type : "post",
                        url : apiUrl + "userApi/oncomplete/video",
                        data : {id : memoryStorage.user_id, 
                            token : memoryStorage.access_token, 
                            admin_video_id : admin_video_id, 
                            sub_profile_id : sub_profile_id},
                        async : false,
                        success : function (data) {
                            if (data.success) {
                                console.log(data);
                                $rootScope.$emit('disconnect');
                            } else {
                                console.log(data.error_messages);
                                return false;
                            }
                        },
                        error : function (data) {
                            UIkit.notify({message : 'Something Went Wrong, Please Try again later', timeout : 3000, pos : 'top-center', status : 'danger'});
                        },
                    });
                }

                // var JWPLAYER_KEY = $.grep($rootScope.site_settings, function(e){ return e.key == 'JWPLAYER_KEY'; });
                // var jwplayer_key = "";
                // if (JWPLAYER_KEY.length == 0) {
                //     console.log("not found");
                // } else if (JWPLAYER_KEY.length == 1) {
                //   // access the foo property using result[0].foo
                //   jwplayer_key = JWPLAYER_KEY[0].value;
                //   if (jwplayer_key != '' || jwplayer_key != null || jwplayer_key != undefined) {
                //   } else {
                //     jwplayer_key = '';
                //   }
                // } else {
                //   // multiple items found
                //   jwplayer_key = "";
                // }
                // jwplayer.key = jwplayer_key;
                // if (jwplayer_key == "") {
                //     UIkit.notify({message :"Configure JWPLAYER KEY, Please Contact Admin", timeout : 3000, pos : 'top-center', status : 'danger'});
                //     return false;
                // }


        		//var playerInstance = jwplayer("video-player");
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
                    var video = $scope.video.ios_video;
                    // var video = $scope.video.video_rtmp_smil ? common_url+'smil/hls/'+$scope.video.video_rtmp_smil : $scope.video.ios_video;
                } else {
                    var video = $scope.video.main_video;
                   // var video = $scope.video.video_rtmp_smil ? common_url+'smil/'+$scope.video.video_rtmp_smil : $scope.video.main_video; 
                }

                function history() {
                    $.ajax({
                        type : "post",
                        url : apiUrl + "userApi/addHistory",
                        data : {id : memoryStorage.user_id, token : memoryStorage.access_token, admin_video_id : $stateParams.id, sub_profile_id:memoryStorage.sub_profile_id},
                        async : false,
                        success : function (data) {
                            if (data.success) {
                            } else {
                                console.log(data.error_messages);
                                return false;
                            }
                        },
                        error : function (data) {
                            console.log('Something Went Wrong, Please Try again later');
                        },
                    });
                }


                var playerOptions = {
                  html5:{
                    hls:{
                      enableLowInitialPlaylist:false,
                      withCredentials: false
                    }
                  },
                  "playbackRates": [.25,.5,1, 1.5,2.5],
                  "preload":'auto',
                  "autoplay": false,
                  "inactivityTimeout":0,
                  "controls":true,
                  controlBar: {
                    volumePanel: {inline: true}
                  }
                };

                var player = videojs('scriptOwlsPlayer', playerOptions);
                /*
                player.src({
                    src: 'https://suunnoo.nyc3.digitaloceanspaces.com/Dil_Diyan_Gallan/playlist.m3u8',
                    type: 'application/x-mpegURL'
                });
                */
                
                player.src({
                    src: video,
                    type: 'application/x-mpegURL'
                });

                // player.src({
                //     src: 'https://bsongs.storage.googleapis.com/video-data/Bollywood/T/Tiger-Zinda-Hai-2.-7/Zinda-Hai--Tiger-Zinda-Hai.mp4/670526_1328958c5a34988abde74aabb38653c7/670526.m3u8',
                //     type: 'application/x-mpegURL'
                // });

                player.seekButtons({
                  forward: 10,
                  back: 10
                });

                player.bug({
                  height: 200,
                  imgSrc: 'http://demo.thescriptowls.com/player/hls/content/minimal_skin_dark/logo.png',
                  link: "http://thescriptowls.com",
                  opacity: 0.7,
                  padding: '10px',
                  position: 'tr',
                  width: 46
                });

                player.s3BubbleMetaOverlay({
                  subTitle: "You're watching",
                  title: $scope.video.video.title,
                  para: ''
                });

                player.poster($scope.video.video.default_image);
                var shareOptions = {
                  socials: ['fb', 'tw', 'reddit', 'gp', 'messenger', 'linkedin', 'telegram', 'whatsapp', 'viber', 'vk', 'ok', 'mail'],
                  url: $scope.video.share_link,
                  title: $scope.video.video.title,
                  description: $scope.video.video.description,
                  image: $scope.video.video.default_image,
                  // required for Facebook and Messenger
                  fbAppId: '12345', 
                  // optional for Facebook
                  redirectUri: window.location.href + '#close',
                  // optional for VK
                  isVkParse: true,
                }

                player.share(shareOptions);
                
                player.hlsQualitySelector();

                /*
                player.suggestedVideoEndcap({
                  header: 'You may also like',
                  suggestions: [{ 
                    image: "http://demo.thescriptowls.com/videos/Dil_Diyan_Gallan/Dil_Diyan_Gallan.jpg", 
                    title: "Dil Diyan Gallan Song | Tiger Zinda Hai | Salman Khan | Katrina Kaif | Atif Aslam", 
                    url: "http://demo.thescriptowls.com/videos/Dil_Diyan_Gallan/playlist.m3u8"
                  },{ 
                    image: "http://demo.thescriptowls.com/videos/Feeling__Kaur_B/Feeling__Kaur_B.jpg",
                    title: "Feeling | Kaur B | feat. Bunty Bains | Desi Crew | New Punjabi Songs", 
                    url: "http://demo.thescriptowls.com/videos/Feeling__Kaur_B/playlist.m3u8" 
                  },{ 
                    image: "http://demo.thescriptowls.com/videos/IK_VAARI/IK_VAARI.jpg", 
                    title: "IK VAARI Video Song | Feat. Ayushmann Khurrana & Aisha Sharma | T-Series",
                    url: "http://demo.thescriptowls.com/videos/IK_VAARI/playlist.m3u8" 
                  }]
                });
                */

                /*
                player.upnext({
                  timeout : 5000,
                  headText : 'Up Next',
                  cancelText: 'Cancel',
                  getTitle : function() { return 'Next video title' },
                  next : function () { performActionAfterTimeout() }
                });

                function performActionAfterTimeout(){
                  alert('Next Video');
                }
                */

                player.ready(function() {
                  //player.play();
                  if ($scope.video.seek > 0) {
                      player.currentTime($scope.video.seek);
                  }
                });

                player.on('ended', function() {
                  //this.dispose();
                  history();
                  on_complete_video_delete(memoryStorage.continous_sub_profile_id, memoryStorage.continous_watch_video_id);
                });
                //destroy video when $scope is destroyed
                $scope.$on( '$destroy', function() {
                    console.log( 'destroying video player' );
                    player.dispose();
                });

          //       var playerInstance = jwplayer("video-player");
        		// playerInstance.setup({
          //           /*sources: [{
          //               file: video
          //             }],*/
          //           file: video,
          //           image: $scope.video.video.default_image,
          //           width: "100%",
          //           height : $scope.height,
          //           primary: "flash",
          //           autostart : true,
          //           /* "sharing": {
          //               "sites": ["reddit","facebook","twitter"]
          //           },*/
          //           events : {
          //               onComplete : function(event) { 
          //                   history();
          //                   on_complete_video_delete(memoryStorage.continous_sub_profile_id, memoryStorage.continous_watch_video_id);
          //               },
          //           },
          //           tracks : [{
          //             file : common_url+'subtitles/'+$scope.video.video_subtitle_name,
          //             kind : "captions",
          //             default : true,
          //           }]
          //       });

          //       if ($scope.video.seek > 0) {  
          //           playerInstance.on('firstFrame', function() { 
          //               // console.log(seek);
          //               //seek = 15;
          //               playerInstance.seek($scope.video.seek);
          //           });
          //       }
          //       playerInstance.on('error', function() {
          //          //jQuery("#video-player").css("display", "none");
          //          // jQuery('#trailer_video_setup_error').hide();
          //           $rootScope.$emit('disconnect');
          //           var hasFlash = false;
          //           try {
          //               var fo = new ActiveXObject('ShockwaveFlash.ShockwaveFlash');
          //               if (fo) {
          //                   hasFlash = true;
          //               }
          //           } catch (e) {
          //               if (navigator.mimeTypes
          //                       && navigator.mimeTypes['application/x-shockwave-flash'] != undefined
          //                       && navigator.mimeTypes['application/x-shockwave-flash'].enabledPlugin) {
          //                   hasFlash = true;
          //               }
          //           }
          //           console.log(hasFlash);
          //           if (hasFlash == false) {
          //               jQuery('#flash_error_display').show();
          //               return false;
          //           }
          //           // jQuery('#main_video_setup_error').css("display", "block");
          //           // confirm('The video format is not supported in this browser. Please option some other browser.');
          //       });
          //       playerInstance.on('setupError', function() {
          //           $rootScope.$emit('disconnect');
          //           jQuery("#video-player").css("display", "none");
          //          // jQuery('#trailer_video_setup_error').hide();
          //           var hasFlash = false;
          //           try {
          //               var fo = new ActiveXObject('ShockwaveFlash.ShockwaveFlash');
          //               if (fo) {
          //                   hasFlash = true;
          //               }
          //           } catch (e) {
          //               if (navigator.mimeTypes
          //                       && navigator.mimeTypes['application/x-shockwave-flash'] != undefined
          //                       && navigator.mimeTypes['application/x-shockwave-flash'].enabledPlugin) {
          //                   hasFlash = true;
          //               }
          //           }

          //           if (hasFlash == false) {
          //               jQuery('#flash_error_display').show();
          //               return false;
          //           }
          //           // jQuery('#main_video_setup_error').css("display", "block");
          //           // confirm('The video format is not supported in this browser. Please option some other browser.');
          //       });

                var socket_url = $.grep($rootScope.site_settings, function(e){ return e.key == 'socket_url'; });
                var get_socket_url = "";
                if (socket_url.length == 0) {
                    console.log("not found");
                } else if (socket_url.length == 1) {
                  // access the foo property using result[0].foo
                  get_socket_url = socket_url[0].value;
                  if (get_socket_url != '' || get_socket_url != null || get_socket_url != undefined) {
                  } else {
                    get_socket_url = '';
                  }
                } else {
                  // multiple items found
                  get_socket_url = "";
                }
                if (get_socket_url == "") {
                    UIkit.notify({message :"Configure Socket Url, Please Contact Admin", timeout : 3000, pos : 'top-center', status : 'danger'});
                    return false;
                }

                var socketState = false;
                sockets = function () {
                    this.socket = undefined;
                }
                sockets.prototype.disconnect_video = function(data) {
                    this.socket.emit('disconnect', data); 
                }

                sockets.prototype.initialize = function() {
                    this.socket = io(get_socket_url, { 
                        query: "user_id="+memoryStorage.user_id+"&video_id="+$stateParams.id 
                    });

                    this.socket.on('connected', function (data) {
                        socketState = true;
                        console.log('Connected');
                        console.log(data);
                    });

                    this.socket.on('disconnect', function (data) {
                        socketState = false;
                        console.log('Disconnected from server');
                    });
                }

                sockets.prototype.continueWatchingVideo = function(data) {
                    data = {};
                    data.sub_profile_id = memoryStorage.continous_sub_profile_id;
                    data.admin_video_id = memoryStorage.continous_watch_video_id;
                    data.id = memoryStorage.user_id;
                    data.token = memoryStorage.access_token;
                    //data.duration = $(".jw-text-elapsed").html();
                    data.duration = $(".vjs-current-time-display").html();
                    this.socket.emit('save_continue_watching_video', data); 
                }

                socketClient = new sockets();
                socketClient.initialize();
                function continueWatchingVideo(text) {
                    socketClient.continueWatchingVideo();
                }
                var intervalId = window.setInterval(function(){
                    continueWatchingVideo();
                }, 3000); // Every 3 sec

                $rootScope.$on('disconnect', function() {
                    clearInterval(intervalId);
                });
            }
        } else {
            window.localStorage.setItem('logged_in', false);
            memoryStorage = {};
            localStorage.removeItem("sessionStorage");
            localStorage.clear();
            $state.go('static.index', {}, {reload:true});
        }
	}
]);
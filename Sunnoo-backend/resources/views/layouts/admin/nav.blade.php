<aside class="main-sidebar">

    <!-- sidebar: style can be found in sidebar.less -->
    <section class="sidebar">

        <!-- Sidebar user panel -->
        <div class="user-panel">
            <div class="pull-left image">
                <img src="@if(Auth::guard('admin')->user()->picture){{Auth::guard('admin')->user()->picture}} @else {{asset('placeholder.png')}} @endif" class="img-circle" alt="User Image">
            </div>
            <div class="pull-left info">
                <p>{{Auth::guard('admin')->user()->name}}</p>
                <a href="{{route('admin.profile')}}">{{ tr('admin') }}</a>
            </div>
        </div>

        <!-- sidebar menu: : style can be found in sidebar.less -->
        <ul class="sidebar-menu">

            <li id="dashboard">
              <a href="{{route('admin.dashboard')}}">
                <i class="fa fa-dashboard"></i> <span>{{tr('dashboard')}}</span>
              </a>
              
            </li>

            <li class="treeview" id="users">

                <a href="#">
                    <i class="fa fa-user"></i> <span>{{tr('users')}}</span> <i class="fa fa-angle-left pull-right"></i>
                </a>

                <ul class="treeview-menu">
                    <li id="add-user"><a href="{{route('admin.add.user')}}"><i class="fa fa-circle-o"></i>{{tr('add_user')}}</a></li>
                    <li id="view-user"><a href="{{route('admin.users')}}"><i class="fa fa-circle-o"></i>{{tr('view_users')}}</a></li>
                </ul>
    
            </li>

            <li class="treeview" id="moderators">
                
                <a href="#">
                    <i class="fa fa-users"></i> <span>{{tr('moderators')}}</span> <i class="fa fa-angle-left pull-right"></i>
                </a>

                <ul class="treeview-menu">
                    <li id="add-moderator"><a href="{{route('admin.add.moderator')}}"><i class="fa fa-circle-o"></i>{{tr('add_moderator')}}</a></li>
                    <li id="view-moderator"><a href="{{route('admin.moderators')}}"><i class="fa fa-circle-o"></i>{{tr('view_moderators')}}</a></li>
                </ul>
            
            </li>

            <li class="treeview" id="categories">
                <a href="{{route('admin.categories')}}">
                    <i class="fa fa-suitcase"></i> <span>{{tr('categories')}}</span> <i class="fa fa-angle-left pull-right"></i>
                </a>

                <ul class="treeview-menu">
                    <li id="add-category"><a href="{{route('admin.add.category')}}"><i class="fa fa-circle-o"></i>{{tr('add_category')}}</a></li>
                    <li id="view-categories"><a href="{{route('admin.categories')}}"><i class="fa fa-circle-o"></i>{{tr('view_categories')}}</a></li>
                </ul>

            </li>

            <li class="treeview" id="videos">
                <a href="{{route('admin.videos')}}">
                    <i class="fa fa-video-camera"></i> <span>{{tr('videos')}}</span> <i class="fa fa-angle-left pull-right"></i>
                </a>

                <ul class="treeview-menu">
                    <li id="add-video"><a href="{{route('admin.add.video')}}"><i class="fa fa-circle-o"></i>{{tr('add_video')}}</a></li>
                    <li id="view-videos"><a href="{{route('admin.videos')}}"><i class="fa fa-circle-o"></i>{{tr('view_videos')}}</a></li>

                     @if(Setting::get('is_spam'))

                        <li id="spam_videos">
                            <a href="{{route('admin.spam-videos')}}">
                                <i class="fa fa-circle-o"></i> <span>{{tr('spam_videos')}}</span>
                            </a>
                        </li>

                    @endif
                </ul>

            </li>

            <li class="treeview" id="banner-videos">
                <a href="{{route('admin.banner.videos')}}">
                    <i class="fa fa-university"></i> <span>{{tr('banner_videos')}}</span> <i class="fa fa-angle-left pull-right"></i>
                </a>

                <ul class="treeview-menu">
                    @if(get_banner_count() < 6)
                        <li id="add-banner-video"><a href="{{route('admin.add.banner.video')}}"><i class="fa fa-circle-o"></i>{{tr('add_video')}}</a></li>
                    @endif
                    <li id="view-banner-videos"><a href="{{route('admin.banner.videos')}}"><i class="fa fa-circle-o"></i>{{tr('view_videos')}}</a></li>
                </ul>

            </li>

            <li class="treeview" id="subscriptions">

                <a href="#">
                    <i class="fa fa-key"></i> <span>{{tr('subscriptions')}}</span> <i class="fa fa-angle-left pull-right"></i>
                </a>

                <ul class="treeview-menu">

                    <li id="subscriptions-add"><a href="{{route('admin.subscriptions.create')}}"><i class="fa fa-circle-o"></i>{{tr('add_subscription')}}</a></li>
                    <li id="view-subscription"><a href="{{route('admin.subscriptions.index')}}"><i class="fa fa-circle-o"></i>{{tr('view_subscriptions')}}</a></li>
                </ul>
            </li>
            
        <!-- Coupon Section-->
            <li class="treeview" id="coupons">

                <a href="#">
                    <i class="fa fa-gift"></i><span>{{tr('coupons')}}</span><i class="fa fa-angle-left pull-right"></i>
                </a>

                <ul class="treeview-menu">

                    <li id="add-coupons"><a href="{{route('admin.add.coupons')}}"><i class="fa fa-circle-o"></i>{{tr('add_coupon')}}</a></li>
                    <li id = "view-coupons"><a href="{{route('admin.coupon.list')}}"><i class="fa fa-circle-o"></i>{{tr('view_coupon')}}</a></li>
                </ul>
            </li>

            <li id="payments">
                <a href="{{route('admin.user.payments')}}">
                    <i class="fa fa-credit-card"></i> <span>{{tr('payments')}}</span> <i class="fa fa-angle-left pull-right"></i>
                </a>

                <ul class="treeview-menu">

                   
                    
                    <li id="user-payments">
                        <a href="{{route('admin.user.payments')}}">
                            <i class="fa fa-credit-card"></i> <span>{{tr('user_payments')}}</span>
                        </a>
                    </li>


                    @if(Setting::get('is_payper_view'))
                    
                        <li id="video-subscription">
                            <a href="{{route('admin.user.video-payments')}}">
                                <i class="fa fa-credit-card"></i> <span>{{tr('ppv_payments')}}</span>
                            </a>
                        </li>

                    @endif
                    
                </ul>
            </li>

            @if(Setting::get('redeem_control'))

            <li id="redeems">
                <a href="{{route('admin.users.redeems')}}">
                    <i class="fa fa-trophy"></i> <span>{{tr('redeems')}}</span> 
                </a>
            </li>

            @endif

            @if(Setting::get('admin_language_control') == 0)
            <li id="languages">
                <a href="{{route('admin.languages.index')}}">
                    <i class="fa fa-globe"></i> <span>{{tr('languages')}}</span>
                </a>
            </li>
            @endif


            <li id="settings">
                <a href="{{route('admin.settings')}}">
                    <i class="fa fa-gears"></i> <span>{{tr('settings')}}</span>
                </a>
            </li>

            {{-- <li id="settings">
                <a href="{{route('admin.email.settings')}}">
                    <i class="fa fa-envelope"></i> <span>{{tr('email_settings')}}</span>
                </a>
            </li> --}}

           <?php  /*<li id="theme-settings">
                <a href="{{route('admin.theme.settings')}}">
                    <i class="fa fa-refresh"></i> <span>{{tr('theme_settings')}}</span>
                </a>
            </li> 

            <li id="custom-push">
                <a href="{{route('admin.push')}}">
                    <i class="fa fa-send"></i> <span>{{tr('custom_push')}}</span>
                </a>
            </li>*/?>

            <li id="email_templates">
                <a href="{{route('admin.templates')}}">
                    <i class="fa fa-envelope"></i> <span>{{tr('email_templates')}}</span>
                </a>
            </li>

            <li class="treeview" id="static-pages">
                <a href="{{route('static_pages.index')}}">
                    <i class="fa fa-book"></i> <span>{{tr('pages')}}</span> <i class="fa fa-angle-left pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <li id="static-pages-create"><a href="{{route('static_pages.add')}}"><i class="fa fa-circle-o"></i>{{tr('add_page')}}</a></li>
                    <li id="static-pages-list"><a href="{{route('static_pages.index')}}"><i class="fa fa-circle-o"></i>{{tr('view_pages')}}</a></li>
                </ul>
            </li>

            <li id="profile">
                <a href="{{route('admin.profile')}}">
                    <i class="fa fa-diamond"></i> <span>{{tr('account')}}</span>
                </a>
            </li>

            <li id="redeems">
                <a href="{{route('admin.add.mailcamp')}}">
                    <i class="fa fa-envelope"></i> <span>{{tr('mail_camp')}}</span> 
                </a>
            </li>


            <li id="help">
                <a href="{{route('admin.help')}}">
                    <i class="fa fa-question-circle"></i> <span>{{tr('help')}}</span>
                </a>
            </li>

            <li>
                <a href="{{route('admin.logout')}}">
                    <i class="fa fa-sign-out"></i> <span>{{tr('sign_out')}}</span>
                </a>
            </li>

        </ul>

    </section>

    <!-- /.sidebar -->

</aside>
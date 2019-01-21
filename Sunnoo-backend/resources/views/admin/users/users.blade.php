@extends('layouts.admin')

@section('title', tr('users'))

@section('content-header') 

{{tr('users')}} 

<a href="#" id="help-popover" class="btn btn-danger" style="font-size: 14px;font-weight: 600" title="{{tr('any_help')}}">{{tr('help_ques_mark')}}</a>

<div id="help-content" style="display: none">

    <ul class="popover-list">
        <li><span class="text-green"><i class="fa fa-check-circle"></i></span> - {{tr('paid_subscribed_users')}}</li>
        <li><span class="text-red"><i class="fa fa-times"></i></span> -{{tr('unpaid_unsubscribed_user')}}</li>
        <li><b>{{tr('validity_days')}} - </b>{{tr('expiry_days_subscription_user')}}</li>
        <li><b>{{tr('upgrade')}} - </b>{{tr('admin_moderator_upgrade_option')}}</li>
    </ul>
    
</div>

@endsection
@section('breadcrumb')
    <li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
    <li class="active"><i class="fa fa-user"></i> {{tr('users')}}</li>
@endsection

@section('content')

	@include('notification.notify')

	<div class="row">
        <div class="col-xs-12">
          <div class="box box-primary">
          	<div class="box-header label-primary">
                <b style="font-size:18px;">{{tr('users')}}</b>
                <a href="{{route('admin.add.user')}}" class="btn btn-default pull-right">{{tr('add_user')}}</a>
            </div>
            <div class="box-body">

            	@if(count($users) > 0)

	              	<table id="example1" class="table table-bordered table-striped">

						<thead>
						    <tr>
								<th>{{tr('id')}}</th>
								<th>{{tr('username')}}</th>
								<th>{{tr('email')}}</th>
								<th>{{tr('upgrade')}}</th>
								<th>{{tr('validity_days')}}</th>
								<th>{{tr('active_plan')}}</th>
								<th>{{tr('sub_profiles')}}</th>
								<th>{{tr('status')}}</th>
								<th>{{tr('action')}}</th>
						    </tr>
						</thead>

						<tbody>
							@foreach($users as $i => $user)

							    <tr>
							      	<td>{{$i+1}}</td>
							      	<td>
							      		<a href="{{route('admin.view.user' , $user->id)}}">
							      			{{$user->name}}

							      			@if($user->user_type)

							      				<span class="text-green pull-right"><i class="fa fa-check-circle"></i></span>

							      			@else

							      				<span class="text-red pull-right"><i class="fa fa-times"></i></span>

							      			@endif
							      		</a>
							      	</td>

							      	<td>{{$user->email}}</td>
							      	
							      	<td>
							      		@if($user->is_moderator)
							      			<a onclick="return confirm('Are you sure?');" href="{{route('user.upgrade.disable' , array('id' => $user->id, 'moderator_id' => $user->moderator_id))}}" class="label label-warning" title="Do you want to remove the user from Moderator Role?">{{tr('disable')}}</a>
							      		@else
							      			<a onclick="return confirm('Are you sure?');" href="{{route('admin.user.upgrade' , array('id' => $user->id ))}}" class="label label-danger" title="Do you want to change the user as Moderator ?">{{tr('upgrade')}}</a>
							      		@endif

							      	</td>
							      
							      	<td>
								      	@if($user->user_type)
	                                        {{get_expiry_days($user->id)}}
	                                    @endif
							      	</td>

							      	<td><?php echo active_plan($user->id);?></td>


							      	<td>
							      		<a role="menuitem" tabindex="-1" href="{{route('admin.view.sub_profiles',  $user->id)}}"><span class="label label-primary">
							      			{{count($user->subProfile)}} {{tr('sub_profiles')}}</span>
							      		</a>
							      	</td>

							      	<td>
								      	@if($user->is_activated)

								      		<span class="label label-success">{{tr('approve')}}</span>

								      	@else

								      		<span class="label label-warning">{{tr('pending')}}</span>

								      	@endif

							     	</td>
							 
							      	<td>
            							<ul class="admin-action btn btn-default">
            								<li class="@if($i < 2) dropdown @else dropup @endif">
								                <a class="dropdown-toggle" data-toggle="dropdown" href="#">
								                  {{tr('action')}} <span class="caret"></span>
								                </a>
								                <ul class="dropdown-menu">
								                  	<li role="presentation"><a role="menuitem" tabindex="-1" href="{{route('admin.edit.user' , array('id' => $user->id))}}">{{tr('edit')}}</a></li>

								                  	<li role="presentation"><a role="menuitem" tabindex="-1" href="{{route('admin.view.user' , $user->id)}}">{{tr('view')}}</a></li>
								                  	
								                  	<li role="presentation"><a role="menuitem" tabindex="-1" href="{{route('admin.user_approve' , array('id'=>$user->id))}}">@if($user->is_activated) {{tr('decline')}} @else {{tr('approve')}} @endif</a></li>

								                  	@if(!$user->is_verified)
								                  	
									                  	<li role="presentation" class="divider"></li>								                  	
											      		<li role="presentation">
									                  		<a role="menuitem" tabindex="-1" href="{{route('admin.users.verify' , $user->id)}}">{{tr('verify')}}</a>
								                  		</li>
										      		@endif

								                  	<li role="presentation" class="divider"></li>

								                  	<li role="presentation">
								                  		<a role="menuitem" tabindex="-1" href="{{route('admin.view.sub_profiles',  $user->id)}}">{{tr('sub_profiles')}}</a>
								                  	</li>

								                  	<li role="presentation">
								                  	 	@if(Setting::get('admin_delete_control'))
								                  	 		<a role="button" href="javascript:;" class="btn disabled" style="text-align: left">{{tr('delete')}}</a>
								                  	 	@elseif(get_expiry_days($user->id) > 0)

								                  	 		<a role="menuitem" tabindex="-1" onclick="return confirm('Are you sure want to delete the premium user?');" href="{{route('admin.delete.user', array('id' => $user->id))}}">{{tr('delete')}}
								                  			</a>
								                  		@else 
								                  			<a role="menuitem" tabindex="-1" onclick="return confirm('Are you sure?');" href="{{route('admin.delete.user', array('id' => $user->id))}}">{{tr('delete')}}
								                  			</a>
								                  	 	@endif

								                  	</li>
								                  	<li role="presentation" class="divider"></li>

								                  	<?php /*<li role="presentation"><a role="menuitem" tabindex="-1" href="{{route('admin.user.history', $user->id)}}">{{tr('history')}}</a></li>

								                  	<li role="presentation"><a role="menuitem" tabindex="-1" href="{{route('admin.user.wishlist', $user->id)}}">{{tr('wishlist')}}</a></li> */?>
								                  	<li>
														<a href="{{route('admin.subscriptions.plans' , $user->id)}}">		
															<span class="text-green"><b><i class="fa fa-eye"></i>&nbsp;{{tr('subscription_plans')}}</b></span>
														</a>

													</li>
								                  	

								                </ul>
              								</li>
            							</ul>
							      	
							      	</td>
							    </tr>
							@endforeach
						</tbody>
					</table>
				@else
					<h3 class="no-result">{{tr('no_user_found')}}</h3>
				@endif
            </div>
          </div>
        </div>
    </div>

@endsection

@extends('layouts.moderator')

@section('title', tr('videos'))

@section('content-header') 

{{tr('videos') }}

<a href="#" id="help-popover" class="btn btn-danger" style="font-size: 14px;font-weight: 600;border-radius:50%" title="Any Help ?"><i class="fa fa-question"></i></a>

<div id="help-content" style="display: none">

	<!-- <h5>Usage : This section used to display the payment details of the PPV.</h5> -->

    <ul class="popover-list">
        <li><b>{{tr('ppv')}} - </b> {{tr('pay_per_view')}}</li>
        <li><b>{{tr('watch_count_revenue')}} - </b> {{tr('revenue_details')}} </li>
        <li><b>{{tr('ppv_revenue')}} - </b> {{tr('revenue_based_ppv')}}</li>
        <li><b>{{tr('edit_video')}} - </b> {{tr('click_action')}}</li>
        <li><b>{{tr('view_video')}} - </b> {{tr('click_title_action')}}  </li>
        <li><b>Set {{tr('ppv')}} - </b> {{tr('click_action')}}</li>
        <li><b>{{tr('ppv')}} - </b> {{tr('click_action')}}</li>
        
    </ul>
    
</div>

@endsection

@section('breadcrumb')
    <li><a href="{{route('moderator.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
    <li class="active"><i class="fa fa-video-camera"></i> {{tr('videos')}}</li>
@endsection

@section('content')

    @include('notification.notify')


	<div class="row">
        <div class="col-xs-12">
          <div class="box box-primary">
          	<div class="box-header label-primary">
                <b style="font-size:18px;">{{tr('videos')}}</b>
                <a href="{{route('moderator.add.video')}}" class="btn btn-default pull-right">{{tr('add_video')}}</a>
            </div>

            <div class="box-body">

            	<div class="table table-responsive" style="padding: 35px 0px">

            	@if(count($videos) > 0)

	              	<table id="example1" class="table-bordered table-striped">

						<thead>
						    <tr>
						      <th>{{tr('id')}}</th>
						      <th>{{tr('category')}}</th>
						      <th>{{tr('sub_category')}}</th>
						      <th>{{tr('title')}}</th>
						      @if(Setting::get('is_payper_view')) <th>{{tr('pay_per_view')}}</th> @endif
						       <th>{{tr('likes')}}</th>
								<th>{{tr('dislikes')}}</th>
						      <th>{{tr('viewers_cnt')}}</th>
						      <th>{{tr('watch_count_revenue')}}</th>
						      <th>{{tr('ppv_revenue')}}</th>
						      <th>{{tr('status')}}</th>
						      <th>{{tr('action')}}</th>
						    </tr>
						</thead>

						<tbody>
							@foreach($videos as $i => $video)
					
							    <tr>
							      	<td>{{$i+1}}</td>
							      	<td>{{$video->category_name}}</td>
							      	<td>{{$video->sub_category_name}}</td>
							      	<td><a href="{{route('moderator.view.video' , array('id' => $video->video_id))}}"> {{substr($video->title , 0,25)}}...</a></td>

							      	@if(Setting::get('is_payper_view'))
							      	
								      	<td class="text-center">
								      		@if($video->amount > 0)
								      			<span class="label label-success">{{tr('yes')}}</span>
								      		@else
								      			<span class="label label-danger">{{tr('no')}}</span>
								      		@endif
								      	</td>

							      	@endif

							      	<td>{{number_format_short($video->getScopeLikeCount->count())}}</td>

							      	<td>{{number_format_short($video->getScopeDisLikeCount->count())}}</td>

							      	<td>{{$video->watch_count}}</td>
							      	
							      	<td>{{Setting::get('currency')}} {{$video->redeem_amount}}</td>
							      	<td>{{Setting::get('currency')}} {{$video->user_amount}}</td>

							      	<td>
							      		@if ($video->compress_status == 0 || $video->trailer_compress_status == 0)
							      			<span class="label label-danger">{{tr('compress')}}</span>
							      		@else
								      		@if($video->is_approved)
								      			<span class="label label-success">{{tr('approved')}}</span>
								       		@else
								       			<span class="label label-warning">{{tr('pending')}}</span>
								       		@endif
								       	@endif
							      	</td>
								    <td>
            							<ul class="admin-action btn btn-default">
            								<li class="dropdown">
								                <a class="dropdown-toggle" data-toggle="dropdown" href="#">
								                  {{tr('action')}} <span class="caret"></span>
								                </a>
								                <ul class="dropdown-menu">
								                	@if ($video->compress_status == 1 && $video->trailer_compress_status == 1)
								                  	<li role="presentation"><a role="menuitem" tabindex="-1" href="{{route('moderator.edit.video' , array('id' => $video->video_id))}}">{{tr('edit_video')}}</a></li>
								                  	@endif
								                  	<li role="presentation"><a role="menuitem" tabindex="-1" target="_blank" href="{{route('moderator.view.video' , array('id' => $video->video_id))}}">{{tr('view_video')}}</a></li>

								                  	@if(Setting::get('is_payper_view'))

								                  		<li role="presentation">
								                  			<a role="menuitem" tabindex="-1" data-toggle="modal" data-target="#{{$video->video_id}}">{{tr('pay_per_view')}}</a>
								                  		</li>

								                  	@endif
								                </ul>
              								</li>
            							</ul>
								    </td>
							    </tr>

							    <div id="{{$video->video_id}}" class="modal fade" role="dialog">
								  <div class="modal-dialog">
								  <form action="{{route('moderator.save.video-payment', $video->video_id)}}" method="POST">
									    <!-- Modal content-->
									   	<div class="modal-content">
									      <div class="modal-header">
									        <button type="button" class="close" data-dismiss="modal">&times;</button>
									        <h4 class="modal-title">{{tr('pay_per_view')}}</h4>
									      </div>
									      <div class="modal-body">

									      <input type="hidden" name="ppv_created_by" id="ppv_created_by" value="{{Auth::guard('moderator')->user()->id}}">

									        <div class="row">
									        	<div class="col-lg-3">
									        		<label>{{tr('type_of_user')}}</label>
									        	</div>
								                <div class="col-lg-9">
								                  <div class="input-group">
								                        <input type="radio" name="type_of_user" value="{{NORMAL_USER}}" {{($video->type_of_user == NORMAL_USER) ? 'checked' : ''}}>&nbsp;<label>{{tr('normal_user')}}</label>&nbsp;
								                        <input type="radio" name="type_of_user" value="{{PAID_USER}}" {{($video->type_of_user == PAID_USER) ? 'checked' : ''}}>&nbsp;<label>{{tr('paid_user')}}</label>&nbsp;
								                        <input type="radio" name="type_of_user" value="{{BOTH_USERS}}" {{($video->type_of_user == BOTH_USERS) ? 'checked' : ''}}>&nbsp;<label>{{tr('both_user')}}</label>
								                  </div>
								                  <!-- /input-group -->
								                </div>
								            </div>
								            <br>
								            <div class="row">
									        	<div class="col-lg-3">
									        		<label>{{tr('type_of_subscription')}}</label>
									        	</div>
								                <div class="col-lg-9">
								                  <div class="input-group">
								                        <input type="radio" name="type_of_subscription" value="{{ONE_TIME_PAYMENT}}" {{($video->type_of_subscription == ONE_TIME_PAYMENT) ? 'checked' : ''}}>&nbsp;<label>{{tr('one_time_payment')}}</label>&nbsp;
								                        <input type="radio" name="type_of_subscription" value="{{RECURRING_PAYMENT}}" {{($video->type_of_subscription == RECURRING_PAYMENT) ? 'checked' : ''}}>&nbsp;<label>{{tr('recurring_payment')}}</label>
								                  </div>
								                  <!-- /input-group -->
								                </div>
								            </div>
								            <br>
								            <div class="row">
									        	<div class="col-lg-3">
									        		<label>{{tr('amount')}}</label>
									        	</div>
								                <div class="col-lg-9">
								                       <input type="number" required value="{{$video->amount}}" name="amount" class="form-control" id="amount" placeholder="{{tr('amount')}}" step="any">
								                  <!-- /input-group -->
								                </div>
								            </div>
									      </div>
									      <div class="modal-footer">
									      	<div class="pull-left">
									      		@if($video->amount > 0)
									       			<a class="btn btn-danger" href="{{route('admin.remove_pay_per_view', $video->video_id)}}">{{tr('remove_pay_per_view')}}</a>
									       		@endif
									       	</div>
									        <div class="pull-right">
										        <button type="button" class="btn btn-default" data-dismiss="modal">{{tr('close')}}</button>
										        <button type="submit" class="btn btn-primary">{{tr('submit')}}</button>
										    </div>
										    <div class="clearfix"></div>
									      </div>
									    </div>
									</form>
								  </div>
								</div>
							@endforeach
						</tbody>
					</table>
				@else
					<h3 class="no-result">{{tr('no_result_found')}}</h3>
				@endif

				</div>
            </div>
          </div>
        </div>
    </div>

@endsection



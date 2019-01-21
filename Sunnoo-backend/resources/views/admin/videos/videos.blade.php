@extends('layouts.admin')

@section('title', tr('videos'))

@section('content-header', tr('videos'))

@section('breadcrumb')
    <li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
    <li class="active"><i class="fa fa-video-camera"></i> {{tr('videos')}}</li>
@endsection

@section('content')

    @include('notification.notify')
	<div class="row">
        <div class="col-xs-12">
          <div class="box box-primary">

          	<div class="box-header label-primary">
                <b style="font-size:18px;">{{tr('videos')}}</b>
                <a href="{{route('admin.add.video')}}" class="btn btn-default pull-right">{{tr('add_video')}}</a>
            </div>

            <div class="box-body">

            	<div class=" table-responsive"> 

            	@if(count($videos) > 0)

	              	<table id="example1" class="table table-bordered table-striped text-nowrap">

						<thead>
						    <tr>
						      <th>{{tr('id')}}</th>
						      <th>{{tr('action')}}</th>
						      <th>{{tr('position')}}</th>
						      <th>{{tr('status')}}</th>
						      <th>{{tr('category')}}</th>
						      <th>{{tr('sub_category')}}</th>
						      <th>{{tr('genre_name')}}</th>
						      <th>{{tr('title')}}</th>
						      @if(Setting::get('theme') == 'default')
						      	<th>{{tr('slider_video')}}</th>
						      @endif
						      @if(Setting::get('is_payper_view'))
						      	<th>{{tr('pay_per_view')}}</th>
						      @endif
						      <th>{{tr('likes')}}</th>
								<th>{{tr('dislikes')}}</th>
						      <th>{{tr('viewers_cnt')}}</th>
						      <th>{{tr('revenue')}}</th>
						      <th>{{tr('uploaded_by')}}</th>
						      
						    </tr>
						</thead>

						<tbody>
							@foreach($videos as $i => $video)

							    <tr>
							      	<td>{{$i+1}}</td>

								    <td>
            							<ul class="admin-action btn btn-default">
            								<li class="{{ $i < 1 ? 'dropdown' : 'dropup'}}">
								                <a class="dropdown-toggle" data-toggle="dropdown" href="#">
								                  {{tr('action')}} <span class="caret"></span>
								                </a>
								                <ul class="dropdown-menu">
								                	@if ($video->compress_status == 1 && $video->trailer_compress_status == 1)
								                  	<li role="presentation">
                                                        @if(Setting::get('admin_delete_control'))
                                                            <a role="button" href="javascript:;" class="btn disabled" style="text-align: left">{{tr('edit')}}</a>
                                                        @else
                                                            <a role="menuitem" tabindex="-1" href="{{route('admin.edit.video' , array('id' => $video->video_id))}}">{{tr('edit')}}</a>
                                                        @endif
                                                    </li>
                                                    @endif
								                  	<li role="presentation"><a role="menuitem" tabindex="-1" target="_blank" href="{{route('admin.view.video' , array('id' => $video->video_id))}}">{{tr('view')}}</a></li>

								                  	@if ($video->genre_id > 0 && $video->is_approved && $video->status)

								                  	<li role="presentation">
								                		<a role="menuitem" tabindex="-1" role="menuitem" tabindex="-1" data-toggle="modal" data-target="#video_{{$video->video_id}}">{{tr('change_position')}}</a>
								                	</li>

								                	@endif

								                  	@if(Setting::get('is_payper_view'))

								                  		<li role="presentation">
								                  			<a role="menuitem" tabindex="-1" data-toggle="modal" data-target="#{{$video->video_id}}">{{tr('pay_per_view')}}</a>
								                  		</li>

								                  	@endif

								                  	<li class="divider" role="presentation"></li>

								                  	@if($video->is_approved)
								                		<li role="presentation"><a role="menuitem" tabindex="-1" href="{{route('admin.video.decline',$video->video_id)}}">{{tr('decline')}}</a></li>
								                	@else
								                		@if ($video->compress_status == 0 || $video->trailer_compress_status == 0)
								                			<li role="presentation"><a role="menuitem" tabindex="-1">{{tr('compress')}}</a></li>
								                		@else 
								                  			<li role="presentation"><a role="menuitem" tabindex="-1" href="{{route('admin.video.approve',$video->video_id)}}">{{tr('approve')}}</a></li>
								                  		@endif
								                  	@endif

								                  	@if($video->status == 0)
								                  		<li role="presentation"><a role="menuitem" tabindex="-1" href="{{route('admin.video.publish-video',$video->video_id)}}">{{tr('publish')}}</a></li>
								                  	@endif

								                  	@if ($video->compress_status == 1 && $video->trailer_compress_status == 1)
									                  	<li class="divider" role="presentation"></li>

									                  	<li role="presentation">
									                  		@if(Setting::get('admin_delete_control'))

										                  	 	<a role="button" href="javascript:;" class="btn disabled" style="text-align: left">{{tr('delete')}}</a>

										                  	@else
									                  			<a role="menuitem" tabindex="-1" onclick="return confirm('Are you sure want to delete video? Remaining video positions will Rearrange')" href="{{route('admin.delete.video' , array('id' => $video->video_id))}}">{{tr('delete')}}</a>
									                  		@endif
									                  	</li>
								                  	@endif
								                </ul>
              								</li>
            							</ul>
								    </td>

							      	<td>

							      		@if ($video->genre_id > 0)
							      			
								      		@if($video->position > 0)

									      	<span class="label label-success">{{$video->position}}</span>

									      	@else

									      	<span class="label label-danger">{{$video->position}}</span>

									      	@endif

									    @else

									    	<span class="label label-warning">{{tr('not_genre')}}</span>

									    @endif

							      	</td>

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

							      	<td>{{$video->category_name}}</td>
							      	<td>{{$video->sub_category_name}}</td>
							      	<td>{{$video->genre_name ? $video->genre_name : '-'}}</td>
							      	<td><a href="{{route('admin.view.video' , array('id' => $video->video_id))}}">{{substr($video->title , 0,25)}}...</a></td>
							      	@if(Setting::get('theme') == 'default')
							      	<td>
							      		@if($video->is_home_slider == 0 && $video->is_approved && $video->status)
							      			<a href="{{route('admin.slider.video' , $video->video_id)}}"><span class="label label-danger">{{tr('set_slider')}}</span></a>
							      		@elseif($video->is_home_slider)
							      			<span class="label label-success">{{tr('slider')}}</span>
							      		@else
							      			-
							      		@endif
							      	</td>

							      	@endif
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
							      	<td>{{number_format_short($video->watch_count)}}</td>

							      	<td>{{Setting::get('currency')}} {{$video->admin_amount ? $video->admin_amount : "0.00"}}</td>

							      	<td>

							      		@if(is_numeric($video->uploaded_by))

							      			<a href="{{route('admin.moderator.view',$video->uploaded_by)}}">{{$video->moderator ? $video->moderator->name : ''}}</a>


							      		@else 

							      			{{$video->uploaded_by}}

							      		@endif

							      	</td>

							    </tr>

								<!-- Modal -->
								<div id="{{$video->video_id}}" class="modal fade" role="dialog">
								  <div class="modal-dialog">
								  <form action="{{route('admin.save.video-payment', $video->video_id)}}" method="POST">
									    <!-- Modal content-->
									   	<div class="modal-content">
									      <div class="modal-header">
									        <button type="button" class="close" data-dismiss="modal">&times;</button>
									        <h4 class="modal-title">{{tr('ppv')}}</h4>
									      </div>
									      <div class="modal-body">
									        <div class="row">

									        	<input type="hidden" name="ppv_created_by" id="ppv_created_by" value="{{Auth::guard('admin')->user()->name}}">

									        	<div class="col-lg-3">
									        		<label>{{tr('type_of_user')}}</label>
									        	</div>
								                <div class="col-lg-9">
								                  <div class="input-group">
								                        <input type="radio" name="type_of_user" value="{{NORMAL_USER}}" {{($video->type_of_user == 0 || $video->type_of_user == '') ? 'checked' : (($video->type_of_user == NORMAL_USER) ? 'checked' : '')}}>&nbsp;<label>{{tr('normal_user')}}</label>&nbsp;
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
								                        <input type="radio" name="type_of_subscription" value="{{ONE_TIME_PAYMENT}}" {{($video->type_of_subscription == 0 || $video->type_of_subscription == '') ? 'checked' : (($video->type_of_subscription == ONE_TIME_PAYMENT) ? 'checked' : '')}}>&nbsp;<label>{{tr('one_time_payment')}}</label>&nbsp;
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
										        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
										        <button type="submit" class="btn btn-primary">{{tr('submit')}}</button>
										    </div>
										    <div class="clearfix"></div>
									      </div>
									    </div>
									</form>
								  </div>
								</div>

								@if ($video->genre_id > 0 && $video->is_approved && $video->status)

								<div id="video_{{$video->video_id}}" class="modal fade" role="dialog">
								  <div class="modal-dialog">
								  <form action="{{route('admin.save.video.position',['video_id'=>$video->video_id])}}" method="POST">
									    <!-- Modal content-->
									   	<div class="modal-content">
									      <div class="modal-header">
									        <button type="button" class="close" data-dismiss="modal">&times;</button>
									        <h4 class="modal-title">{{tr('change_position')}}</h4>
									      </div>

									      <div class="modal-body">
									        
								            <div class="row">
									        	<div class="col-lg-3">
									        		<label>{{tr('position')}}</label>
									        	</div>
								                <div class="col-lg-9">
								                       <input type="number" required value="{{$video->position}}" name="position" class="form-control" id="position" placeholder="{{tr('position')}}" pattern="[0-9]{1,}" title="Enter 0-9 numbers">
								                  <!-- /input-group -->
								                </div>
								            </div>
									      </div>
									      <div class="modal-footer">
									        <div class="pull-right">
										        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
										        <button type="submit" class="btn btn-primary">{{tr('submit')}}</button>
										    </div>
										    <div class="clearfix"></div>
									      </div>
									    </div>
									</form>
								  </div>
								</div>

								@endif
							@endforeach
						</tbody>
					</table>
				@else
					<h3 class="no-result">{{tr('no_video_found')}}</h3>
				@endif

				</div>
            </div>
          </div>
        </div>
    </div>

@endsection

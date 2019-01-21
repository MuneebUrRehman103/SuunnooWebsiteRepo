@extends('layouts.admin')

@section('title', tr('subscriptions'))

@section('content-header', tr('subscriptions'))

@section('breadcrumb')
    <li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
    <li class="active"><i class="fa fa-key"></i> {{tr('subscriptions')}}</li>
@endsection

@section('after-styles')

<style>

.subscription-image {
	overflow: hidden !important;
	position: relative !important;
	height: 15em !important;
	background-position: center !important;
	background-repeat: no-repeat !important;
	background-size: cover !important;
	margin: 0 !important;
	width: 100%;
}

.subscription-desc {
	min-height: 10em !important;
	max-height: 10em !important;
	overflow: scroll !important;
	margin-bottom: 10px !important;
}

</style>

@endsection

@section('content')
	

			<div class="row">
		        <div class="col-xs-12">
		          <div class="box">
		            <div class="box-body">

		            	@if(count($payments) > 0)

			              	<table id="example1" class="table table-bordered table-striped">

								<thead>
								    <tr>
								      <th>{{tr('id')}}</th>
								      <th>{{tr('username')}}</th>
								      <th>{{tr('subscription')}}</th>
								      <th>{{tr('payment_id')}}</th>
								      <th>{{tr('no_of_account')}}</th>
								      <th>{{tr('amount')}}</th>
								      <th>{{tr('expiry_date')}}</th>
								    </tr>
								</thead>

								<tbody>

									@foreach($payments as $i => $payment)

									    <tr>
									      	<td>{{$i+1}}</td>
									      	<td><a href="{{route('admin.view.user' , $payment->user_id)}}"> {{($payment->user) ? $payment->user->name : ''}} </a></td>
									      	<td>
									      		@if($payment->subscription)
									      		<a href="{{route('admin.subscriptions.view' , $payment->subscription->unique_id)}}" target="_blank">{{$payment->subscription ? $payment->subscription->title : ''}}</a>
									      		@endif
									      	</td>
									      	<td>{{$payment->payment_id}}</td>
									      	<td>{{$payment->subscription ? $payment->subscription->no_of_account : 0}}</td>
									      	<td>$ {{$payment->amount}}</td>
									      	<td>{{date('d M Y',strtotime($payment->expiry_date))}}</td>
									    </tr>					
									@endforeach
								</tbody>
							</table>

							<div>
								
							</div>
						@else
							<h3 class="no-result">{{tr('no_subscription_found')}}</h3>
						@endif

		            </div>
		          </div>
		        </div>
		    </div>
			

			<div class="row">

				<div class="col-md-12">

					<!-- <h3>{{tr('subscription')}}</h3> -->

					@include('notification.notify')

					<div class="row">

						@if(count($subscriptions) > 0)

							@foreach($subscriptions as $s => $subscription)

								<div class="col-md-4 col-lg-4 col-sm-6 col-xs-12">

									<div class="thumbnail">

										<!-- <img alt="{{$subscription->title}}" src="{{$subscription->picture ?  $subscription->picture : asset('common/img/landing-9.png')}}" class="subscription-image" /> -->

										<div class="caption">

											<h3>
												<a href="{{route('admin.subscriptions.view' , $subscription->unique_id)}}" target="_blank">{{$subscription->title}}</a>
											</h3>

											<hr>

											<h4>{{tr('no_of_account')}}</h4>


											<div class="text-success"><b>{{$subscription->no_of_account}}</b></div>


											<h4>
												<b>{{tr('description')}} : </b>
											</h4>

											<div class="subscription-desc">
												<?php echo $subscription->description; ?>
											</div>

											<br>

											<p>
												<span class="btn btn-danger pull-left">{{ Setting::get('currency')}} {{$subscription->amount}} / {{$subscription->plan}} M</span>

												<a href="{{route('admin.subscription.save' , ['s_id' => $subscription->id, 'u_is'=>$id])}}" class="btn btn-success pull-right">{{tr('choose')}}</a>

											</p>
											<br>
											<br>
										</div>
									
									</div>
								
								</div>

							@endforeach

						@endif
						
					</div>
				</div>
			</div>


@endsection
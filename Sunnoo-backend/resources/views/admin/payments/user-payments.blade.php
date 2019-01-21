@extends('layouts.admin')

@section('title', tr('user_payments'))

@section('content-header',tr('user_payments'))

@section('breadcrumb')
    <li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
    <li class="active"><i class="fa fa-credit-card"></i> {{tr('user_payments')}}</li>
@endsection

@section('content')

@include('notification.notify')

	<div class="row">
        <div class="col-xs-12">
          <div class="box box-info">
            <div class="box-body">

            	@if(count($data) > 0)

	              	<table id="example1" class="table table-bordered table-striped">

						<thead>
						    <tr>
						      <th>{{tr('id')}}</th>
						      <th>{{tr('username')}}</th>
						      <th>{{tr('subscriptions')}}</th>
						      <th>{{tr('payment_id')}}</th>
						      <th>{{tr('amount')}}</th>
						      <th>{{tr('expiry_date')}}</th>
						    </tr>
						</thead>

						<tbody>

							@foreach($data as $i => $payment)

							    <tr>
							      	<td>{{$i+1}}</td>
							      	<td><a href="{{route('admin.view.user' , $payment->user_id)}}"> {{($payment->user) ? $payment->user->name : ''}} </a></td>
							      	<td>
							      		@if($payment->subscription)
							      			<a href="{{route('admin.subscriptions.view' , $payment->subscription->unique_id)}}" target="_blank">{{$payment->subscription ? $payment->subscription->title : ''}}</a>
							      		@else
							      			-
							      		@endif
							      	</td>
							      	<td>{{$payment->payment_id}}</td>
							      	<td>{{Setting::get('currency')}} {{$payment->amount}}</td>
							      	<td>{{date('d M Y',strtotime($payment->expiry_date))}}</td>
							    </tr>					
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

@endsection



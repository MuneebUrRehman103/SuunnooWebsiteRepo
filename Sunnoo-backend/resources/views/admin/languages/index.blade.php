@extends('layouts.admin')

@section('title', tr('languages'))


@section('content-header', tr('languages'))


@section('breadcrumb')
    <li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
    <li class="active"><i class="fa fa-globe"></i> {{tr('languages')}}</li>

@endsection

@section('content')

	@include('notification.notify')

    <div class="row">

        <div class="col-xs-12">

            <div class="box box-primary">

                <div class="box-header label-primary">
                    <b>{{tr('languages')}}</b>
                    <a href="{{route('admin.languages.create')}}" style="float:right" class="btn btn-default">{{tr('create_language')}}</a>
                </div>
                <div class="box-body">


                  	    <table id="example1" class="table table-bordered table-striped">

                            <thead>
                                <tr>
                                    <th>{{tr('id')}}</th>
                                    <th>{{tr('language') }}</th>
                                    <th>{{tr('short_name')}}</th>
                                    <th>{{tr('file')}}</th>
                                    <th>{{tr('status')}}</th>
                                    <th>{{tr('action')}}</th>
                                </tr>
                            
                            </thead>

                            <tbody>
                               
                                @foreach($data as $index => $value)
                                    <tr>
                                        <td>{{$index+1}}</td>
                                        <td>{{$value->language}}</td>
                                        <td>{{$value->folder_name}}</td>
                                        <td>
                                            <a href="{{route('admin.languages.download', $value->folder_name)}}" target="_blank">
                                                {{tr('download_here')}}
                                            </a>

                                        </td>
                                        <td>
                                             @if($value->status)
                                                <span class="label label-success">{{tr('active')}}</span>
                                            @else
                                                <span class="label label-warning">{{tr('inactive')}}</span>
                                            @endif
                                        </td>
                                        <td>
                                           <div class="dropdown">
                                            
                                                <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    {{tr('action')}}
                                                    <span class="caret"></span>
                                                </button>

                                                <ul class="dropdown-menu" aria-labelledby="dropdownMenu">
                                                    @if($index != 0)
                                                    <li>
                                                        @if(Setting::get('admin_delete_control'))
                                                            <a href="javascript:;" class="btn disabled" style="text-align: left"><b><i class="fa fa-edit"></i>&nbsp;{{tr('edit')}}</b></a>
                                                        @else
                                                            <a href="{{route('admin.languages.edit', $value->id)}}"><b><i class="fa fa-edit"></i>&nbsp;{{tr('edit')}}</b></a>
                                                        @endif
                                                    </li>

                                                    @endif
                                                    <li>

                                                        <a href="{{route('admin.languages.status', $value->id)}}"><b>
                                                        @if($value->status)
                                                            <i class="fa fa-close"></i>&nbsp;{{tr('inactivate')}}
                                                        @else
                                                            <i class="fa fa-check"></i>&nbsp;{{tr('activate')}}
                                                        @endif
                                                        </b>
                                                        </a>
                                                    </li>
                                                    
                                                    @if($value->folder_name != Setting::get('default_lang'))
                                                    <li>

                                                        <a href="{{route('admin.languages.set_default_language', $value->folder_name)}}"><b>
                                                            <i class="fa fa-globe"></i>&nbsp;{{tr('set_default_language')}}
                                                            </b>
                                                        </a>
                                                    </li>

                                                    @endif
                                                    

                                                    @if($index != 0)

                                                    <li>
                                                        @if(Setting::get('admin_delete_control'))
                                                            <a href="javascript:;" class="btn disabled" style="text-align: left"><b><i class="fa fa-trash"></i>&nbsp;{{tr('delete')}}</b></a>

                                                        @else
                                                            <a onclick="return confirm('Are you sure?')" href="{{route('admin.languages.delete',$value->id)}}"><b><i class="fa fa-trash"></i>&nbsp;{{tr('delete')}}</b></a>

                                                        @endif

                                                    </li>   

                                                    @endif                                 

                                                </ul>

                                            </div>
                                        </td>
                                    </tr>
                                
                                @endforeach

        					</tbody>

    					</table>
                
                </div>

            </div>
        
        </div>
    
    </div>

@endsection


@if(Session::has('flash_language'))

@section('scripts')

<script type="text/javascript" src="{{asset('common/js/bootbox.min.js')}}"></script>
<script type="text/javascript">

bootbox.confirm("Do you want to reload the page to view default language ?", function(result){ 
    if (result == true) {
        window.location.reload(true);
    }
    console.log('This was logged in the callback: ' + result); 
});

</script>
@endsection

@endif

@foreach($model as $genre_list) 
<div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
	<div class="episode-list-box">

		<div class="episode-img bg-img" style="background-image: url({{$genre_list['default_image']}});">
			<div class="episode-img-overlay">
				<div class="episode-img-inner">
					<p class="episode-count">{{$genre_list['watch_count']}}</p>
				</div>

				<div class="epi-play-icon-outer">
					<a href="{{Setting::get('ANGULAR_SITE_URL').'video/'.$genre_list['admin_video_id']}}" class="epi-play-icon">
						<i class="fa fa-play"></i>
					</a>
				</div>
			</div>
		</div>

		<div class="episode-list-content">
			<div class="row no-margin">
				<div class="pull-left">
					<span class="epi-count">+{{$genre_list['title']}}</span>
				</div>

				<div class="pull-right">
					<p class="bold gray-color1">{{$genre_list['duration']}}</p>
				</div>
			</div>

			<div class="">
				<p class="epi-des gray-color1">{{$genre_list['description']}}</p>
			</div>
		</div>

	</div>

</div>

@endforeach
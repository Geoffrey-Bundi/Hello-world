@extends("app")

@section("content")
<div class="row">
    <div class="col-sm-12">
        <ol class="breadcrumb">
            <li><a href="{!! url('home') !!}"><i class="fa fa-home"></i> {!! trans('messages.home') !!}</a></li>
            <li class="active"><i class="fa fa-cubes"></i> {!! trans('messages.pt') !!}</li>
            <li class="active"><i class="fa fa-cube"></i> {!! trans('messages.sample-preparation') !!}</li>
        </ol>
    </div>
</div>
<div class="card">
	<div class="card-header">
	    <i class="fa fa-book"></i> {!! trans('messages.sample-preparation') !!}
	    <span>
        @permission('create-sample')
		    <a class="btn btn-sm btn-belize-hole" href="{!! url("material/create") !!}" >
  				<i class="fa fa-plus-circle"></i>
  				{!! trans('messages.add') !!}
  			</a>
        @endpermission
  			<a class="btn btn-sm btn-carrot" href="#" onclick="window.history.back();return false;" alt="{!! trans('messages.back') !!}" title="{!! trans('messages.back') !!}">
  				<i class="fa fa-step-backward"></i>
  				{!! trans('messages.back') !!}
  			</a>
  		</span>
	</div>
  	<div class="card-block">
		@if (Session::has('message'))
			<div class="alert alert-info">{!! Session::get('message') !!}</div>
		@endif
		@if($errors->all())
        <div class="alert alert-danger alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">{!! trans('messages.close') !!}</span></button>
            {!! HTML::ul($errors->all(), array('class'=>'list-unstyled')) !!}
        </div>
        @endif
	 	<table class="table table-bordered table-sm search-table" id="example">
			<thead>
				<tr>
					<th>{!! trans('messages.batch') !!}</th>
					<th>{!! trans('messages.date-prepared') !!}</th>
					<th>{!! trans('messages.expiry-date') !!}</th>
          <th>{!! trans('messages.material-type') !!}</th>
          <th>{!! trans('messages.original-source') !!}</th>
          <th>{!! trans('messages.date-collected') !!}</th>
          <th>{!! trans('messages.prepared-by') !!}</th>
					<th>{!! trans('messages.action') !!}</th>
				</tr>
			</thead>
			<tbody>
			@foreach($materials as $key => $value)
				<tr @if(session()->has('active_material'))
	                    {!! (session('active_material') == $value->id)?"class='warning'":"" !!}
	                @endif
	                >
					<td>{!! $value->batch !!}</td>
					<td>{!! $value->date_prepared !!}</td>
					<td>{!! $value->expiry_date !!}</td>
          <td>{!! $value->material($value->material_type) !!}</td>
					<td>{!! $value->original_source !!}</td>
					<td>{!! $value->date_collected !!}</td>
          <td>{!! $value->user->name !!}</td>
					<td>

					<!-- show the test category (uses the show method found at GET /material/{id} -->
            @permission('view-sample')
						<a class="btn btn-sm btn-success" href="{!! url("material/" . $value->id) !!}" >
							<i class="fa fa-folder-open-o"></i>
							{!! trans('messages.view') !!}
						</a>
            @endpermission
					<!-- edit this test category (uses edit method found at GET /material/{id}/edit -->
            @permission('update-sample')
						<a class="btn btn-sm btn-info" href="{!! url("material/" . $value->id . "/edit") !!}" >
							<i class="fa fa-edit"></i>
							{!! trans('messages.edit') !!}
						</a>
            @endpermission
					<!-- delete this test category (uses delete method found at GET /material/{id}/delete -->
            @permission('delete-sample')
						<button class="btn btn-sm btn-danger delete-item-link"
							data-toggle="modal" data-target=".confirm-delete-modal"
							data-id='{!! url("material/" . $value->id . "/delete") !!}'>
							<i class="fa fa-trash-o"></i>
							{!! trans('messages.delete') !!}
						</button>
            @endpermission
					</td>
				</tr>
			@endforeach
			</tbody>
		</table>
  	</div>
</div>
{!! session(['SOURCE_URL' => URL::full()]) !!}
@endsection

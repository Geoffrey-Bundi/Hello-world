@extends("app")

@section("content")
<div class="row">
    <div class="col-sm-12">
        <ol class="breadcrumb">
            <li><a href="{!! url('home') !!}"><i class="fa fa-home"></i> {!! trans('messages.home') !!}</a></li>
            <li class="active"><i class="fa fa-cubes"></i> {!! trans('messages.pt') !!}</li>
            <li class="active"><i class="fa fa-cube"></i> {!! trans_choice('messages.receipt', 2) !!}</li>
        </ol>
    </div>
</div>
<div class="card">
	<div class="card-header">
	    <i class="fa fa-book"></i> {!! trans_choice('messages.receipt', 2) !!}
	    <span>
        @permission('create-receipt')
		    <a class="btn btn-sm btn-belize-hole" href="{!! url("receipt/create") !!}" >
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
					<th>{!! trans_choice('messages.pt-round', 1) !!}</th>
          <th>{!! trans('messages.date-received') !!}</th>
          <th>{!! trans('messages.panels-received') !!}</th>
					<th>{!! trans('messages.condition') !!}</th>
          <th>{!! trans('messages.storage') !!}</th>
          <th>{!! trans('messages.transit-temperature') !!}</th>
          <th>{!! trans_choice('messages.recipient', 1) !!}</th>
					<th>{!! trans('messages.action') !!}</th>
				</tr>
			</thead>
			<tbody>
			@foreach($receipts as $key => $value)
				<tr @if(session()->has('active_receipt'))
	                    {!! (session('active_receipt') == $value->id)?"class='warning'":"" !!}
	                @endif
	                >
					<td>{!! $value->id !!}</td>
          <td>{!! $value->date_received !!}</td>
          <td>{!! $value->panels_received !!}</td>
          <td>{!! $value->condition!!}</td>
          <td>{!! $value->storage !!}</td>
          <td>{!! $value->transit_temperature !!}</td>
          <td>{!! $value->receiver->name !!}</td>
					<td>

					<!-- show the test category (uses the show method found at GET /receipt/{id} -->
            @permission('view-receipt')
            <a class="btn btn-sm btn-success" href="{!! url("receipt/" . $value->id) !!}" >
							<i class="fa fa-folder-open-o"></i>
							{!! trans('messages.view') !!}
						</a>
            @endpermission
					<!-- edit this test category (uses edit method found at GET /receipt/{id}/edit -->
            @permission('update-receipt')
            <a class="btn btn-sm btn-info" href="{!! url("receipt/" . $value->id . "/edit") !!}" >
							<i class="fa fa-edit"></i>
							{!! trans('messages.edit') !!}
						</a>
            @endpermission
					<!-- delete this test category (uses delete method found at GET /receipt/{id}/delete -->
            @permission('delete-receipt')
            <button class="btn btn-sm btn-danger delete-item-link"
							data-toggle="modal" data-target=".confirm-delete-modal"
							data-id='{!! url("receipt/" . $value->id . "/delete") !!}'>
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

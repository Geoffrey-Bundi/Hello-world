@extends('app')
@section('content')
<div class="row">
    <div class="col-sm-12">
        <ol class="breadcrumb">
            <li><a href="{!! url('home') !!}"><i class="fa fa-home"></i> {!! trans('messages.home') !!}</a></li>
            <li class="active"><i class="fa fa-cubes"></i> {!! trans('messages.pt') !!}</li>
            <li class="active"><i class="fa fa-cube"></i> {!! trans_choice('messages.pt-round', 2) !!}</li>
        </ol>
    </div>
</div>
<div class="" id="manage-round">
    <!-- Round Listing -->
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left col-md-8">
                <h5><i class="fa fa-book"></i> {!! trans_choice('messages.pt-round', 2) !!}
        
                @permission('create-round')
                    <button type="button" class="btn btn-sm btn-belize-hole" data-toggle="modal" data-target="#create-round">
                        <i class="fa fa-plus-circle"></i>
                        {!! trans('messages.add') !!}
                    </button>
                @endpermission
                    <a class="btn btn-sm btn-carrot" href="#" onclick="window.history.back();return false;" alt="{!! trans('messages.back') !!}" title="{!! trans('messages.back') !!}">
                        <i class="fa fa-step-backward"></i>
                        {!! trans('messages.back') !!}
                    </a>
                @permission('enrol-participants')
                    <button type="button" class="btn btn-sm btn-concrete" data-toggle="modal" data-target="#create-round">
                        <i class="fa fa-download"></i>
                        Enrolment Template
                    </button>
                @endpermission
                </h5>
            </div>
            <div class="col-md-2"></div>
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control" placeholder="Search for..." v-model="query">
                    <span class="input-group-btn">
                        <button class="btn btn-secondary" type="button" @click="search()" v-if="!loading"><i class="fa fa-search"></i></button>
                        <button class="btn btn-secondary" type="button" disabled="disabled" v-if="loading">Searching...</button>
                    </span>
                </div>
            </div>
        </div>
    </div>
    <table class="table table-bordered">
        <tr>
            <th>Title</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Action</th>
        </tr>
        <tr v-for="round in rounds">
            <td>@{{ round.description }}</td>
            <td>@{{ round.start_date }}</td>
            <td>@{{ round.end_date }}</td>
            <td>
            @permission('update-round')	
                <button v-bind="{ 'disabled': round.deleted_at!=NULL}" class="btn btn-sm btn-primary" @click.prevent="editRound(round)"><i class="fa fa-edit"></i> Edit</button>
            @endpermission
            @permission('restore-round')
                <button v-if="round.deleted_at!=NULL" class="btn btn-sm btn-success" @click.prevent="restoreRound(round)"><i class="fa fa-toggle-on"></i> Enable</button>
            @endpermission
            @permission('delete-round')
                <button v-if="round.deleted_at==NULL" class="btn btn-sm btn-danger" @click.prevent="deleteRound(round)"><i class="fa fa-power-off"></i> Disable</button>
            @endpermission
            @permission('enrol-participants')
                <button v-if="round.deleted_at==NULL" class="btn btn-sm btn-wet-asphalt" id="enrol" data-toggle="modal" data-target="#enrol-participants" data-fk="@{{round.id}}" @click.prevent="loadParticipants(round)"><i class="fa fa-send"></i> Enrol Testers</button>
                <button v-if="round.deleted_at==NULL" class="btn btn-sm btn-nephritis" id="enrol" data-toggle="modal" data-target="#enrol-participants" data-fk="@{{round.id}}"><i class="fa fa-level-up"></i> Upload Sheet</button>
                <button v-if="round.deleted_at==NULL" class="btn btn-sm btn-amethyst"  id="enrolled" @click.prevent="loadEnrollments(round)"><i class="fa fa-folder-open"></i> Enrolled Testers</button>
            @endpermission
            </td>
        </tr>
    </table>
    <!-- Pagination -->
    <nav>
        <ul class="pagination">
            <li v-if="pagination.current_page > 1" class="page-item">
                <a class="page-link" href="#" aria-label="Previous"
                    @click.prevent="changePage(pagination.current_page - 1)">
                    <span aria-hidden="true">«</span>
                </a>
            </li>
            <li v-for="page in pagesNumber" class="page-item"
                v-bind:class="[ page == isActived ? 'active' : '']">
                <a class="page-link" href="#"
                    @click.prevent="changePage(page)">@{{ page }}</a>
            </li>
            <li v-if="pagination.current_page < pagination.last_page" class="page-item">
                <a class="page-link" href="#" aria-label="Next"
                    @click.prevent="changePage(pagination.current_page + 1)">
                    <span aria-hidden="true">»</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Create Round Modal -->
    <div class="modal fade" id="create-round" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            <h4 class="modal-title" id="myModalLabel">Create Round</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <form method="POST" enctype="multipart/form-data" v-on:submit.prevent="createRound">
                        <div class="col-md-12">
                            <div class="form-group row">
                                <label class="col-sm-4 form-control-label" for="title">Title:</label>
                                <div class="col-sm-8">
                                    <input type="text" name="name" class="form-control" v-model="newRound.name" />
                                    <span v-if="formErrors['name']" class="error text-danger">@{{ formErrors['name'] }}</span>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-4 form-control-label" for="title">Description:</label>
                                <div class="col-sm-8">
                                    <textarea name="description" class="form-control" v-model="newRound.description"></textarea>
                                    <span v-if="formErrors['description']" class="error text-danger">@{{ formErrors['description'] }}</span>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-4 form-control-label" for="title">Start Date:</label>
                                <div class="col-sm-8">
                                    <input type="date" name="start_date" class="form-control" v-model="newRound.start_date" />
                                    <span v-if="formErrors['start_date']" class="error text-danger">@{{ formErrors['start_date'] }}</span>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-4 form-control-label" for="title">End Date:</label>
                                <div class="col-sm-8">
                                    <input type="date" name="end_date" class="form-control" v-model="newRound.end_date" />
                                    <span v-if="formErrors['end_date']" class="error text-danger">@{{ formErrors['end_date'] }}</span>
                                </div>
                            </div>
                            <div class="form-group row col-sm-offset-4 col-sm-8">
                                <button type="submit" class="btn btn-sm btn-success"><i class='fa fa-plus-circle'></i> Submit</button>
                                <button type="button" class="btn btn-sm btn-silver" data-dismiss="modal" aria-label="Close"><span aria-hidden="true"><i class="fa fa-times-circle"></i> {!! trans('messages.cancel') !!}</span></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        </div>
    </div>

    <!-- Edit Round Modal -->
    <div class="modal fade" id="edit-round" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            <h4 class="modal-title" id="myModalLabel">Edit Round</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <form method="POST" enctype="multipart/form-data" v-on:submit.prevent="updateRound(fillRound.id)">
                        <div class="col-md-12">
                            <div class="form-group row">
                                <label class="col-sm-4 form-control-label" for="title">Title:</label>
                                <div class="col-sm-8">
                                    <input type="text" name="name" class="form-control" v-model="fillRound.name" />
                                    <span v-if="formErrorsUpdate['name']" class="error text-danger">@{{ formErrorsUpdate['name'] }}</span>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-4 form-control-label" for="title">Description:</label>
                                <div class="col-sm-8">
                                    <textarea name="description" class="form-control" v-model="fillRound.description"></textarea>
                                    <span v-if="formErrorsUpdate['description']" class="error text-danger">@{{ formErrorsUpdate['description'] }}</span>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-4 form-control-label" for="title">Start Date:</label>
                                <div class="col-sm-8">
                                    <input type="date" name="start_date" class="form-control" v-model="fillRound.start_date" />
                                    <span v-if="formErrorsUpdate['start_date']" class="error text-danger">@{{ formErrorsUpdate['start_date'] }}</span>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-4 form-control-label" for="title">End Date:</label>
                                <div class="col-sm-8">
                                    <input type="date" name="end_date" class="form-control" v-model="fillRound.end_date" />
                                    <span v-if="formErrorsUpdate['end_date']" class="error text-danger">@{{ formErrorsUpdate['end_date'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="form-group row col-sm-offset-4 col-sm-8">
                            <button type="submit" class="btn btn-sm btn-success"><i class='fa fa-plus-circle'></i> Submit</button>
                            <button type="button" class="btn btn-sm btn-silver" data-dismiss="modal" aria-label="Close"><span aria-hidden="true"><i class="fa fa-times-circle"></i> {!! trans('messages.cancel') !!}</span></button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
        </div>
    </div>

    <!-- Enrol Users Modal -->
    <div id="enrol-participants" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="myModalLabel">Enrol Participants</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4"></div>
                        <div class="col-md-4"></div>
                        <div class="col-md-4" style="padding-bottom:10px;">
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" placeholder="Search for..." v-model="psrch">
                                <span class="input-group-btn">
                                    <button class="btn btn-secondary" type="button" @click="srchEnrol()" v-if="!loading"><i class="fa fa-search"></i></button>
                                    <button class="btn btn-secondary" type="button" disabled="disabled" v-if="loading">Searching...</button>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <form method="POST" enctype="multipart/form-data" v-on:submit.prevent="enrolParticipants" id="partFrm">
                            <div class="col-md-12">
                                <input type="hidden" class="form-control" name="round_id" id="round-id" value=""/>
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Participant</th>
                                        <th>UID</th>
                                        <th>Facility</th>
                                        <th>Program</th>
                                    </tr>
                                    <tr v-for="participant in participants">
                                        <td>
                                            <input type="checkbox" :value="participant.id" name="usrs[]">
                                            @{{ participant.name }}
                                        </td>
                                        <td>@{{ participant.uid }}</td>
                                        <td>@{{ participant.facility }}</td>
                                        <td>@{{ participant.program }}</td>
                                    </tr>
                                </table>
                                <!-- Pagination -->
                                <nav>
                                    <ul class="pagination">
                                        <li v-if="pagination.current_page > 1" class="page-item">
                                            <a class="page-link" href="#" aria-label="Previous"
                                                @click.prevent="changePage(pagination.current_page - 1)">
                                                <span aria-hidden="true">«</span>
                                            </a>
                                        </li>
                                        <li v-for="page in pagesNumber" class="page-item"
                                            v-bind:class="[ page == isActived ? 'active' : '']">
                                            <a class="page-link" href="#"
                                                @click.prevent="changePage(page)">@{{ page }}</a>
                                        </li>
                                        <li v-if="pagination.current_page < pagination.last_page" class="page-item">
                                            <a class="page-link" href="#" aria-label="Next"
                                                @click.prevent="changePage(pagination.current_page + 1)">
                                                <span aria-hidden="true">»</span>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>

                                <div class="form-group row col-sm-offset-4 col-sm-8">
                                    <button type="submit" class="btn btn-sm btn-success"><i class='fa fa-plus-circle'></i> Submit</button>
                                    <button type="button" class="btn btn-sm btn-silver" data-dismiss="modal" aria-label="Close"><span aria-hidden="true"><i class="fa fa-times-circle"></i> {!! trans('messages.cancel') !!}</span></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!--View Enrolled Participants Modal -->
    <div id="enrolled-participants" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="myModalLabel">Enrolled Participants</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4"></div>
                        <div class="col-md-4"></div>
                        <div class="col-md-4" style="padding-bottom:10px;">
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" placeholder="Search for..." v-model="esrch">
                                <span class="input-group-btn">
                                    <button class="btn btn-secondary" type="button" @click="srchEnrol()" v-if="!loading"><i class="fa fa-search"></i></button>
                                    <button class="btn btn-secondary" type="button" disabled="disabled" v-if="loading">Searching...</button>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-bordered">
                                <tr>
                                    <th>Participant</th>
                                    <th>UID</th>
                                    <th>Facility</th>
                                    <th>Program</th>
                                </tr>
                                <tr v-for="enrol in testers">
                                    <td>@{{ enrol.name }}</td>
                                    <td>@{{ enrol.uid }}</td>
                                    <td>@{{ enrol.facility }}</td>
                                    <td>@{{ enrol.program }}</td>
                                </tr>
                            </table>
                            <!-- Pagination -->
                            <nav>
                                <ul class="pagination">
                                    <li v-if="pagination.current_page > 1" class="page-item">
                                        <a class="page-link" href="#" aria-label="Previous"
                                            @click.prevent="changePage(pagination.current_page - 1)">
                                            <span aria-hidden="true">«</span>
                                        </a>
                                    </li>
                                    <li v-for="page in pagesNumber" class="page-item"
                                        v-bind:class="[ page == isActived ? 'active' : '']">
                                        <a class="page-link" href="#"
                                            @click.prevent="changePage(page)">@{{ page }}</a>
                                    </li>
                                    <li v-if="pagination.current_page < pagination.last_page" class="page-item">
                                        <a class="page-link" href="#" aria-label="Next"
                                            @click.prevent="changePage(pagination.current_page + 1)">
                                            <span aria-hidden="true">»</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>

                            <div class="form-group row col-sm-offset-4 col-sm-8">
                                <button type="button" class="btn btn-sm btn-silver" data-dismiss="modal" aria-label="Close"><span aria-hidden="true"><i class="fa fa-times-circle"></i> {!! trans('messages.cancel') !!}</span></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
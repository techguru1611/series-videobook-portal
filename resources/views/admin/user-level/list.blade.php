@extends('layouts.admin-master')

@section('title')
    {{trans('title.USER_LEVEL_MANAGEMENT')}}
@endsection

@section('content')

    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body table-responsive">
                <h3>{{ trans('admin-labels.USER_LEVEL_MANAGEMENT') }}</h3>
                <a href="{{url('admin/user-levels/new')}}" class="btn btn-success btn-sm pull-right"> {{ trans('admin-labels.ADD_USER_LEVEL') }}</a>
                <br><br>
                
                <table id="listUserLevel" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                    <tr>
                        <th>{{trans('admin-labels.USER_LEVEL_TITLE')}}</th>
                        <th>{{trans('admin-labels.USER_LEVEL_PURCHASE')}}</th>
                        <th>{{trans('admin-labels.USER_LEVEL_PURCHASED_VIDEO_LENGTH')}}</th>
                        <th>{{trans('admin-labels.USER_LEVEL_STATUS')}}</th>
                        <th>{{trans('admin-labels.USER_LEVEL_ACTION')}}</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

@endsection


@section('style')
    <link rel="stylesheet" href="{{asset('admin/css/dataTables.bootstrap4.min.css')}}">
    <style type="text/css">
        table tr td a{
            font-size: 16px;
        }
    </style>
@endsection

@section('script')
    <script src="{{ asset('admin/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('admin/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('admin/js/sweetalert.min.js') }}"></script>

    <script type="text/javascript">
        var getUserLevelList = function (ajaxParams) {
            $('#listUserLevel').DataTable({
                "processing": true,
                "serverSide": true,
                "destroy": true,
                "ajax": {
                    "url": "{{ url('admin/user-levels/list-ajax') }}",
                    "dataType": "json",
                    "type": "POST",
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    "data": function (data) {
                        if (ajaxParams) {
                            $.each(ajaxParams, function (key, value) {
                                data[key] = value;
                            });
                            ajaxParams = {};
                        }
                    }
                },
                "columns": [
                    {"data": "title"},
                    {"data": "purchase"},
                    {"data": "purchased_video_length"},
                    {"data": "status"},
                    {"data": "action", "orderable": false}
                ]
            });
        };

        $(document).ready(function () {
            var ajaxParams = {};
            getUserLevelList(ajaxParams);

            // Remove Constituency
            $(document).on('click', '.btn-delete-user-level', function (e) {
                e.preventDefault();
                var userLevelId = $(this).attr('data-id');
                var cmessage = 'Are you sure you want to delete this user level ?';
                var ctitle = 'Delete User Level';

                ajaxParams.customActionName = 'delete';
                ajaxParams.customActionType = 'groupAction';            
                ajaxParams.id = [userLevelId];

                swal(cmessage, {
                    title: ctitle,
                    buttons: {
                        cancel: "Cancel",
                        catch: "Confirm!",
                    },
                })
                .then((value) => {
                    switch (value) {
                    
                        case "cancel":
                        break;
                        case "catch":
                            getUserLevelList(ajaxParams);
                            swal( "success", "{{trans('admin-message.USER_LEVEL_DELETED_SUCCESSFULLY_MESSAGE')}}", "success");
                        break;
                    }
                });                
            });

            // Change Status
            $(document).on('click', '.btn-status-user-level', function (e) {
                e.preventDefault();
                var userLevelId = $(this).attr('data-id');
                var cmessage = 'Are you sure you want to inactive this user level ?';
                var ctitle = 'Inactive User Level';

                if ($(this).attr('title') == 'Make Active') {
                    cmessage = 'Are you sure you want to active this user level ?';
                    ctitle = 'Active User Level';
                }

                ajaxParams.customActionType = 'groupAction';
                ajaxParams.customActionName = 'status';
                ajaxParams.id = [userLevelId];

                swal(cmessage, {
                    title: ctitle,
                    buttons: {
                        cancel: "Cancel",
                        catch: "Confirm!",
                    },
                })
                .then((value) => {
                    switch (value) {
                    
                        case "cancel":
                        break;
                        case "catch":
                            getUserLevelList(ajaxParams);
                            swal( "success", "{{trans('admin-message.USER_LEVEL_STATUS_UPDATED_SUCCESS_MESSAGE')}}", "success");
                        break;
                    }
                });                
            });
        });
    </script>
@endsection

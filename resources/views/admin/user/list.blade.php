@extends('layouts.admin-master')

@section('title')
    {{trans('title.USER_MANAGEMENT')}}
@endsection

@section('content')

    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body table-responsive">
                <h3>{{ trans('admin-labels.USER_MANAGEMENT') }}</h3>
                <a href="{{url('admin/users/new')}}" class="btn btn-success btn-sm pull-right"> {{ trans('admin-labels.ADD_USER') }}</a>
                <br><br>
                
                <table id="listUser" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                    <tr>
                        <th>{{trans('admin-labels.USER_FULL_NAME')}}</th>
                        <th>{{trans('admin-labels.USER_USER_NAME')}}</th>
                        <th>{{trans('admin-labels.USER_EMAIL')}}</th>
                        <th>{{trans('admin-labels.USER_PHONE_NO')}}</th>
                        <th>{{trans('admin-labels.USER_GENDER')}}</th>
                        <th>{{trans('admin-labels.USER_PHOTO')}}</th>
                        <th>{{trans('admin-labels.USER_USER_LEVEL')}}</th>
                        <th>{{trans('admin-labels.USER_STATUS')}}</th>
                        <th>{{trans('admin-labels.USER_ACTION')}}</th>
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
        var getUserList = function (ajaxParams) {
            $('#listUser').DataTable({
                "processing": true,
                "serverSide": true,
                "destroy": true,
                "ajax": {
                    "url": "{{ url('admin/users/list-ajax') }}",
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
                    {"data": "full_name"},
                    {"data": "username"},
                    {"data": "email"},
                    {"data": "phone_no"},
                    {"data": "gender"},
                    {"data": "photo", "orderable": false},
                    {"data": "title"},
                    {"data": "status"},
                    {"data": "action", "orderable": false}
                ]
            });
        };

        $(document).ready(function () {
            var ajaxParams = {};
            getUserList(ajaxParams);

            // Remove Constituency
            $(document).on('click', '.btn-delete-user', function (e) {
                e.preventDefault();
                var userId = $(this).attr('data-id');
                var cmessage = 'Are you sure you want to delete this user ?';
                var ctitle = 'Delete User';

                ajaxParams.customActionName = 'delete';
                ajaxParams.customActionType = 'groupAction';            
                ajaxParams.id = [userId];

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
                            getUserList(ajaxParams);
                            swal( "success", "{{trans('admin-message.USER_DELETED_SUCCESSFULLY_MESSAGE')}}", "success");
                        break;
                    }
                });                
            });

            // Change Status
            $(document).on('click', '.btn-status-user', function (e) {
                e.preventDefault();
                var userId = $(this).attr('data-id');
                var cmessage = 'Are you sure you want to inactive this user ?';
                var ctitle = 'Inactive User';

                if ($(this).attr('title') == 'Make Active') {
                    cmessage = 'Are you sure you want to active this user ?';
                    ctitle = 'Active User';
                }

                ajaxParams.customActionType = 'groupAction';
                ajaxParams.customActionName = 'status';
                ajaxParams.id = [userId];

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
                            getUserList(ajaxParams);
                            swal( "success", "{{trans('admin-message.USER_STATUS_UPDATED_SUCCESS_MESSAGE')}}", "success");
                        break;
                    }
                });                
            });
        });
    </script>
@endsection
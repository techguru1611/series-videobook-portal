@extends('layouts.admin-master')

@section('title')
    {{trans('title.CMS_MANAGEMENT')}}
@endsection

@section('content')
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body table-responsive">
                <h3>{{ trans('admin-labels.CMS_MANAGEMENT') }}</h3>
                <a href="{{url('admin/cms/new')}}" class="btn btn-success btn-sm pull-right"> {{ trans('admin-labels.ADD_CMS') }}</a>
                <br><br>
                <table id="listCms" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                    <tr>
                        <th>{{trans('admin-labels.CMS_NAME')}}</th>
                        <th>{{trans('admin-labels.CMS_SLUG')}}</th>
                        <th>{{trans('admin-labels.CMS_STATUS')}}</th>
                        <th>{{trans('admin-labels.CMS_ACTION')}}</th>
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
        var getCmsList = function (ajaxParams) {
            $('#listCms').DataTable({
                "processing": true,
                "serverSide": true,
                "destroy": true,
                "ajax": {
                    "url": "{{ url('admin/cms/list-ajax') }}",
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
                    {"data": "name"},
                    {"data": "slug"},
                    {"data": "status"},
                    {"data": "action", "orderable": false}
                ]
            });
        };

        $(document).ready(function () {
            var ajaxParams = {};
            getCmsList(ajaxParams);

            // Remove Cms
            $(document).on('click', '.btn-delete-cms', function (e) {
                e.preventDefault();
                var cmsId = $(this).attr('data-id');
                var cmessage = 'Are you sure you want to delete this cms ?';
                var ctitle = 'Delete cms';

                ajaxParams.customActionName = 'delete';
                ajaxParams.customActionType = 'groupAction';            
                ajaxParams.id = [cmsId];

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
                            getCmsList(ajaxParams);
                            swal( "success", "{{trans('admin-message.CMS_DELETED_SUCCESSFULLY_MESSAGE')}}", "success");
                        break;
                    }
                });                
            });

            // Change Status
            $(document).on('click', '.btn-status-cms', function (e) {
                e.preventDefault();
                var cmsId = $(this).attr('data-id');
                var cmessage = 'Are you sure you want to inactive this cms ?';
                var ctitle = 'Inactive cms';

                if ($(this).attr('title') == 'Make Active') {
                    cmessage = 'Are you sure you want to active this cms ?';
                    ctitle = 'Active cms';
                }

                ajaxParams.customActionType = 'groupAction';
                ajaxParams.customActionName = 'status';
                ajaxParams.id = [cmsId];

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
                            getCmsList(ajaxParams);
                            swal( "success", "{{trans('admin-message.CMS_STATUS_UPDATED_SUCCESS_MESSAGE')}}", "success");
                        break;
                    }
                });                
            });
        });
    </script>
@endsection
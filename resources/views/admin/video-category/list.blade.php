@extends('layouts.admin-master')

@section('title')
    {{trans('title.VIDEO_CATEGORY_MANAGEMENT')}}
@endsection

@section('content')

    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body table-responsive">
                <h3>{{ trans('admin-labels.VIDEO_CATEGORY_MANAGEMENT') }}</h3>
                <a href="{{url('admin/video-category/new')}}" class="btn btn-success btn-sm pull-right"> {{ trans('admin-labels.ADD_VIDEO_CATEGORY') }}</a>
                <br><br>
                
                <table id="listVideoCategory" class="table dataTable table-striped table-bordered" style="width:100%">
                    <thead>
                    <tr>
                        <th>{{trans('admin-labels.VIDEO_CATEGORY_TITLE')}}</th>
                        <th>{{trans('admin-labels.VIDEO_CATEGORY_DESCRIPTION')}}</th>
                        <th>{{trans('admin-labels.VIDEO_CATEGORY_IMAGE')}}</th>
                        <th>{{trans('admin-labels.VIDEO_CATEGORY_STATUS')}}</th>
                        <th>{{trans('admin-labels.VIDEO_CATEGORY_ACTION')}}</th>
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
        var getVideoCategoryList = function (ajaxParams) {
            $('#listVideoCategory').DataTable({
                "processing": true,
                "serverSide": true,
                "destroy": true,
                "ajax": {
                    "url": "{{ url('admin/video-category/list-ajax') }}",
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
                    {"data": "descr"},
                    {"data":"image"},
                    {"data": "status"},
                    {"data": "action", "orderable": false}
                ]
               
            });
        };

        $(document).ready(function () {
            var ajaxParams = {};
            getVideoCategoryList(ajaxParams);

            // Remove Constituency
            $(document).on('click', '.btn-delete-video-category', function (e) {
                e.preventDefault();
                var videoCategoryId = $(this).attr('data-id');
                var cmessage = 'Are you sure you want to delete this video category ?';
                var ctitle = 'Delete Video Category';

                ajaxParams.customActionName = 'delete';
                ajaxParams.customActionType = 'groupAction';            
                ajaxParams.id = [videoCategoryId];

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
                            getVideoCategoryList(ajaxParams);
                            swal( "success", "{{trans('admin-message.VIDEO_CATEGORY_DELETED_SUCCESSFULLY_MESSAGE')}}", "success");
                        break;
                    }
                });                
            });

            // Change Status
            $(document).on('click', '.btn-status-video-category', function (e) {
                e.preventDefault();
                var videoCategoryId = $(this).attr('data-id');
                var cmessage = 'Are you sure you want to inactive this video category ?';
                var ctitle = 'Inactive Video Category';

                if ($(this).attr('title') == 'Make Active') {
                    cmessage = 'Are you sure you want to active this video category ?';
                    ctitle = 'Active Video Category';
                }

                ajaxParams.customActionType = 'groupAction';
                ajaxParams.customActionName = 'status';
                ajaxParams.id = [videoCategoryId];

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
                            getVideoCategoryList(ajaxParams);
                            swal( "success", "{{trans('admin-message.VIDEO_CATEGORY_STATUS_UPDATED_SUCCESS_MESSAGE')}}", "success");
                        break;
                    }
                });                
            });
        });
    </script>
@endsection
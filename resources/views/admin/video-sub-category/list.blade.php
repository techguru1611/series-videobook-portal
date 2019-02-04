@extends('layouts.admin-master')

@section('title')
    {{trans('title.VIDEO_SUB_CATEGORY_MANAGEMENT')}}
@endsection

@section('content')

    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body table-responsive">
                <h3>{{ trans('admin-labels.VIDEO_SUB_CATEGORY_MANAGEMENT') }}</h3>
                <a href="{{url('admin/video-sub-category/new')}}" class="btn btn-success btn-sm pull-right"> {{ trans('admin-labels.ADD_VIDEO_SUB_CATEGORY') }}</a>
                <br><br>
                
                <table id="listVideoSubCategory" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                    <tr>
                        <th>{{trans('admin-labels.VIDEO_SUB_CATEGORY_CATEGORY_TITLE')}}</th>
                        <th>{{trans('admin-labels.VIDEO_SUB_CATEGORY_TITLE')}}</th>
                        <th>{{trans('admin-labels.VIDEO_SUB_CATEGORY_DESCRIPTION')}}</th>
                        <th>{{trans('admin-labels.VIDEO_SUB_CATEGORY_STATUS')}}</th>
                        <th>{{trans('admin-labels.VIDEO_SUB_CATEGORY_ACTION')}}</th>
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
        var getVideoSubCategoryList = function (ajaxParams) {
            $('#listVideoSubCategory').DataTable({
                "processing": true,
                "serverSide": true,
                "destroy": true,
                "ajax": {
                    "url": "{{ url('admin/video-sub-category/list-ajax') }}",
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
                    {"data": "video_category_title"},
                    {"data": "title"},
                    {"data": "descr"},
                    {"data": "status"},
                    {"data": "action", "orderable": false}
                ]
            });
        };

        $(document).ready(function () {
            var ajaxParams = {};
            getVideoSubCategoryList(ajaxParams);

            // Remove Constituency
            $(document).on('click', '.btn-delete-video-sub-category', function (e) {
                e.preventDefault();
                var videoSubCategoryId = $(this).attr('data-id');
                var cmessage = 'Are you sure you want to delete this video sub category ?';
                var ctitle = 'Delete Video Sub Category';

                ajaxParams.customActionName = 'delete';
                ajaxParams.customActionType = 'groupAction';            
                ajaxParams.id = [videoSubCategoryId];

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
                            getVideoSubCategoryList(ajaxParams);
                            swal( "success", "{{trans('admin-message.VIDEO_SUB_CATEGORY_DELETED_SUCCESSFULLY_MESSAGE')}}", "success");
                        break;
                    }
                });                
            });

            // Change Status
            $(document).on('click', '.btn-status-video-sub-category', function (e) {
                e.preventDefault();
                var videoSubCategoryId = $(this).attr('data-id');
                var cmessage = 'Are you sure you want to inactive this video sub category ?';
                var ctitle = 'Inactive Video Sub Category';

                if ($(this).attr('title') == 'Make Active') {
                    cmessage = 'Are you sure you want to active this video sub category ?';
                    ctitle = 'Active Video Sub Category';
                }

                ajaxParams.customActionType = 'groupAction';
                ajaxParams.customActionName = 'status';
                ajaxParams.id = [videoSubCategoryId];

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
                            getVideoSubCategoryList(ajaxParams);
                            swal( "success", "{{trans('admin-message.VIDEO_SUB_CATEGORY_STATUS_UPDATED_SUCCESS_MESSAGE')}}", "success");
                        break;
                    }
                });                
            });
        });
    </script>
@endsection
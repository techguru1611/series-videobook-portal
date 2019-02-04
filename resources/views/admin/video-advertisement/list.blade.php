@extends('layouts.admin-master')

@section('title')
    {{trans('title.VIDEO_ADVERTISEMENT_MANAGEMENT')}}
@endsection

@section('content')

    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body table-responsive">
                <h3>{{ trans('admin-labels.VIDEO_ADVERTISEMENT_MANAGEMENT') }}</h3>
                <a href="{{url('admin/video-advertisement/new')}}" class="btn btn-success btn-sm pull-right"> {{ trans('admin-labels.ADD_VIDEO_ADVERTISEMENT') }}</a>
                <br><br>
                
                <table id="listVideoAvertisement" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                    <tr>
                        <th>{{trans('admin-labels.VIDEO_ADVERTISEMENT_TITLE')}}</th>
                        <th>{{trans('admin-labels.VIDEO_ADVERTISEMENT_DESC')}}</th>
                        <th>{{trans('admin-labels.VIDEO_ADVERTISEMENT_IS_SKPIABLE')}}</th>
                        <th>{{trans('admin-labels.VIDEO_ADVERTISEMENT_SIZE')}}</th>
                        <th>{{trans('admin-labels.VIDEO_ADVERTISEMENT_POSITION')}}</th>
                        <th>{{trans('admin-labels.VIDEO_ADVERTISEMENT_STATUS')}}</th>
                        <th>{{trans('admin-labels.VIDEO_ADVERTISEMENT_ACTION')}}</th>
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
        var getVideoAdvertisementList = function (ajaxParams) {
            $('#listVideoAvertisement').DataTable({
                "processing": true,
                "serverSide": true,
                "destroy": true,
                "ajax": {
                    "url": "{{ url('admin/video-advertisement/list-ajax') }}",
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
                    {"data": "is_skipable"},
                    {"data": "size"},
                    {"data": "position"},
                    {"data": "status"},
                    {"data": "action", "orderable": false}
                ]
            });
        };

        $(document).ready(function () {
            var ajaxParams = {};
            getVideoAdvertisementList(ajaxParams);

            // Remove Constituency
            $(document).on('click', '.btn-delete-video-advertisement', function (e) {
                e.preventDefault();
                var videoBookId = $(this).attr('data-id');
                var cmessage = 'Are you sure you want to delete this video advertisement ?';
                var ctitle = 'Delete Video advertisement';

                ajaxParams.customActionName = 'delete';
                ajaxParams.customActionType = 'groupAction';            
                ajaxParams.id = [videoBookId];

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
                            getVideoAdvertisementList(ajaxParams);
                            swal( "success", "{{trans('admin-message.VIDEO_BOOK_DELETED_SUCCESSFULLY_MESSAGE')}}", "success");
                        break;
                    }
                });                
            });

            // Change Status
            $(document).on('click', '.btn-status-video-advertisement', function (e) {
                e.preventDefault();
                var videoBookId = $(this).attr('data-id');
                var cmessage = 'Are you sure you want to inactive this video advertisement ?';
                var ctitle = 'Inactive Video advertisement';

                if ($(this).attr('title') == 'Make Active') {
                    cmessage = 'Are you sure you want to active this video advertisement ?';
                    ctitle = 'Active Video advertisement';
                }

                ajaxParams.customActionType = 'groupAction';
                ajaxParams.customActionName = 'status';
                ajaxParams.id = [videoBookId];

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
                            getVideoAdvertisementList(ajaxParams);
                            swal( "success", "{{trans('admin-message.VIDEO_BOOK_STATUS_UPDATED_SUCCESS_MESSAGE')}}", "success");
                        break;
                    }
                });                
            });
        });
    </script>
@endsection
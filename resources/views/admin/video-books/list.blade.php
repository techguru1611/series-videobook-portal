@extends('layouts.admin-master')

@section('title')
    {{trans('title.VIDEO_BOOK_MANAGEMENT')}}
@endsection

@section('content')

    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body table-responsive">
                <h3>{{ trans('admin-labels.VIDEO_BOOK_MANAGEMENT') }}</h3>
                <a href="{{url('admin/video-books/new')}}" class="btn btn-success btn-sm pull-right"> {{ trans('admin-labels.ADD_VIDEO_BOOK') }}</a>
                <br><br>
                
                <table id="listVideoBook" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                    <tr>
                        <th>{{trans('admin-labels.VIDEO_BOOK_CATEGORY_TITLE')}}</th>
                        <th>{{trans('admin-labels.VIDEO_BOOK_AUTHER_NAME')}}</th>
                        <th>{{trans('admin-labels.VIDEO_BOOK_TITLE')}}</th>
                        <th>{{trans('admin-labels.VIDEO_SERIES_IMAGE')}}</th>
                        <th>{{trans('admin-labels.VIDEO_BOOK_DESCRIPTION')}}</th>
                        <th>{{trans('admin-labels.VIDEO_BOOK_PRICE')}}</th>
                        <th>{{trans('admin-labels.VIDEO_BOOK_TOTAL_PURCHASED')}}</th>
                        <th>{{trans('admin-labels.VIDEO_BOOK_AUTHOR_PROFIT')}}</th>
                        <th>{{trans('admin-labels.VIDEO_BOOK_STATUS')}}</th>
                        <th>{{trans('admin-labels.VIDEO_BOOK_ACTION')}}</th>
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
        var table = $('#listVideoBook').DataTable({
            responsive: true,
        });
        var getVideoBookList = function (ajaxParams) {
            $('#listVideoBook').DataTable({
                "processing": true,
                "serverSide": true,
                "destroy": true,
                "ajax": {
                    "url": "{{ url('admin/video-books/list-ajax') }}",
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
                    },
                    dataFilter: function(reps) {
                        var res = JSON.parse(reps);
                        if(res.err){
                            swal( "error", res.err, "error");
                        }

                        if(res.message){
                            swal( "success", res.message, "success");
                        }

                        return reps;
                    },

                },
                "columns": [
                    {"data": "video_category_title"},
                    {"data": "full_name"},
                    {"data": "title"},
                    {"data": "image"},
                    {"data": "descr"},
                    {"data": "price"},
                    {"data": "total_download"},
                    {"data": "author_profit_in_percentage"},
                    {"data": "status"},
                    {"data": "action", "orderable": false}
                ],
            });
        };

        $(document).ready(function () {
            var ajaxParams = {};
            getVideoBookList(ajaxParams);

            // Remove Constituency
            $(document).on('click', '.btn-delete-video-books', function (e) {
                e.preventDefault();
                var videoBookId = $(this).attr('data-id');
                var cmessage = 'Are you sure you want to delete this video book ?';
                var ctitle = 'Delete Video Book';

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
                            getVideoBookList(ajaxParams);
                            swal( "success", "{{trans('admin-message.VIDEO_BOOK_DELETED_SUCCESSFULLY_MESSAGE')}}", "success");
                        break;
                    }
                });                
            });

            // Change Status
            $(document).on('click', '.btn-status-video-books', function (e) {
                e.preventDefault();
                var videoBookId = $(this).attr('data-id');
                var cmessage = 'Are you sure you want to inactive this video book ?';
                var ctitle = 'Inactive Video Book';

                if ($(this).attr('title') == 'Make Active') {
                    cmessage = 'Are you sure you want to active this video book ?';
                    ctitle = 'Active Video Book';
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
                            getVideoBookList(ajaxParams);
                        break;
                    }
                });                
            });
        });
    </script>
@endsection

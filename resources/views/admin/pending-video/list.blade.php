@extends('layouts.admin-master')

@section('title')
    {{trans('title.PENDING_VIDEOS')}}
@endsection

@section('content')

    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body table-responsive">
                <h3>{{ trans('admin-labels.PENDING_VIDEOS') }}</h3>
                
                <table id="listVideoBook" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                    <tr>
                        <th>{{trans('admin-labels.VIDEO_TITLE')}}</th>
                        <th>{{trans('admin-labels.VIDEO_DESCRIPTION')}}</th>
                        <th>{{trans('admin-labels.VIDEO_SERIES_TITLE')}}</th>
                        <th>{{trans('admin-labels.VIDEO_BOOK_AUTHER_NAME')}}</th>
                        <th>{{trans('admin-labels.VIDEO_THUMB')}}</th>
                        <th>{{trans('admin-labels.TRANSCODE_STATUS')}}</th>
                        <th>{{trans('admin-labels.APPROVAL')}}</th>
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
                    "url": "{{ url('admin/pending-video/list-ajax') }}",
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
                    {"data": "video_book_title"},
                    {"data": "full_name"},
                    {"data": "thumb"},
                    {"data": "is_transacoded"},
                    {"data": "status"},
                    {"data": "action", "orderable": false}
                ]
            });
        };

        $(document).ready(function () {
            var ajaxParams = {};
            getVideoBookList(ajaxParams);

            // Change Status
            $(document).on('click', '.btn-status-video-books', function (e) {
                e.preventDefault();
                var videoBookId = $(this).attr('data-id');
                var cmessage = 'Are you sure you want to inactive this video ?';
                var ctitle = 'Inactive Video';

                if ($(this).attr('title') == 'Make Approve') {
                    cmessage = 'Are you sure you want to approve this video?';
                    ctitle = 'Approve Video';
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
                            swal( "success", "{{trans('admin-message.VIDEO_APPROVED_SUCCESSFULLY')}}", "success");
                        break;
                    }
                });                
            });
        });

    </script>
@endsection

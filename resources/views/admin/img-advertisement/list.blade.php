@extends('layouts.admin-master')

@section('title')
    {{trans('title.IMAGE_ADVERTISEMENT_MANAGEMENT')}}
@endsection

@section('content')

    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body table-responsive">
                <h3>{{ trans('admin-labels.IMAGE_ADVERTISEMENT_MANAGEMENT') }}</h3>
                <a href="{{url('admin/img-advertisement/new')}}" class="btn btn-success btn-sm pull-right"> {{ trans('admin-labels.ADD_IMAGE_ADVERTISEMENT') }}</a>
                <br><br>
                
                <table id="listVideoCategory" class="table dataTable table-striped table-bordered" style="width:100%">
                    <thead>
                    <tr>
                        <th>{{trans('admin-labels.IMAGE_ADVERTISEMENT_TITLE')}}</th>
                        <th>{{trans('admin-labels.IMAGE_ADVERTISEMENT_IMAGE')}}</th>
                        <th>{{trans('admin-labels.IMAGE_ADVERTISEMENT_ORDER')}}</th>
                        <th>{{trans('admin-labels.IMAGE_ADVERTISEMENT_STATUS')}}</th>
                        <th>{{trans('admin-labels.IMAGE_ADVERTISEMENT_ACTION')}}</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-2"></div>
                    <div class="col-md-8">
                        <div class="response"></div>
                        <h5>Set The Order Of Image Advertisement</h5>
                        <ul id="sortable2" class="connectedSortable">
                            @if(isset($imgAd) && !empty($imgAd))
                                @foreach($imgAd as $ad)
                                    <div id="{{$ad->id}}" class="alert fade show text-white content bg-info">
                                        <h4>{{$ad->title}}</h4>
                                    </div>
                                @endforeach
                            @endif
                        </ul>
                    </div>
                    <div class="col-md-2"></div>
                    <div class="col-md-12">
                        <button class="btn btn-success btn-lg" id="saveTestContent">Save Order</button>
                    </div>
                </div>

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

        #sortable2{
            list-style-type: none;
            background: #eee;
            padding: 20px;
            width: 100%;
            text-align: left;
            min-height: 200px;
        }
        .placeholder_drag{
            min-height: 50px;
            background: #0c85d0;
            border-radius: 5px;
            margin-bottom: 10px;
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
                    "url": "{{ url('admin/img-advertisement/list-ajax') }}",
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

                    {"data": "image","orderable": false},
                    {"data": "img_order"},
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
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script type="text/javascript">
        $( function() {
            $( "#sortable2" ).sortable({
                placeholder: "placeholder_drag"
            }).disableSelection();

            $("#saveTestContent").click(function(){
                var sortedIDs = $( "#sortable2" ).sortable( "toArray" );
                $.ajax({
                    type: "POST",
                    data: {
                        orderdId: sortedIDs,
                        _token: "{{csrf_token()}}"
                    },

                    url: "{{ route('save-image-ad-order') }}",
                    success: function(res){
                        if (res == 'save'){
                            $('.response').append('<div class="alert alert-success alert-dismissible fade show">\n' +
                                '                        Order Saved Successfully' +
                                '                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">\n' +
                                '                            <span aria-hidden="true">&times;</span>\n' +
                                '                        </button>\n' +
                                '                    </div>')
                            location.reload();
                        }else{
                            $('.response').append('<div class="alert alert-danger alert-dismissible fade show">\n' +
                                '                        Something Went Wrong?' +
                                '                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">\n' +
                                '                            <span aria-hidden="true">&times;</span>\n' +
                                '                        </button>\n' +
                                '                    </div>')
                        }

                    }
                });
            });

        });
    </script>

@endsection

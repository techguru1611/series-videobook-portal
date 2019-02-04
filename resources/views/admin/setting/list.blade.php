@extends('layouts.admin-master')

@section('title')
    {{trans('title.SETTINGS_MANAGEMENT')}}
@endsection

@section('content')
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body table-responsive">
                <h3>{{ trans('admin-labels.SETTINGS_MANAGEMENT') }}</h3>
                    <table id="listSettings" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                    <tr>
                        <th>{{trans('admin-labels.SETTING_NAME')}}</th>
                        <th>{{trans('admin-labels.SETTING_SLUG')}}</th>
                        <th>{{trans('admin-labels.VIDEO_SERIES_TITLE')}}</th>
                        <th>{{trans('admin-labels.SETTING_STATUS')}}</th>
                        <th>{{trans('admin-labels.SETTING_ACTION')}}</th>
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
        var getSettingList = function (ajaxParams) {
            $('#listSettings').DataTable({
                "processing": true,
                "serverSide": true,
                "destroy": true,
                "ajax": {
                    "url": "{{ url('admin/settings/list-ajax') }}",
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
                    {"data": "slug"},
                    {"data": "video_series_title"},
                    {"data": "status"},
                    {"data": "action", "orderable": false}
                ]
            });
        };

        $(document).ready(function () {
            var ajaxParams = {};
            getSettingList(ajaxParams);

            // Change Status
            $(document).on('click', '.btn-status-setting', function (e) {
                e.preventDefault();
                var settingId = $(this).attr('data-id');
                var cmessage = 'Are you sure you want to inactive this settings ?';
                var ctitle = 'Inactive settings';

                if ($(this).attr('title') == 'Make Active') {
                    cmessage = 'Are you sure you want to active this settings ?';
                    ctitle = 'Active settings';
                }

                ajaxParams.customActionType = 'groupAction';
                ajaxParams.customActionName = 'status';
                ajaxParams.id = [settingId];

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
                            getSettingList(ajaxParams);
                            swal( "success", "{{trans('admin-message.SETTING_STATUS_UPDATED_SUCCESS_MESSAGE')}}", "success");
                        break;
                    }
                });                
            });
        });
    </script>
@endsection
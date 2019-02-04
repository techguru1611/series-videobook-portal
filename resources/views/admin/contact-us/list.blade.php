@extends('layouts.admin-master')

@section('title')
    {{trans('title.CONTACT_US')}}
@endsection

@section('content')
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body table-responsive">
                <h3>{{ trans('admin-labels.CONTACT_US') }}</h3>
                <table id="listCms" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                    <tr>
                        <th>{{trans('admin-labels.USER_EMAIL')}}</th>
                        <th>{{trans('admin-labels.USER_MASSAGE')}}</th>
                        <th>{{trans('admin-labels.CREATED_AT')}}</th>
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
                    "url": "{{ url('admin/contact-us/list-ajax') }}",
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
                    {"data": "email"},
                    {"data": "description"},
                    {"data": "created_at"}
                ]
            });
        };

        $(document).ready(function () {
            var ajaxParams = {};
            getCmsList(ajaxParams);
        });
    </script>
@endsection

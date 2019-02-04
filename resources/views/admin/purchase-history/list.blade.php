@extends('layouts.admin-master')

@section('title')
    {{trans('title.PURCHASE_HISTORY')}}
@endsection

@section('content')
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body table-responsive">
                <h3>{{ trans('admin-labels.PURCHASE_HISTORY') }}</h3>
                <table id="listCms" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                    <tr>
                        <th>{{trans('admin-labels.VIDEO_SERIES_NAME')}}</th>
                        <th>{{trans('admin-labels.AUTHOR')}}</th>
                        <th>{{trans('admin-labels.USER')}}</th>
                        <th>{{trans('admin-labels.PRICE')}}</th>
                        <th>{{trans('admin-labels.AUTHOR_PROFIT')}}</th>
                        <th>{{trans('admin-labels.PURCHASED_TR_ID')}}</th>
                        <th>{{trans('admin-labels.PAYOUT_TR_ID')}}</th>
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

    <script type="text/javascript">
        var getCmsList = function (ajaxParams) {
            $('#listCms').DataTable({
                "processing": true,
                "serverSide": true,
                "destroy": true,
                "ajax": {
                    "url": "{{ url('admin/purchase-history/list-ajax') }}",
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
                    {"data": "e_video_book_title"},
                    {"data": "author_name"},
                    {"data": "user_name"},
                    {"data": "price"},
                    {"data": "author_price"},
                    {"data": "purchase_transaction_id"},
                    {"data": "payout_transaction_id"},
                ]
            });
        };

        $(document).ready(function () {
            var ajaxParams = {};
            getCmsList(ajaxParams);
        });
    </script>
@endsection

@if (session('success'))
<div class="row success-msg">
    <div class="col-md-12">
        <div class="box-body">
            <div class="alert alert-success alert-dismissable fade show">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4><i class="icon-check"></i> {{trans('admin-labels.SUCCESS')}}</h4>
                {{ session('success') }}
            </div>
        </div>
    </div>
</div>
@endif

@if (session('error'))
<div class="row error-msg">
    <div class="col-md-12">
        <div class="box-body">
            <div class="alert alert-danger danger alert-dismissable fade show">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4><i class="icon-close"></i> {{trans('admin-labels.ERROR')}}</h4>
                {{ session('error') }}
            </div>
        </div>
    </div>
</div>
@endif
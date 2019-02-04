@extends('layouts.admin-master')

@section('title')
    {{trans('title.ADD_CMS')}}
@endsection

@section('content')

    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h3>
                    @if($cms->id > 0)
                        {{trans('title.EDIT_CMS')}}
                    @else
                        {{trans('title.ADD_CMS')}}
                    @endif
                </h3>

                <form class="form-groups" role="form" method="post" action="{{ url('admin/cms/set') }}">
                    @csrf
                    <?php $id = ($cms->id > 0 && isset($cms)) ? $cms['id'] : '0'; ?>
                    <input type="hidden" name="id" value="{{\App\Services\UrlService::base64UrlEncode($id)}}">

                    <!-- cms name -->
                    <div class="form-group row">
                        <label class="col-md-2 control-label" for="name">{{trans('admin-labels.CMS_NAME')}}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" id="name" placeholder="{{trans('place-holder.CMS_NAME')}}" name="name" value="{{ old('name') ? old('name') : $cms['name'] }}">
                            @if($errors->has('name'))
                                <div class="invalid-feedback">
                                    {{$errors->first('name')}}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- cms slug -->
                    @if($id == 0) <!-- check id is 0 to add new value other wise not display -->
                    <div class="form-group row">
                        <label class="col-md-2 control-label" for="slug">{{trans('admin-labels.CMS_SLUG')}}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control {{ $errors->has('slug') ? 'is-invalid' : '' }}" id="slug" placeholder="{{trans('place-holder.CMS_SLUG')}}" name="slug" value="{{ old('slug') ? old('slug') : $cms['slug'] }}" readonly>
                            @if($errors->has('slug'))
                                <div class="invalid-feedback">
                                    {{$errors->first('slug')}}
                                </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- value -->
                    <div class="form-group row">
                        <label class="col-md-2 control-label" for="value">{{trans('admin-labels.CMS_VALUE')}}</label>
                        <div class="col-md-6">
                            <textarea class="form-control {{ $errors->has('value') ? 'is-invalid' : '' }}" id="value" placeholder="{{trans('place-holder.CMS_VALUE')}}" name="value">{{ old('value') ? old('value') : $cms['value'] }}</textarea>
                            <span id="valueError" class="error"></span>
                            @if($errors->has('value'))
                                <div class="invalid-feedback">
                                    {{$errors->first('value')}}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="form-group row">
                        <label class="col-md-2 control-label" for="status" class="col-md-2 control-label"> {{ trans('admin-labels.CMS_STATUS') }} </label>
                        <div class="col-md-6">
                            <select class="form-control" id="status" name="status">
                                @foreach ($status as $_status)
                                    <option value="{{$_status['value']}}" @if($_status['value'] == ( old('status') ? old('status') : $cms->status )) selected="selected" @endif >{{$_status['name']}}</option>
                                @endforeach
                            </select>
                            @if($errors->has('status'))
                                <div class="invalid-feedback">
                                    {{$errors->first('status')}}
                                </div>
                            @endif
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success mr-2">{{ trans('admin-labels.SUBMIT') }}</button>
                    <a href="{{ url('admin/cms') }}" class="btn btn-light">{{ trans('admin-labels.CANCEL') }}</a>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('script')
<script src="{{ asset('plugins/ckeditor/ckeditor.js') }}"></script>
<script>
    CKEDITOR.replace( 'value' );
    $("form").submit( function(e) {
        var messageLength = CKEDITOR.instances['value'].getData().replace(/<[^>]*>/gi, '').length;
        if( !messageLength ) {
            $('#valueError').html('This field is required');
            return false;
        }else{            
            $('#valueError').remove(); 
            return true;
        }
    });
    $(document).ready(function() { 
        $('#name').keyup(function(){
            var name = $('#name').val();
            name = name.toLowerCase();
            var trimmed = $.trim(name);
            var slug = trimmed.replace(/[^a-z0-9-]/gi, '-').replace(/-+/g, '_').replace(/^-|-$/g, '');
            $('#slug').val(slug);
        });
    });
</script>
@endsection
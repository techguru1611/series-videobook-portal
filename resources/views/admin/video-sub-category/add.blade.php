@extends('layouts.admin-master')

@section('title')
    {{trans('title.ADD_VIDEO_SUB_CATEGORY')}}
@endsection

@section('content')

    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                @if($videoSubCategory->id > 0)
                    {{trans('title.EDIT_VIDEO_SUB_CATEGORY')}}
                @else
                    {{trans('title.ADD_VIDEO_SUB_CATEGORY')}}
                @endif

                <form class="form-groups" role="form" id="videoSubCategory" method="post" action="{{ url('admin/video-sub-category/set') }}">
                    @csrf
                    <?php $id = ($videoSubCategory->id > 0 && isset($videoSubCategory)) ? $videoSubCategory['id'] : '0'; ?>
                    <input type="hidden" name="id" value="{{\App\Services\UrlService::base64UrlEncode($id)}}">

                    <!-- Video Category -->
                    <div class="form-group row">
                        <label class="col-md-2 control-label" for="video_category_id" class="col-md-2 control-label"> {{ trans('admin-labels.VIDEO_SUB_CATEGORY_CATEGORY_TITLE') }} </label>
                        <div class="col-md-6">
                            <select class="form-control" id="video_category_id" name="video_category_id">
                                <option value="0">{{ trans('admin-labels.SELECT_VIDEO_CATEGORY') }}</option>
                                @foreach ($videoCategory as $_videoCategory)
                                    <option value="{{$_videoCategory['id']}}" @if($_videoCategory['id'] == ( old('video_category_id') ? old('video_category_id') : $videoSubCategory->video_category_id )) selected="selected" @endif >{{$_videoCategory->title}}</option>
                                @endforeach
                            </select>
                            @if($errors->has('video_category_id'))
                                <div class="invalid-feedback">
                                    {{$errors->first('video_category_id')}}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Video Sub Category Title -->
                    <div class="form-group row">
                        <label class="col-md-2 control-label" for="title">{{trans('admin-labels.VIDEO_SUB_CATEGORY_TITLE')}}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control {{ $errors->has('title') ? 'is-invalid' : '' }}" id="title" placeholder="{{trans('place-holder.VIDEO_SUB_CATEGORY_TITLE')}}" name="title" value="{{ old('title') ? old('title') : $videoSubCategory['title'] }}">
                            @if($errors->has('title'))
                                <div class="invalid-feedback">
                                    {{$errors->first('title')}}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Video Sub Category Description -->
                    <div class="form-group row">
                        <label class="col-md-2 control-label" for="descr">{{trans('admin-labels.VIDEO_SUB_CATEGORY_DESCRIPTION')}}</label>
                        <div class="col-md-6">
                            <textarea class="form-control {{ $errors->has('descr') ? 'is-invalid' : '' }}" id="descr" placeholder="{{trans('place-holder.VIDEO_SUB_CATEGORY_DESCRIPTION')}}" name="descr">{{ old('descr') ? old('descr') : $videoSubCategory['descr'] }}</textarea>
                            @if($errors->has('descr'))
                                <div class="invalid-feedback">
                                    {{$errors->first('descr')}}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="form-group row">
                        <label class="col-md-2 control-label" for="status" class="col-md-2 control-label"> {{ trans('admin-labels.VIDEO_SUB_CATEGORY_STATUS') }} </label>
                        <div class="col-md-6">
                            <select class="form-control" id="status" name="status">
                                @foreach ($status as $_status)
                                    <option value="{{$_status['value']}}" @if($_status['value'] == ( old('status') ? old('status') : $videoSubCategory->status )) selected="selected" @endif >{{$_status['name']}}</option>
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
                    <a href="{{ url('admin/video-sub-category') }}" class="btn btn-light">{{ trans('admin-labels.CANCEL') }}</a>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('script')
    <script type="text/javascript">
        $(document).ready(function() {
            $("#videoSubCategory").validate({
                ignore: ":hidden:not(select)",
                rules: {
                    title: {
                        required: true
                    },
                    descr :{
                       required: true 
                    },
                    status: {
                        required: true
                    },  
                }
            });
        });
    </script>
@endsection
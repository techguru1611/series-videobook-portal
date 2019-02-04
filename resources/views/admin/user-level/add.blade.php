@extends('layouts.admin-master')

@section('title')
    {{trans('title.ADD_USER_LEVEL')}}
@endsection

@section('content')

    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                @if($userLevel->id > 0)
                    {{trans('title.EDIT_USER_LEVEL')}}
                @else
                    {{trans('title.ADD_USER_LEVEL')}}
                @endif

                <form class="form-groups" role="form" id="userLevel" method="post" action="{{ url('admin/user-levels/set') }}">
                    @csrf
                    <?php $id = ($userLevel->id > 0 && isset($userLevel)) ? $userLevel['id'] : '0'; ?>
                    <input type="hidden" name="id" value="{{\App\Services\UrlService::base64UrlEncode($id)}}">

                    <!-- User Level Title -->
                    <div class="form-group row">
                        <label class="col-md-2 control-label" for="title">{{trans('admin-labels.USER_LEVEL_TITLE')}}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control {{ $errors->has('title') ? 'is-invalid' : '' }}" id="title" placeholder="{{trans('place-holder.USER_LEVEL_TITLE')}}" name="title" value="{{ old('title') ? old('title') : $userLevel['title'] }}">
                            @if($errors->has('title'))
                                <div class="invalid-feedback">
                                    {{$errors->first('title')}}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- User Level Purchase -->
                    <div class="form-group row">
                        <label class="col-md-2 control-label" for="purchase">{{trans('admin-labels.USER_LEVEL_PURCHASE')}}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control {{ $errors->has('purchase') ? 'is-invalid' : '' }}" id="purchase" placeholder="{{trans('place-holder.USER_LEVEL_PURCHASE')}}" name="purchase" value="{{ old('purchase') ? old('purchase') : $userLevel['purchase'] }}">
                            @if($errors->has('purchase'))
                                <div class="invalid-feedback">
                                    {{$errors->first('purchase')}}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- User Level Watched In Minutes -->
                    <div class="form-group row">
                        <label class="col-md-2 control-label" for="purchased_video_length">{{trans('admin-labels.USER_LEVEL_PURCHASED_VIDEO_LENGTH')}}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control {{ $errors->has('purchased_video_length') ? 'is-invalid' : '' }}" id="purchased_video_length" placeholder="{{trans('place-holder.USER_LEVEL_PURCHASED_VIDEO_LENGTH')}}" name="purchased_video_length" value="{{ old('purchased_video_length') ? old('purchased_video_length') : $userLevel['purchased_video_length'] }}">
                            @if($errors->has('purchased_video_length'))
                                <div class="invalid-feedback">
                                    {{$errors->first('purchased_video_length')}}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="form-group row">
                        <label class="col-md-2 control-label" for="status" class="col-md-2 control-label"> {{ trans('admin-labels.USER_LEVEL_STATUS') }} </label>
                        <div class="col-md-6">
                            <select class="form-control" id="status" name="status">
                                @foreach ($status as $_status)
                                    <option value="{{$_status['value']}}" @if($_status['value'] == ( old('status') ? old('status') : $userLevel->status )) selected="selected" @endif >{{$_status['name']}}</option>
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
                    <a href="{{ url('admin/user-levels') }}" class="btn btn-light">{{ trans('admin-labels.CANCEL') }}</a>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('script')
    <script type="text/javascript">
        $(document).ready(function() {
            $("#userLevel").validate({
                ignore: ":hidden:not(select)",
                rules: {
                    title: {
                        required: true
                    },
                    wached_in_minutes :{
                       required: true 
                    },
                    purchase :{
                       required: true
                    },
                    country_code :{
                       required: true 
                    },
                    phone_no :{
                       required: true,
                       number: true
                    },
                    gender :{
                       required: true 
                    },
                    birth_date:{
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

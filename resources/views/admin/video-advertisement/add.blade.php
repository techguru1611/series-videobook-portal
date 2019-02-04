@extends('layouts.admin-master')

@section('title')
    {{trans('title.ADD_VIDEO_ADVERTISEMENT')}}
@endsection

@section('content')

    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                @if($videoAdvertisement->id > 0)
                    {{trans('title.EDIT_VIDEO_ADVERTISEMENT')}}
                @else
                    {{trans('title.ADD_VIDEO_ADVERTISEMENT')}}
                @endif

                <form class="form-groups" role="form" id="videoAdvertisement" method="post" action="{{ url('admin/video-advertisement/set') }}" enctype="multipart/form-data">
                    @csrf
                    <?php $id = ($videoAdvertisement->id > 0 && isset($videoAdvertisement)) ? $videoAdvertisement['id'] : '0'; ?>
                    <input type="hidden" name="id" value="{{\App\Services\UrlService::base64UrlEncode($id)}}">

                    <!-- Video Advertisement Title -->
                    <div class="form-group row">
                        <label class="col-md-2 control-label" for="title">{{trans('admin-labels.VIDEO_ADVERTISEMENT_TITLE')}}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control {{ $errors->has('title') ? 'is-invalid' : '' }}" id="title" placeholder="{{trans('place-holder.VIDEO_ADVERTISEMENT_TITLE')}}" name="title" value="{{ old('title') ? old('title') : $videoAdvertisement['title'] }}">
                            @if($errors->has('title'))
                                <div class="invalid-feedback">
                                    {{$errors->first('title')}}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Video Advertisement Description -->
                    <div class="form-group row">
                        <label class="col-md-2 control-label" for="descr">{{trans('admin-labels.VIDEO_BOOK_DESCRIPTION')}}</label>
                        <div class="col-md-6">
                            <textarea class="form-control {{ $errors->has('descr') ? 'is-invalid' : '' }}" id="descr" placeholder="{{trans('place-holder.VIDEO_ADVERTISEMENT_DESC')}}" name="descr">{{ old('descr') ? old('descr') : $videoAdvertisement['descr'] }}</textarea>
                            @if($errors->has('descr'))
                                <div class="invalid-feedback">
                                    {{$errors->first('descr')}}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Video advertisement video -->
                    <div class="form-group row">
                        <label class="col-md-2 control-label" for="path">{{trans('admin-labels.VIDEO_ADVERTISEMENT_PATH')}}</label>
                        <div class="col-md-6">
                            <input type="file" class="form-control" id="path" name="path">
                        </div>
                    </div>

                    <!-- Video Advertisement is skipable -->
                    <?php $is_skipable = (old('is_skipable') ? old('is_skipable') : (($videoAdvertisement->id) ? $videoAdvertisement->is_skipable : '0')); ?>
                    <div class="form-group row">
                        <label class="col-md-2 control-label" for="is_skipable">{{trans('admin-labels.VIDEO_ADVERTISEMENT_IS_SKPIABLE')}}</label>
                        <div class="col-md-6 form-group row">
                            <div class="col-md-4">
                                <div class="form-radio">
                                    <label class="form-check-label">
                                        <input type="radio" class="form-control" id="1" name="is_skipable" value="1" @if(isset($id) && ( old('is_skipable') ? old('is_skipable') : $is_skipable ) == '1') checked @endif >{{ trans('admin-labels.YES') }}
                                        <i class="input-helper"></i>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-radio">
                                    <label class="form-check-label">
                                        <input type="radio" class="form-control" id="2" name="is_skipable" value="0" @if(isset($id) && ( old('is_skipable') ? old('is_skipable') : $is_skipable ) == '0') checked @endif>{{ trans('admin-labels.NO') }}
                                        <i class="input-helper"></i>
                                    </label>
                                </div>
                            </div>
                            @if($errors->has('is_skipable'))
                                <div class="invalid-feedback">
                                    {{$errors->first('is_skipable')}}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Video Advertisement Position-->
                    <div class="form-group row">
                        <label class="col-md-2 control-label" for="position">{{trans('admin-labels.VIDEO_ADVERTISEMENT_POSITION')}}</label>
                        <div class="col-md-6">
                        <select class="form-control" id="position" name="position" data-select2-id="s2_demo1" tabindex="-1" aria-hidden="true">
                            @foreach($position as $_position)
                                    <option value="{{$_position['value']}}">{{$_position['name']}}</option>
                            @endforeach                         
                        </select>
                    </div>
                    </div>

                    <!-- Video Advertisement skipable after -->
                    <div class="form-group row">
                        <label class="col-md-2 control-label" for="skipale_after">{{trans('admin-labels.VIDEO_SKIPABLE_AFTER')}}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control allownumericwithdecimal {{  $errors->has('skipale_after') ? 'is-invalid' : '' }}" id="skipale_after" placeholder="{{trans('place-holder.VIDEO_SKIPABLE_AFTER')}}" name="skipale_after" value="{{ old('skipale_after') ? old('skipale_after') : $videoAdvertisement['skipale_after'] }}">
                            @if($errors->has('skipale_after'))
                                <div class="invalid-feedback">
                                    {{$errors->first('skipale_after')}}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Status --> 
                    <div class="form-group row">
                        <label class="col-md-2 control-label" for="status" class="col-md-2 control-label"> {{ trans('admin-labels.VIDEO_BOOK_STATUS') }} </label>
                        <div class="col-md-6">
                            <select class="form-control" id="status" name="status">
                                @foreach ($status as $_status)
                                    <option value="{{$_status['value']}}" @if($_status['value'] == ( old('status') ? old('status') : $videoAdvertisement->status )) selected="selected" @endif >{{$_status['name']}}</option>
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
                    <a href="{{ url('admin/video-advertisement') }}" class="btn btn-light">{{ trans('admin-labels.CANCEL') }}</a>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('script')
    <script type="text/javascript">
        $(document).ready(function() {
            
            // Allow numeric with decimal only
            $(".allownumericwithdecimal").on("keypress keyup blur",function (event) {
                $(this).val($(this).val().replace(/[^0-9\.]/g,''));
                if ((event.which != 46 || $(this).val().indexOf('.') != -1) && (event.which < 48 || event.which > 57)) {
                    event.preventDefault();
                }
            });

            $("#videoAdvertisement").validate({
                ignore: ":hidden:not(select)",
                rules: {
                    title: {
                        required: true
                    },
                    descr :{
                       required: true 
                    },
                    path :{ 
                        extension: 'mp4'
                    },
                    position:{
                        required:true
                    },
                    status: {
                        required: true
                    },  
                }
            });
        });
    </script>
@endsection
@extends('layouts.admin-master')

@section('title')
    {{trans('title.ADD_IMAGE_ADVERTISEMENT')}}
@endsection

@section('content')

    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                @if($imageAd->id > 0)
                    {{trans('title.EDIT_IMAGE_ADVERTISEMENT')}}
                @else
                    {{trans('title.ADD_IMAGE_ADVERTISEMENT')}}
                @endif

                <form class="form-groups" role="form" id="videoCategory" method="post" action="{{ url('admin/img-advertisement/set') }}" enctype="multipart/form-data">
                    @csrf
                    <?php $id = ($imageAd->id > 0 && isset($imageAd)) ? $imageAd['id'] : '0'; ?>
                    <input type="hidden" name="id" value="{{\App\Services\UrlService::base64UrlEncode($id)}}">

                    <!-- Video Category Title -->
                    <div class="form-group row">
                        <label class="col-md-2 control-label" for="title">{{trans('admin-labels.VIDEO_CATEGORY_TITLE')}}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control {{ $errors->has('title') ? 'is-invalid' : '' }}" id="title" placeholder="{{trans('place-holder.VIDEO_CATEGORY_TITLE')}}" name="title" value="{{ old('title') ? old('title') : $imageAd['title'] }}">
                            @if($errors->has('title'))
                                <div class="invalid-feedback">
                                    {{$errors->first('title')}}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Photo -->
                    <div class="form-group row">
                        <label class="col-md-2 control-label" for="image">{{trans('admin-labels.VIDEO_CATEGORY_IMAGE')}}</label>
                        <div class="col-md-6">
                            <input type="file" class="form-control {{ $errors->has('image') ? 'is-invalid' : '' }}" id="image" name="image">
                            @if($errors->has('image'))
                                <div class="invalid-feedback">
                                    {{$errors->first('image')}}
                                </div>
                            @endif
                            <br>
                            @if(isset($videoCategory->image) && !empty($videoCategory->image))
                                <img src="{{Config::get('constant.AWS_URL') . Config::get('constant.VIDEO_CATEGORY_THUMB_PHOTO_UPLOAD_PATH') . $imageAd->image}}" height="50px">
                            @endif
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="form-group row">
                        <label class="col-md-2 control-label" for="status" class="col-md-2 control-label"> {{ trans('admin-labels.VIDEO_CATEGORY_STATUS') }} </label>
                        <div class="col-md-6">
                            <select class="form-control" id="status" name="status">
                                @foreach ($status as $_status)
                                    <option value="{{$_status['value']}}" @if($_status['value'] == ( old('status') ? old('status') : $imageAd->status )) selected="selected" @endif >{{$_status['name']}}</option>
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
                    <a href="{{ url('admin/img-advertisement') }}" class="btn btn-light">{{ trans('admin-labels.CANCEL') }}</a>
                </form>
            </div>
        </div>
    </div>


@endsection

@section('script')
    <script type="text/javascript">
        $(document).ready(function() {
            var ID = '<?php echo $id; ?>';
            var imageRequired = (ID == '0' ? true : false);

             $("#videoCategory").validate({
                ignore: ":hidden:not(select)",
                rules: {
                    title: {
                        required: true
                    },
                    descr :{
                       required: true 
                    },
                    image :{ 
                        required:imageRequired,
                        extension: "png|jpeg|jpg|bmp",
                        filesize: 2,
                    },
                    status: {
                        required: true
                    },  
                }
            });
        });
    </script>
@endsection

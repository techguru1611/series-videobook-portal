@extends('layouts.admin-master')

@section('title')
    {{trans('title.ADD_VIDEO_BOOK')}}
@endsection

@section('style')
<link rel="stylesheet" href="{{asset('admin/plugins/dropzone/dropzone.min.css')}}">
    <style type="text/css">
        /* common */
        .ribbon {
            width: 75px;
            height: 75px;
            overflow: hidden;
            position: absolute;
        }
        .ribbon::before,
        .ribbon::after {
            position: absolute;
            z-index: -1;
            content: '';
            display: block;
            border: 5px solid #2980b9;
        }
        .ribbon span {
            position: absolute;
            display: block;
            width: 101px;
            padding: 5px 0;
            background-color: #3498db;
            box-shadow: 0 5px 10px rgba(0,0,0,.1);
            color: #fff;
            font: 700 18px/1 'Lato', sans-serif;
            text-shadow: 0 1px 1px rgba(0,0,0,.2);
            text-align: center;
            z-index: 1;
        }

        /* top left*/
        .ribbon-top-left {
            top: 0px;
            left: 0px;
        }
        .ribbon-top-left::before,
        .ribbon-top-left::after {
            border-top-color: transparent;
            border-left-color: transparent;
        }
        .ribbon-top-left::before {
            top: 0;
            right: 0;
        }
        .ribbon-top-left::after {
            bottom: 0;
            left: 0;
        }
        .ribbon-top-left span {
            right: 0px;
            top: 10px;
            transform: rotate(-45deg);
        }
    </style>
@endsection

@section('content')

    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                @if($videoBook->id > 0)
                    {{trans('title.EDIT_VIDEO_BOOK')}}
                @else
                    {{trans('title.ADD_VIDEO_BOOK')}}
                @endif
                <form class="form-groups" role="form" id="videoBook" enctype="multipart/form-data" method="post" action="{{ url('admin/video-books/set') }}">
                    @csrf
                    <?php $id = ($videoBook->id > 0 && isset($videoBook)) ? $videoBook['id'] : '0'; ?>
                    <input type="hidden" name="id" value="{{\App\Services\UrlService::base64UrlEncode($id)}}">

                    <!-- Video Category -->
                    <div class="form-group row">
                        <label class="col-md-2 control-label" for="video_category_id" class="col-md-2 control-label"> {{ trans('admin-labels.VIDEO_BOOK_CATEGORY_TITLE') }} </label>
                        <div class="col-md-6">
                            <select class="form-control" id="video_category_id" name="video_category_id">
                                <option value="0">{{ trans('admin-labels.SELECT_VIDEO_CATEGORY') }}</option>
                                @foreach ($videoCategory as $_videoCategory)
                                    <option value="{{$_videoCategory['id']}}" @if($_videoCategory['id'] == ( old('video_category_id') ? old('video_category_id') : $videoBook->video_category_id )) selected="selected" @endif >{{$_videoCategory->title}}</option>
                                @endforeach
                            </select>
                            @if($errors->has('video_category_id'))
                                <div class="invalid-feedback">
                                    {{$errors->first('video_category_id')}}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Video Book Title -->
                    <div class="form-group row">
                        <label class="col-md-2 control-label" for="title">{{trans('admin-labels.VIDEO_BOOK_TITLE')}}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control {{ $errors->has('title') ? 'is-invalid' : '' }}" id="title" placeholder="{{trans('place-holder.VIDEO_BOOK_TITLE')}}" name="title" value="{{ old('title') ? old('title') : $videoBook['title'] }}">
                            @if($errors->has('title'))
                                <div class="invalid-feedback">
                                    {{$errors->first('title')}}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Video Book Description -->
                    <div class="form-group row">
                        <label class="col-md-2 control-label" for="descr">{{trans('admin-labels.VIDEO_BOOK_DESCRIPTION')}}</label>
                        <div class="col-md-6">
                            <textarea class="form-control {{ $errors->has('descr') ? 'is-invalid' : '' }}" id="descr" placeholder="{{trans('place-holder.VIDEO_BOOK_DESCRIPTION')}}" name="descr">{{ old('descr') ? old('descr') : $videoBook['descr'] }}</textarea>
                            @if($errors->has('descr'))
                                <div class="invalid-feedback">
                                    {{$errors->first('descr')}}
                                </div>
                            @endif
                        </div>
                    </div>

                @if($videoBook->id > 0)
                    <!-- Video Book Intro Video -->
                    <div class="form-group row">
                        <label class="col-md-2 control-label" for="introVideo">{{trans('admin-labels.VIDEO_BOOK_INTRO_VIDEO')}}</label>
                        <div class="col-md-6">
                            <input type="file" class="form-control" id="introVideo" name="introVideo">
                                @php
                                    if(isset($videoBook['videos'][0])) { 
                                        if(Storage::disk(env('FILESYSTEM_DRIVER'))->exists($introVideoPath . $videoBook['videos'][0]['path']) && $videoBook['videos'][0]['path'] != '') { @endphp
                                            <a href="{{ Storage::disk(env('FILESYSTEM_DRIVER'))->url($introVideoPath . $videoBook['videos'][0]['path']) }}" target="_blank">{{ trans('admin-labels.VIDEO_BOOK_INTRO_VIDEO') }}</a>
                                        @php }
                                    }
                                @endphp
                        </div>
                    </div>
                @endif

                    <!-- Video Book Price -->
                    <div class="form-group row">
                        <label class="col-md-2 control-label" for="price">{{trans('admin-labels.VIDEO_BOOK_PRICE')}}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control allownumericwithdecimal {{ $errors->has('price') ? 'is-invalid' : '' }}" id="price" placeholder="{{trans('place-holder.VIDEO_BOOK_PRICE')}}" name="price" value="{{ old('price') ? old('price') : $videoBook['price'] }}">
                            @if($errors->has('price'))
                                <div class="invalid-feedback">
                                    {{$errors->first('price')}}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- image for video series -->
                    <div class="form-group row">
                        <label class="col-md-2 control-label" for="image">{{trans('admin-labels.VIDEO_SERIES_IMAGE')}}</label>
                        <div class="col-md-6">
                            <input type="file" class="form-control {{ $errors->has('image') ? 'is-invalid' : '' }}" id="image" name="image">
                            @if($errors->has('image'))
                                <div class="invalid-feedback">
                                    {{$errors->first('image')}}
                                </div>
                            @endif
                            <br>
                            {{--@if(Storage::disk(env('FILESYSTEM_DRIVER'))->exists(Config::get('constant.SERIES_THUMB_PHOTO_UPLOAD_PATH') . $videoBook['image']) && $videoBook['image'] != '')
                                <img src="{{Storage::disk(env('FILESYSTEM_DRIVER'))->url(Config::get('constant.SERIES_THUMB_PHOTO_UPLOAD_PATH') . $videoBook['image'])}}" height="50px">
                            @endif--}}
                        </div>
                    </div>

                    <!-- Video Book Author Profit in Percentage -->
                    <div class="form-group row">
                        <label class="col-md-2 control-label" for="author_profit_in_percentage">{{trans('admin-labels.VIDEO_BOOK_AUTHOR_PROFIT')}}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control allownumericwithdecimal {{ $errors->has('author_profit_in_percentage') ? 'is-invalid' : '' }}" id="author_profit_in_percentage" placeholder="{{trans('place-holder.VIDEO_BOOK_AUTHOR_PROFIT')}}" name="author_profit_in_percentage" value="{{ old('author_profit_in_percentage') ? old('author_profit_in_percentage') : $videoBook['author_profit_in_percentage'] }}">
                            @if($errors->has('author_profit_in_percentage'))
                                <div class="invalid-feedback">
                                    {{$errors->first('author_profit_in_percentage')}}
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
                                    <option value="{{$_status['value']}}" @if($_status['value'] == ( old('status') ? old('status') : $videoBook->status )) selected="selected" @endif >{{$_status['name']}}</option>
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
                    <a href="{{ url('admin/video-books') }}" class="btn btn-light">{{ trans('admin-labels.CANCEL') }}</a>
                </form>

                @if($videoBook->id > 0)
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="button-bar">
                                <button id="submit-all" class="btn btn-success mr-2" style="display:none;"><i class="fa fa-upload" aria-hidden="true"></i> Start Upload</button>
                                <button id="cancel-all" class="btn btn-light" style="display:none;"><i class="fa fa-ban" aria-hidden="true"></i> Cancel Upload</button>
                            </div>
                        </div>
                    </div>                
                    <div id="dropzone">
                        <form class="dropzone needsclick dz-clickable" enctype="multipart/form-data" id="video-book-videos">
                            <div class="dz-message needsclick">
                                Drop files here or click to upload.
                            </div>
                        </form>
                    </div>
                    <!-- video list section start here-->
                    <div id="video-list"></div>
                    <!-- video list section end here-->
                @endif
            </div>
        </div>
    </div>

@endsection

@section('script')
    <script src="{{ asset('admin/js/sweetalert.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/dropzone/dropzone.min.js')}}"></script>
    <script type="text/javascript">
        //Dropzone Configuration
        Dropzone.autoDiscover = false;

        var videosInfo = [];

        $(document).ready(function() {

            refreshVideoList($("input[name=id]").val()); // Refresh video list

            // Allow numeric with decimal only
            $(".allownumericwithdecimal").on("keypress keyup blur",function (event) {
                $(this).val($(this).val().replace(/[^0-9\.]/g,''));
                if ((event.which != 46 || $(this).val().indexOf('.') != -1) && (event.which < 48 || event.which > 57)) {
                    event.preventDefault();
                }
            });

            var ID = '<?php echo $id; ?>';
            var imageRequired = (ID == '0' ? true : false);

            $("#videoBook").validate({
                ignore: ":hidden:not(select)",
                rules: {
                    video_category_id: {
                        required: true
                    },
                    descr :{
                       required: true 
                    },
                    title :{
                       required: true 
                    },
                    price :{
                       required: true,
                       number: true
                    },
                    image :{
                        required : imageRequired, 
                        extension: "png|jpeg|jpg|bmp",
                        filesize: 2,
                    },
                    author_profit_in_percentage :{
                       required: true ,
                    },
                    status: {
                        required: true
                    },  
                }
            });

            $('#video-book-videos').dropzone({
                paramName: "videos", // The name that will be used to transfer the file
                maxFilesize: 2048, // MB
                headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                url: "{{ url('admin/video-books/upload-video-ajax') }}", // ajax source
                method: "POST",
                uploadMultiple: true,
                acceptedFiles: 'video/mpeg,video/mp4,video/avi,video/x-ms-wmv,video/quicktime,video/x-msvideo,video/webm,video/ogg,video/x-flv,video/3gpp',
                parallelUploads: 7,
                autoProcessQueue: false,
                addRemoveLinks: true,
                init: function() {

                    var submitBtn = document.querySelector("#submit-all")
                    var cancelBtn = document.querySelector("#cancel-all")

                    var _this = this; // Closure

                    submitBtn.addEventListener("click", function() {
                        _this.processQueue(); // Tell Dropzone to process all queued files.
                    });

                    cancelBtn.addEventListener("click", function() {
                        _this.removeAllFiles(true); // Tell Dropzone to cancel all queued files.
                    });

                    this.on("addedfile", function(file) {
                        $('#submit-all').show(); // Show submit button here and/or inform user to click it.
                        $('#cancel-all').show();
                    });

                    this.on("removedfile", function(file) {
                        if (this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0) {
                            $('#submit-all').hide(); // Hide submit button here.
                            $('#cancel-all').hide();
                        }
                    });

                    this.on("sending", function (file, xhr, formData) {
                        formData.append('videoBookId', $("input[name=id]").val());
                        formData.append('type', '{{\Config::get('constant.SERIES_VIDEO')}}');
                    });

                    this.on("queuecomplete", function (file) {
                        refreshVideoList($("input[name=id]").val()); // Refresh video list
                        $('#submit-all').hide();
                        $('#cancel-all').hide();
                    });

                    this.on("complete", function (file) {
                        this.removeFile(file);
                        saveVideosToVideoBook(videosInfo);
                        videosInfo = [];
                    });
                },
                success: function(file, response) {
                    if (response.status == 0) {
                        //console.log('Error after load', response.message);
                        swal ( "Oops" ,  response.message ,  "error" );
                        return;
                    }
                    videosInfo.push(response);
                }
            });
        });

        // Save videos after upload
        var saveVideosToVideoBook = function(videosInfo) {

            $.ajax({
                "type": "POST",
                "url": "{{ url('admin/video-books/set-video-ajax') }}",
                "dataType": "json",
                "headers": {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                "data": {'videosInfo': videosInfo},
                success: function(data){
                }
            });
        };

        // Get video list for given video series
        var refreshVideoList = function(videoSeriesId) {
            $.ajax({
                "type": "POST",
                "url": "{{ url('admin/video-books/get-video-ajax') }}",
                "dataType": "json",
                "headers": {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                "data": {'videoSeriesId': videoSeriesId},
                success: function(data){
                    if(data.status == 1) {
                        $('#video-list').html(data.html);
                    }
                }
            });
        };

        // Save video detail
        function saveVideoDetail(videoId) {

            $.ajax({
                "type": "POST",
                "url": "{{ url('admin/video-books/save-video-ajax') }}",
                "dataType": "json",
                "headers": {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                "data": {
                    "video_name": $("input[name=video_name_" + videoId + "]").val(),
                    "video_descr": $("textarea:input[name=video_descr_" + videoId + "]").val(),
                    "video_id": videoId,
                },
                success: function(data){
                    console.log("Video detail updated.");
                }
            });
        };

        // Delete video
        function approveVideo(videoId) {

            $.ajax({
                "type": "POST",
                "url": "{{ url('admin/video-books/approve-video-ajax') }}",
                "dataType": "json",
                "headers": {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                "data": {
                    "video_id": videoId,
                },
                success: function(data){
                    console.log("Video Approved successfully.");
                    refreshVideoList($("input[name=id]").val()); // Refresh video list
                }
            });
        };

        function rejectVideo(videoId) {

            $.ajax({
                "type": "POST",
                "url": "{{ url('admin/video-books/reject-video-ajax') }}",
                "dataType": "json",
                "headers": {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                "data": {
                    "video_id": videoId,
                },
                success: function(data){
                    console.log("Video Approved successfully.");
                    refreshVideoList($("input[name=id]").val()); // Refresh video list
                }
            });
        };

        // Delete video
        function deleteVideo(videoId) {

            $.ajax({
                "type": "POST",
                "url": "{{ url('admin/video-books/remove-video-ajax') }}",
                "dataType": "json",
                "headers": {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                "data": {
                    "video_id": videoId,
                },
                success: function(data){
                    console.log("Video deleted successfully.");
                    refreshVideoList($("input[name=id]").val()); // Refresh video list
                }
            });
        };
</script>
@endsection

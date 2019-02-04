<section id="video-list" class="video-list">
@foreach ($videos as $video)
    <div class="video-block">
        <div class="row">
            <div class="col-xl-3 col-md-4">
                <div class="video-thumbnail">
                    @if($video->type == Config::get('constant.INTRO_VIDEO'))
                        <div class="ribbon ribbon-top-left"><span>Intro</span></div>
                    @endif
                    <video controls>
                        <!-- Check video type -->
                        @if($video->type == Config::get('constant.INTRO_VIDEO'))

                        @else

                        @endif

                        <!-- Checked video transacoding status -->
                        @if($video->is_transacoded == Config::get('constant.TRANSCODING_DONE_VIDEO_STATUS'))
                            {{-- @if (Storage::disk('s3')->exists(Config::get('constant.SERIES_VIDEO_UPLOAD_PATH') . $video->path))
                                <source src="{{ Storage::disk('s3')->url(Config::get('constant.SERIES_VIDEO_UPLOAD_PATH') . $video->path) }}" type="video/mp4">
                            @else --}}
                            <?php
                            $object_key = Config::get('constant.SERIES_VIDEO_UPLOAD_PATH') . $video->path;
                            $video_url = Helpers::generateAWSSignedUrl($object_key);
                            ?>
                                <source src="{{ $video_url  }}" type="video/mp4">
                            {{-- @endif --}}
                        @elseif($video->is_transacoded == Config::get('constant.TRANSCODING_FAILED_VIDEO_STATUS'))
                            {{-- @if (Storage::disk('s3')->exists(Config::get('constant.SERIES_VIDEO_UPLOAD_PATH') . $video->path))
                                <source src="{{ Storage::disk('s3')->url(Config::get('constant.SERIES_VIDEO_UPLOAD_PATH') . $video->path) }}" type="video/mp4">
                            @else --}}
                            <?php
                            $object_key = Config::get('constant.SERIES_VIDEO_TEMP_UPLOAD_PATH') . $video->path;
                            $video_url = Helpers::generateAWSSignedUrl($object_key);
                            ?>
                            <source src="{{ $video_url  }}" type="video/mp4">
                            {{-- @endif --}}
                        @else
                            {{-- @if (Storage::disk('s3')->exists(Config::get('constant.SERIES_VIDEO_TEMP_UPLOAD_PATH') . $video->path))
                                <source src="{{ Storage::disk('s3')->url(Config::get('constant.SERIES_VIDEO_TEMP_UPLOAD_PATH') . $video->path) }}" type="video/mp4">
                            @else --}}
                                <source src="{{ Storage::disk('public')->url(Config::get('constant.SERIES_VIDEO_TEMP_UPLOAD_PATH') . $video->path) }}" type="video/mp4">
                            {{-- @endif --}}
                        @endif
                        
                    </video>
                </div>
            </div>
            <div class="col-xl-9 col-md-8">
                <div class="text-right">
                    <b>Transcode Status: </b>{{ $video->is_transacoded }}
                </div>
                <div class="video-info">
                    <form>
                        @php $videoID = \App\Services\UrlService::base64UrlEncode($video->id); @endphp
                        <div class="form-group">
                            <label>{{ trans('admin-labels.VIDEO_TITLE') }}</label>
                            <input name="video_name_{{$videoID}}" type="text" placeholder="{{ trans('place-holder.VIDEO_TITLE') }}" class="form-control" value="{{ $video->title }}">
                        </div>
                        <div class="form-group last-child">
                            <label>{{ trans('admin-labels.VIDEO_DESCRIPTION') }}</label>
                            <textarea name="video_descr_{{$videoID}}" placeholder="{{ trans('place-holder.VIDEO_TITLE') }}" class="form-control" rows="4">{{ $video->descr }}</textarea>
                        </div>
                        <div class="btn-grp">
                            <a href="javascript:void(0)" class="btn btn-save" onclick="saveVideoDetail('{{$videoID}}')"><i class="fa fa-floppy-o" aria-hidden="true"></i> {{ trans('admin-labels.SAVE') }}</a>
                            <a href="javascript:void(0)" class="btn btn-delete" onclick="deleteVideo('{{$videoID}}')"><i class="fa fa-trash" aria-hidden="true"></i> {{ trans('admin-labels.DELETE') }}</a>
                            @if($video->is_approved == 0)
                                <a href="javascript:void(0)" class="btn btn-action" onclick="approveVideo('{{$videoID}}')"><i class="fa fa-check" aria-hidden="true"></i> {{ trans('admin-labels.APPROVE') }}</a>
                            @else
                                <a href="javascript:void(0)" class="btn btn-action" onclick="rejectVideo('{{$videoID}}')"><i class="fa fa-ban" aria-hidden="true"></i> {{ trans('admin-labels.REJECT') }}</a>
                            @endif

                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endforeach
</section>

<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Storage;
use Config;

class VideoSeriesVideoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => ($this->id === null || $this->id == '') ? '' : $this->id,
            'name' => $this->title,
            'desc'=>($this->descr === null || $this->descr == '') ? '' : $this->descr,
            'duration' =>($this->lengh === null || $this->lengh == '') ? 0 : $this->lengh,
            'watchedTill' =>(!$this->videoWatchedByUser || empty($this->videoWatchedByUser->toArray()) || $this->videoWatchedByUser->count() == 0 || $this->videoWatchedByUser[0]->pivot->watched_till == null || $this->videoWatchedByUser[0]->pivot->watched_till == '') ? 0 :  $this->videoWatchedByUser[0]->pivot->watched_till,
            'isCompleted' =>(!$this->videoWatchedByUser || empty($this->videoWatchedByUser->toArray()) || $this->videoWatchedByUser->count() == 0 || $this->videoWatchedByUser[0]->pivot->is_completed == null || $this->videoWatchedByUser[0]->pivot->is_completed == '') ? boolval(0) :  boolval($this->videoWatchedByUser[0]->pivot->is_completed),
            $this->mergeWhen(isset($this->videoSignedUrl) && !empty($this->videoSignedUrl),[
                'videoUrl' => $this->videoSignedUrl,
            ]),
            'image'=>($this->thumb_path === null || $this->thumb_path == '') ? '' : (Storage::disk('public')->exists(Config::get('constant.SERIES_VIDEO_THUMB_UPLOAD_PATH') . $this->thumb_path) ? Storage::disk('public')->url(Config::get('constant.SERIES_VIDEO_THUMB_UPLOAD_PATH') . $this->thumb_path) : (Storage::disk('s3')->exists(Config::get('constant.SERIES_VIDEO_THUMB_UPLOAD_PATH') . $this->thumb_path) ? Storage::disk('s3')->url(Config::get('constant.SERIES_VIDEO_THUMB_UPLOAD_PATH') . $this->thumb_path) : '')),
            'commentsCount' => $this->comments->count(),
        ];
    }
}

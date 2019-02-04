<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class VideoSeriresAuthorVideoResource extends JsonResource
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
            'duration' =>($this->lengh === null || $this->lengh == '') ? 0 : intval($this->lengh),
            'size' => ($this->size === null || $this->size == '') ? 0 : intval($this->size),
            'watchedTill' =>(!$this->videoWatchedByUser || empty($this->videoWatchedByUser->toArray()) || $this->videoWatchedByUser->count() == 0 || $this->videoWatchedByUser[0]->pivot->watched_till == null || $this->videoWatchedByUser[0]->pivot->watched_till == '') ? 0 :  $this->videoWatchedByUser[0]->pivot->watched_till,
            'isCompleted' =>(!$this->videoWatchedByUser || empty($this->videoWatchedByUser->toArray()) || $this->videoWatchedByUser->count() == 0 || $this->videoWatchedByUser[0]->pivot->is_completed == null || $this->videoWatchedByUser[0]->pivot->is_completed == '') ? boolval(0) :  boolval($this->videoWatchedByUser[0]->pivot->is_completed),
            'image'=>($this->thumb_path === null || $this->thumb_path == '') ? '' : (Storage::disk('public')->exists(Config::get('constant.SERIES_VIDEO_THUMB_UPLOAD_PATH') . $this->thumb_path) ? Storage::disk('public')->url(Config::get('constant.SERIES_VIDEO_THUMB_UPLOAD_PATH') . $this->thumb_path) : (Storage::disk('s3')->exists(Config::get('constant.SERIES_VIDEO_THUMB_UPLOAD_PATH') . $this->thumb_path) ? Storage::disk('s3')->url(Config::get('constant.SERIES_VIDEO_THUMB_UPLOAD_PATH') . $this->thumb_path) : '')),
            'chunkPointer' => ($this->chunkPointer === null || $this->chunkPointer == '') ? 0 : intval($this->chunkPointer),
            'uploadPercent' => ($this->progressPercent === null || $this->progressPercent == '') ? 0 : $this->progressPercent,
            'uploadComplete' => boolval($this->completed_at),
            'uploadPause' => boolval($this->resumed_at),
            $this->mergeWhen(isset($this->videoSignedUrl) && !empty($this->videoSignedUrl),[
                'videoUrl' => $this->videoSignedUrl,
            ]),
            'commentsCount' => $this->comments->count(),
            'isApproved' => boolval($this->is_approved),
        ];
    }
}

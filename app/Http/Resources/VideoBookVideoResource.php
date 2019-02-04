<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Storage;
use Config;

class VideoBookVideoResource extends JsonResource
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
            'authorId'=> (!$this->videobook || !$this->videobook->author || empty($this->videobook->author) || $this->videobook->author->id === null || $this->videobook->author->id == '') ? '' : $this->videobook->author->id ,
            'authorName' =>(!$this->videobook || !$this->videobook->author || empty($this->videobook->author) || $this->videobook->author->full_name === null || $this->videobook->author->full_name  == '') ? '' : $this->videobook->author->full_name,
            'name' =>($this->title === null || $this->title == '') ? '' :$this->title,
            'desc'=>($this->descr === null || $this->descr == '') ? '' : $this->descr,
            'videoUrl' => $this->videoSignedUrl,
            'image'=>($this->thumb_path === null || $this->thumb_path == '') ? '' : (Storage::disk('public')->exists(Config::get('constant.SERIES_VIDEO_THUMB_UPLOAD_PATH') . $this->thumb_path) ? Storage::disk('public')->url(Config::get('constant.SERIES_VIDEO_THUMB_UPLOAD_PATH') . $this->thumb_path) : (Storage::disk('s3')->exists(Config::get('constant.SERIES_VIDEO_THUMB_UPLOAD_PATH') . $this->thumb_path) ? Storage::disk('s3')->url(Config::get('constant.SERIES_VIDEO_THUMB_UPLOAD_PATH') . $this->thumb_path) : '')),
            'duration' =>($this->lengh === null || $this->lengh == '') ? 0 : $this->lengh,
            'likesCount'=> (!$this->videoLikesByUser || $this->videoLikesByUser->count() == 0) ? 0 : $this->videoLikesByUser->count(),
            'dislikeCount' => (!$this->videoUnlikesByUser || $this->videoUnlikesByUser->count() == 0) ? 0 : $this->videoUnlikesByUser->count(),
            'downloadsCount' => intval($this->total_download),
            'likeStatus' => (!$this->likes || empty($this->likes) || $this->likes->count() == 0) ? Config::get('constant.NONE') : $this->likes[0]->action,
            'watchedTill' =>(!$this->videoWatchedByUser || empty($this->videoWatchedByUser->toArray()) || $this->videoWatchedByUser->count() == 0 || $this->videoWatchedByUser[0]->pivot->watched_till == null || $this->videoWatchedByUser[0]->pivot->watched_till == '') ? 0 :  $this->videoWatchedByUser[0]->pivot->watched_till,
            'isCompleted' =>(!$this->videoWatchedByUser || empty($this->videoWatchedByUser->toArray()) || $this->videoWatchedByUser->count() == 0 || $this->videoWatchedByUser[0]->pivot->is_completed == null || $this->videoWatchedByUser[0]->pivot->is_completed == '') ? boolval(0) :  boolval($this->videoWatchedByUser[0]->pivot->is_completed),
        ];
    }
}

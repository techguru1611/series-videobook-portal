<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Config;
use Storage;

class VideoAdResource extends JsonResource
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
            'videoUrl' =>(Storage::disk('public')->exists(Config::get('constant.VIDEO_ADVERTISEMENT_ORIGINAL_UPLOAD_PATH') .$this->path) ? Storage::disk('public')->url(Config::get('constant.VIDEO_ADVERTISEMENT_ORIGINAL_UPLOAD_PATH') . $this->path) : Storage::disk('s3')->url(Config::get('constant.VIDEO_ADVERTISEMENT_ORIGINAL_UPLOAD_PATH') . $this->path)),
            'isSkipable' => boolval($this->is_skipable),
            'skipAfter' => $this->skipale_after,
            'startTime' => 0 ,
        ];
    }
}

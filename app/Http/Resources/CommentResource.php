<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;
use Storage;
use Config;

class CommentResource extends JsonResource
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
            'id' => $this->id,
            'userId'=> (!$this->videoCommentedUser || empty($this->videoCommentedUser) || $this->videoCommentedUser->id === null || $this->videoCommentedUser->id == '') ? '' : $this->videoCommentedUser->id,            
            'userProfile' => (!$this->videoCommentedUser || empty($this->videoCommentedUser) || $this->videoCommentedUser->photo === null || $this->videoCommentedUser->photo == '') ? '' : (Storage::disk('public')->exists(Config::get('constant.USER_ORIGINAL_PHOTO_UPLOAD_PATH') . $this->videoCommentedUser->photo) ? Storage::disk('public')->url(Config::get('constant.USER_ORIGINAL_PHOTO_UPLOAD_PATH') . $this->videoCommentedUser->photo) : (Storage::disk('s3')->exists(Config::get('constant.USER_ORIGINAL_PHOTO_UPLOAD_PATH') . $this->videoCommentedUser->photo) ? Storage::disk('s3')->url(Config::get('constant.USER_ORIGINAL_PHOTO_UPLOAD_PATH') . $this->videoCommentedUser->photo) : '')),
            'comment' => $this->comment,
            'repliedCount' => $this->where('type',Config::get('constant.REPLIED'))->where('parent_id', $this->id)->count(),
            'commentBy' => (!$this->videoCommentedUser || empty($this->videoCommentedUser) || $this->videoCommentedUser->full_name === null || $this->videoCommentedUser->full_name == '') ? '' :$this->videoCommentedUser->full_name,
            'createdAt' => Carbon::parse($this->created_at)->timestamp * 1000,
        ];
    }
}

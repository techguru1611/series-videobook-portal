<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\UserHistory;
use DB;

class SaveUserHistory implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $seriesId; 
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($userId, $seriesId)
    {
        $this->userId = $userId;
        $this->seriesId = $seriesId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Start Transaction  
        DB::beginTransaction();

        $userExists = UserHistory::where('user_id',$this->userId)->where('e_video_book_id',$this->seriesId);

        if($userExists->exists())
        {
            $userExists->delete();
        }
        
        if(UserHistory::where('user_id', $this->userId)->count() >= 50)
        {
            UserHistory::where('user_id', $this->userId)->orderBy('id','ASC')->limit(1)->delete();
        }
        $userhistory = new UserHistory();
        $userhistory->user_id =  $this->userId;
        $userhistory->e_video_book_id =  $this->seriesId;

        $userhistory->save();

        // Commit DB 
        DB::commit();
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Setting;
use App\Models\VideoBook;
use Config;

class SaveSeries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'SomethingNew';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command fetch Series id and store into setting table.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Setting $setting)
    {
        parent::__construct();
        $this->setting = $setting;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
    */
    public function handle()
    {
        $videobookId= VideoBook::inRandomOrder()->where('approved_by','>',0)->limit(Config::get('constant.SERIES_LIMIT'))->pluck('id')->toArray();
        $id = implode(",",$videobookId);  
        $this->setting->where('slug', Config::get('constant.SOMETHING_NEW_EVERYDAY'))->update(['value' => $id]);
    }
}

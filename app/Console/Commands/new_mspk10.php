<?php

namespace App\Console\Commands;

use App\Events\RunMssc;
use App\Http\Controllers\Bet\New_Mssc;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class new_mspk10 extends Command
{
    protected  $code    = 'mssc';
    protected  $gameId  = 80;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'new_mspk10';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '新（秒速赛车结算）';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $getFile    = Storage::disk('gameTime')->get('mspk10.json');
        $data       = json_decode($getFile,true);
        $nowTime    = date('H:i:s');
        $filtered = collect($data)->first(function ($value, $key) use ($nowTime) {
            if(strtotime($value['openTime']) === strtotime($nowTime)){
                return $value;
            }
        });
        if($filtered!=null){
            $params =  [
                'issue' => date('ymd').$filtered['issue'],
                'openTime' => date('Y-m-d ').$filtered['openTime']
            ];
            $res = curl(Config::get('website.openServerUrl').$this->code,$params,1);
            $res = json_decode($res);
            $issueCount = DB::table('game_mssc')->where('issue',$res->expect)->count();
            if($issueCount == 0){
                try{
                    DB::table('game_mssc')->insert([
                        'issue'=> $res->expect,
                        'is_open'=> 1,
                        'year'=> date('Y'),
                        'month'=> date('m'),
                        'day'=>  date('d'),
                        'opentime'=> $res->opentime,
                        'opennum'=> $res->opencode
                    ]);
                }catch (\Exception $exception){
                    \Log::info(__CLASS__ . '->' . __FUNCTION__ . ' Line:' . $exception->getLine() . ' ' . $exception->getMessage());
                }
            }
        }
//        $openCode = "10,2,8,1,7,4,6,3,5,9";
//        $openIssue = '180404975';
//        $gameId = 80;
//
////        $job = new New_Mssc();
////        $job->all($openCode,$openIssue,$gameId);
//        event(new RunMssc($openCode,$openIssue,$gameId));
    }
}

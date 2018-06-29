<?php

namespace App\Console\Commands;

use App\Events\RunCqssc;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class new_cqssc extends Command
{
    protected  $code = 'cqssc';
    protected  $gameId = 1;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'new_cqssc';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '新-重庆时时彩';

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
        $getFile    = Storage::disk('gameTime')->get('cqssc.json');
        $data       = json_decode($getFile,true);
        $nowTime    = date('H:i');
        $url = 'http://103.9.195.86:8881/api/guan/cqssc';
        $html = json_decode(file_get_contents($url),true);

        $checkIssueOpen = DB::table('game_cqssc')->where('issue',$html[0]['issue'])->where('is_open',0)->count();
        if($checkIssueOpen > 0){
            $update = DB::table('game_cqssc')->where('issue',$html[0]['issue'])
                ->update([
                    'is_open' => 1,
                    'opennum' => $html[0]['nums']
                ]);
            if($update == 1){
                \Log::info($html[0]['issue']."已更新到数据库！");
//                $jie = event(new RunCqssc($html[0]['nums'],$html[0]['issue'],$this->gameId)); //新--结算
//                if($jie == 1){
//                    \Log::info($html[0]['issue']."已结算完毕！");
//                }
            }
        }

        $filtered = collect($data)->first(function ($value, $key) use ($nowTime) {
            if(strtotime(date('H:i',strtotime($value['openTime']))) == strtotime($nowTime)){
                return $value;
            }
        });
        if($filtered !== null){
            $openIssue = date('Ymd').$filtered['issue'];
           // \Log::info($openIssue);
            $checkdata = DB::table('game_cqssc')->where('issue',$openIssue)->count();
            if($checkdata == 0){
                DB::table('game_cqssc')->insert(
                    ['issue'=>$openIssue, 'is_open'=>0, 'year'=>date('Y'), 'month'=>date('m'), 'day'=>date('d'), 'opentime'=>date('Y-m-d').' '.$filtered['openTime']]
                );
            }
        }

//        $url = 'http://e.apiplus.net/a8e90a303ec6961f/cqssc-1.json';
//        $filtered = collect($data)->first(function ($value, $key) use ($nowTime) {
//            if(strtotime(date('H:i',strtotime($value['openTime']))) == strtotime($nowTime)){
//                return $value;
//            }
//        });
//        if($filtered !== null){
//            $html = json_decode(file_get_contents($url),true);
//            if(isset($html)){
//                $issue =  $html['data'][0]['expect'];
//                $openCode =  $html['data'][0]['opencode'];
//                $openTime =  $html['data'][0]['opentime'];
//                $issueCount = DB::table('game_cqssc')->where('issue',$issue)->count();
//                if($issueCount == 0){
//                    try{
//                        $save = DB::table('game_cqssc')->insert([
//                            'issue'=> $issue,
//                            'is_open'=> 1,
//                            'year'=> date('Y'),
//                            'month'=> date('m'),
//                            'day'=>  date('d'),
//                            'apiopentime'=> $openTime,
//                            'opentime'  => date('Y-m-d H:i:s',strtotime($nowTime)),
//                            'opennum'   => $openCode
//                        ]);
//                        if($save == 1){
//                            event(new RunCqssc($openCode,$issue,$this->gameId)); //新--结算
//                        }
//                    } catch (\Exception $e){
//                        \Log::info(__CLASS__ . '->' . __FUNCTION__ . ' Line:' . $e->getLine() . ' ' . $e->getMessage());
//                    }
//                }
//            }
//        }
    }
}

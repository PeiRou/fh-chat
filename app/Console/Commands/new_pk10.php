<?php

namespace App\Console\Commands;

use App\Events\RunPk10;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class new_pk10 extends Command
{
    protected  $code = 'bjpk10';
    protected  $gameId = 50;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'new_pk10';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '新-北京赛车';

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
        $getFile    = Storage::disk('gameTime')->get('bjpk10.json');
        $data       = json_decode($getFile,true);
        $nowTime    = date('H:i:s');
        $url = 'http://103.9.195.86:8881/api/guan/bjpk10';
        $html = json_decode(file_get_contents($url),true);
        $openIssue = (int)$html[0]['issue']+1;

        $checkIssueOpen = DB::table('game_bjpk10')->where('issue',$html[0]['issue'])->where('is_open',0)->count();
        if($checkIssueOpen > 0){
            $update = DB::table('game_bjpk10')->where('issue',$html[0]['issue'])
                ->update([
                    'is_open' => 1,
                    'opennum' => $html[0]['nums']
                ]);
            if($update == 1){
                \Log::info($html[0]['issue']."已更新到数据库！");
            }
        }

        $filtered = collect($data)->first(function ($value, $key) use ($nowTime) {
            if(strtotime($value['openTime']) == strtotime($nowTime)){
                return $value;
            }
        });
        if($filtered !== null){
            //$checkLastIssue = DB::table('game_bjpk10')->where('is_open',1)->where('opentime','desc')->take(1)->first();
            //$openIssue = (int)$checkLastIssue->issue +1;
            $checkdata = DB::table('game_bjpk10')->where('issue',$openIssue)->count();
            if($checkdata == 0){
                $getLastIssue = DB::table('game_bjpk10')->select('issue')->orderBy('opentime','desc')->take(1)->first();
                $nextOpenIssue = (int)$getLastIssue->issue +1;
                DB::table('game_bjpk10')->insert(
                    [
                        'issue'=>$nextOpenIssue,
                        'is_open'=>0,
                        'year'=>date('Y'),
                        'month'=>date('m'),
                        'day'=>date('d'),
                        'opentime'=>date('Y-m-d').' '.$filtered['openTime']
                    ]
                );
                \Log::info('北京赛车 - 下期期号'.$nextOpenIssue.'已插入数据库');
            }
        }
    }
}

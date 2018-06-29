<?php
/**
 * Created by PhpStorm.
 * User: vincent
 * Date: 2018/2/20
 * Time: 下午4:20
 */

namespace App\Http\Proxy;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;

class CurGame
{
    public function HttpGet($gameId)
    {
        switch ($gameId){
            case 80; //秒速赛车
                $gameType = 'zi';
                $gameTag = 'mssc';
                break;
        }
        $http = new Client();
        if($gameType == 'zi'){
            $request = $http->get("http://112.213.105.60:8001/api/$gameTag");
            $response = json_decode((string) $request->getBody(), true);
            return response()->json([
                'gameId' => $gameId,
                'issue' => $response['expect'],
                'nums' => $response['opencode'],
                'opentime' => $response['opentime']
            ]);
        }
    }

    public function nextIssue($gameId)
    {
        switch ($gameId){
            case 80; //秒速赛车
                $gameTable = 'game_mssc';
                break;
            case 50; //北京赛车
                $gameTable = 'game_bjpk10';
                break;
            case 82; //秒速飞艇
                $gameTable = 'game_msft';
                break;
            case 81; //秒速时时彩
                $gameTable = 'game_msssc';
                break;
            case 1; //重庆时时彩
                $gameTable = 'game_cqssc';
                break;
        }

        $getLottery = DB::table($gameTable)->select()->orderBy('opentime','desc')->take(1)->first();
        if($gameTable == 'game_mssc'){
            $endTime = Carbon::parse($getLottery->opentime)->addSeconds(60)->toDateTimeString();
            $lotteryTime = Carbon::parse($getLottery->opentime)->addSeconds(75)->toDateTimeString();
        }
        if($gameTable == 'game_bjpk10'){
            if(strtotime(date('H:i:s',strtotime($getLottery->opentime))) == strtotime("23:57:30")){
                $lotteryTime = date('Y-m-d',strtotime('+1 day',strtotime($getLottery->opentime)))." 09:07:30";
                $endTime = date('Y-m-d',strtotime('+1 day',strtotime($getLottery->opentime)))." 09:07:00";
            } else {
                $endTime = Carbon::parse($getLottery->opentime)->addSeconds(270)->toDateTimeString();
                $lotteryTime = Carbon::parse($getLottery->opentime)->addSeconds(300)->toDateTimeString();
            }
        }
        if($gameTable == 'game_msft'){
            $endTime = Carbon::parse($getLottery->opentime)->addSeconds(60)->toDateTimeString();
            $lotteryTime = Carbon::parse($getLottery->opentime)->addSeconds(75)->toDateTimeString();
        }
        if($gameTable == 'game_msssc'){
            $endTime = Carbon::parse($getLottery->opentime)->addSeconds(60)->toDateTimeString();
            $lotteryTime = Carbon::parse($getLottery->opentime)->addSeconds(75)->toDateTimeString();
        }
        if($gameTable == 'game_cqssc'){
            $serverTime = explode(':',date('H:i:s'));
            $hour = $serverTime[0];
            if($hour >= 22 || $hour <= 2){
                if(strtotime(date('H:i:s',strtotime($getLottery->opentime))) == strtotime("01:55:00")){
                    $lotteryTime = date('Y-m-d',strtotime($getLottery->opentime))." 10:00:00";
                    $endTime = date('Y-m-d',strtotime($getLottery->opentime))." 09:59:15";
                } else {
                    $lotteryTime = Carbon::parse($getLottery->opentime)->addSeconds('300')->toDateTimeString();
                    $endTime = Carbon::parse($getLottery->opentime)->addSeconds('255')->toDateTimeString();
                }
            }
            if($hour >= 10 && $hour < 22){
                $lotteryTime = Carbon::parse($getLottery->opentime)->addSeconds('600')->toDateTimeString();
                $endTime = Carbon::parse($getLottery->opentime)->addSeconds('555')->toDateTimeString();
            }
            if($hour > 2 && $hour < 10){
                $lotteryTime = date('Y-m-d',strtotime($getLottery->opentime))." 10:00:00";
                $endTime = date('Y-m-d',strtotime($getLottery->opentime))." 09:59:15";
            }
        }
        //dd($getLottery);
        $issue = $getLottery->issue+1;
        if($gameTable == 'game_cqssc'){
            $getCQSSC = DB::table('game_cqssc')->where('is_open',1)->orderBy('opentime','desc')->take(1)->first();
            $getAllCqssc= DB::table('game_cqssc')->orderBy('opentime','desc')->take(1)->first();
            $nextIssue = (int)$getAllCqssc->issue + 1;
            return response()->json([
                "gameId"=>(int)$gameId,
                "from_type"=>"0",
                "serverTime"=>date('Y-m-d H:i:s'),
                "issue"=>(string)$nextIssue,
                "endTime"=>$endTime,
                "nums"=>null,
                "lotteryTime"=>$lotteryTime,
                "preIssue"=>$getCQSSC->issue,
                "preLotteryTime"=>$getCQSSC->opentime,
                "preNum"=>$getCQSSC->opennum,
                "preIsOpen"=>true
            ]);
        } else if ($gameTable == 'game_bjpk10') {
            $getBJPK10 = DB::table('game_bjpk10')->where('is_open',1)->orderBy('opentime','desc')->take(1)->first();
            $getAllBjpk10= DB::table('game_bjpk10')->orderBy('opentime','desc')->take(1)->first();
            $nextIssue = (int)$getAllBjpk10->issue + 1;
            return response()->json([
                "gameId"=>(int)$gameId,
                "from_type"=>"0",
                "serverTime"=>date('Y-m-d H:i:s'),
                "issue"=>(string)$nextIssue,
                "endTime"=>$endTime,
                "nums"=>null,
                "lotteryTime"=>$lotteryTime,
                "preIssue"=>$getBJPK10->issue,
                "preLotteryTime"=>$getBJPK10->opentime,
                "preNum"=>$getBJPK10->opennum,
                "preIsOpen"=>true
            ]);
        } else {
            return response()->json([
                "gameId"=>(int)$gameId,
                "from_type"=>"0",
                "serverTime"=>date('Y-m-d H:i:s'),
                "issue"=>(string)$issue,
                "endTime"=>$endTime,
                "nums"=>null,
                "lotteryTime"=>$lotteryTime,
                "preIssue"=>$getLottery->issue,
                "preLotteryTime"=>$getLottery->opentime,
                "preNum"=>$getLottery->opennum,
                "preIsOpen"=>true
            ]);
        }


    }
}
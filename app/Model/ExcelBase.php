<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/3 0003
 * Time: 20:10
 */

namespace App\Model;

use SameClass\Config\LotteryGames\Games;

class ExcelBase extends Base
{

    protected $table = 'excel_base';

    public $aType = [
        1 => '定位胆',
        11 => '定位胆大小',
        2 => '和值类',
        3 => '平特码生肖',
    ];

    public $timestamps = false;

    public function indexData($aParam)
    {
        $iModel = $this
            ->select('excel_base.*', 'game.game_name')
            ->join('game', 'game.game_id', '=', 'excel_base.game_id')
            ->where('game.status',1)
            ->where('excel_base.is_user',1);
        return [
            'iCount' => $iModel->count(),
            'aData' => $iModel
                ->get(),
        ];
    }

    public function switch_ ($aParam)
    {
        $is_open = $this->getValueByParamField($aParam['id'], 'is_open', 'excel_base_idx');
        $is_open = $is_open == 0 ? 1 : 0;
        return $this->where('excel_base_idx', $aParam['id'])->update(['is_open' => $is_open]);
    }

    public function editArr ($aParam)
    {
        if(isset($aParam['id'])){
            return $this->where('excel_base_idx', $aParam['id'])->update([
                'kill_rate' => (float)abs($aParam['kill_rate']) / 100
            ]);
        }
    }

    public function getGameSelect(){
        $aData = $this->select('excel_base.excel_base_idx','excel_base.game_id','game.game_name')
            ->join('game', 'game.game_id', '=', 'excel_base.game_id')
            ->where('game.status',1)
            ->where('excel_base.is_user',1)
            ->get();
        $Games = new Games();
        $gameType = $Games->getGameData('gameIdtoType');
        foreach ($aData as $kData => $iData){
            $aData[$kData]->category = isset($gameType[$iData->game_id])?$gameType[$iData->game_id]:'';
        }
        return $aData;
    }

}
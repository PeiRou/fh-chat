<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/3 0003
 * Time: 20:10
 */

namespace App\Model;


class ExcelBase extends Base
{

    protected $table = 'excel_base';

    private $aCategory = [
        'car' => [80,82,99,801,802,804],
        'ssc' => [81,113,114,803],
        'k3' => [86],
        'lhc' => [85],
    ];

    public $aType = [
        1 => '定位档',
        2 => '和值类',
    ];

    public $timestamps = false;

    public function indexData($aParam)
    {
        $iModel = $this
            ->select('excel_base.*', 'game.game_name')
            ->join('game', 'game.game_id', '=', 'excel_base.game_id')
            ->where('game.status',1)
            ->where('excel_base.is_user',1);
//            ->where(function ($aSql) use($aParam){
//
//            });
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
        foreach ($aData as $kData => $iData){
            foreach ($this->aCategory as $kCateGory => $iCateGory){
                if(in_array($iData->game_id,$iCateGory))
                    $aData[$kData]->category = $kCateGory;
            }
        }
        return $aData;
    }

}
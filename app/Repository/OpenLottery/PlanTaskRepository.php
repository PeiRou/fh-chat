<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/3 0003
 * Time: 21:15
 */

namespace App\Repository\OpenLottery;

use App\Model\ExcelBase;
use SameClass\Config\LotteryGames\Games;
use Yajra\DataTables\DataTables;

class PlanTaskRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct('ExcelPlan');
        if(isset(request()->gameName))
            $this->gameName = request()->gameName;
    }

    public function indexSelect(){
        return [
            'game' => $this->getOtherModel('ExcelBase')->getGameSelect(),
        ];
    }

    public function index($aParam)
    {
        $ExcelBase = new ExcelBase();
        $aParam['plan_type'] = $ExcelBase->aType;
        $Games = new Games();
        $aParam['gameIdtoType'] = $Games->getGameData('gameIdtoType');
        $aParam['cnLotteryType'] = $Games->cnLotteryType;
        $aData = $this->model->indexData($aParam);
        return DataTables::of($aData['aData'])
            ->editColumn('fact_probability',function ($aData){
                $fact_probability = round($aData->Winning_count / $aData->total_count,4)*100;
                if($aData->planned_probability < $fact_probability - 1){
                    $txt = '<span class="status-3">'.$fact_probability . '%' .'</span>';
                }elseif ($aData->planned_probability > $fact_probability + 1){
                    $txt = '<span class="status-1">'.$fact_probability . '%' .'</span>';
                }else{
                    $txt = '<span class="status-2">'.$fact_probability . '%' .'</span>';
                }
                return $txt;
            })
            ->editColumn('lotteryType',function ($aData) use ($aParam) {
                if(isset($aParam['gameIdtoType'][$aData->game_id])&&isset($aParam['cnLotteryType'][$aParam['gameIdtoType'][$aData->game_id]]))
                    return $aParam['cnLotteryType'][$aParam['gameIdtoType'][$aData->game_id]];
                else
                    return '';
            })
            ->editColumn('type',function ($aData) use ($aParam) {
                if(isset($aParam['plan_type'][$aData->type]))
                    return $aParam['plan_type'][$aData->type];
                else
                    return $aData->type;
            })
            ->editColumn('num_digits', function ($aData){
                return '第'.$aData->num_digits.'位';
            })
            ->editColumn('planned_probability', function ($aData){
                return $aData->planned_probability.'%';
            })
            ->editColumn('control', function ($aData){
                if($aData->status == 1){
                    $is_satus = '不跟投';
                }elseif($aData->status == 0){
                    $is_satus = '跟投';
                }

                return '<ul class="control-menu">
                            <li onclick="edit(\'修改\',\'/chat/planTask/edit/'.$aData->id.'\')">修改</li>
                            <li onclick="del(\'删除\',\'/chat/planTask/del/'.$aData->id.'\')">删除</li>
                            <li onclick="setStatus('.$aData->id.','.$aData->status.')">'.$is_satus.'</li>
                        </ul>';
//                return $this->lineButtonSplice($aData);
            })
            ->rawColumns(['control','fact_probability'])
            ->setTotalRecords($aData['iCount'])
            ->skipPaging()
            ->make(true);
    }

    public function add($aParam){
        if($this->model->add($aParam))
            return $this->ajaxReturn('添加成功',true);
        return $this->ajaxReturn('添加失败');
    }
    //跟投
    public function setStatus($aParam){

        if($aParam['status'] == 0){
            $data['status'] = 1;
        }elseif($aParam['status'] == 1){
            $data['status'] = 0;
        }
        return $this->model->setStatus($data['status'],$aParam['dataId']);
    }
    public function edit($aParam,$id){
        return $this->where('id',$id)->update([
            'play_name' => $aParam['play_name'],
            'plan_num' => $aParam['plan_num'],
            'planned_probability' => 40,
            'Winning_count' => 0,
            'total_count' => 1,
        ]);
    }

}
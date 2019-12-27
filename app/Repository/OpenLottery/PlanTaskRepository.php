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
                return '<ul class="control-menu">
                            <li onclick="edit(\'修改\',\'/chat/planTask/edit/'.$aData->id.'\')">修改</li>
                            <li onclick="del(\'删除\',\'/chat/planTask/del/'.$aData->id.'\')">删除</li>
                        </ul>';
//                return $this->lineButtonSplice($aData);
//                          <li onclick="setStatus('.$aData->id.','.$aData->status.')">'.$is_satus.'</li>

            })
            ->editColumn('money', function ($aData){
                return '<input type="text" name="money['.$aData->money.']" data-id="'.$aData->id.'" class="allMoney" style="width:60px;height:25px;" value='.$aData->money.'>';
            })
            ->rawColumns(['control','fact_probability','money'])
            ->setTotalRecords($aData['iCount'])
            ->skipPaging()
            ->make(true);
    }

    public function add($aParam){
        if($this->model->add($aParam))
            return $this->ajaxReturn('添加成功',true);
        return $this->ajaxReturn('添加失败');
    }
    public function edit($aParam,$id){
        if($this->model->edit($aParam,$id))
            return $this->ajaxReturn('修改成功',true);
        return $this->ajaxReturn('修改失败');
    }
    //批量修改金额
    public function setMoney($aData)
    {
        $dataId = $aData['ids'];
        $dataMoney = $aData['moneys'];
        //比较长度
        if (count($dataId) != count($dataMoney)) {
            return false;
        }
        $aData = [];
        foreach ($dataId as $key => $id) {
            $aData[$key]['id'] = $id;
            $aData[$key]['money'] = $dataMoney[$key];
        }
        return $this->model->setMoney($aData,['money'],'id');
    }
    //栏位那修改金额
    public function setAllMoney($aData){
        return $this->model->setAllMoney($aData);
    }
}
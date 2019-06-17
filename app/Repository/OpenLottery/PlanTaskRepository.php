<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/3 0003
 * Time: 21:15
 */

namespace App\Repository\OpenLottery;


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
            ->editColumn('num_digits', function ($aData){
                return '第'.$aData->num_digits.'位';
            })
            ->editColumn('planned_probability', function ($aData){
                return $aData->planned_probability.'%';
            })
            ->editColumn('control', function ($aData){
                return '<ul class="control-menu">
                            <li onclick="edit(\'修改\',\'/SelfOpen/PlanTask/edit/'.$aData->id.'\')">修改</li>
                            <li onclick="del(\'删除\',\'/SelfOpen/PlanTask/del/'.$aData->id.'\')">删除</li>
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

}
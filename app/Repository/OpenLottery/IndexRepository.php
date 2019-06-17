<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/3 0003
 * Time: 21:15
 */

namespace App\Repository\OpenLottery;

use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class IndexRepository
{
    public $gameName = '';
    public function __construct($model)
    {

        if(isset(request()->gameName))
            $this->gameName = request()->gameName;
    }

    public function add($aParam)
    {
        if($this->model->isCheckFieldValue('issue',$aParam['issue']))
            return $this->ajaxReturn('该期号已存在');
        DB::connection($this->model->getConnectionName())->beginTransaction();
        try{
            if($this->model->add($aParam)){
                if(isset($aParam['nums'])){
                    $aParam['nums'] = preg_split('/[^\d]+/', $aParam['nums'], -1, PREG_SPLIT_NO_EMPTY);
                    $config = \App\Repository\GuanOpen\CreateNumRepository::getConf($aParam['gameName']);
                    if(count($aParam['nums']) !== $config['number'])
                        throw new \Exception('号码位数不对');
                    if(array_first($aParam['nums'], function($v) use($config){
                        return $v < $config['min'] || $v > $config['max'];
                    })){
                        throw new \Exception('有号码不符合要求');
                    }
                    if($data = $this->model->reOpen($aParam))
                        throw new \Exception($data);
                }
                DB::connection($this->model->getConnectionName())->commit();
                return $this->ajaxReturn('添加成功',true);
            }
            throw new \Exception('添加失败');
        }catch (\Exception $e){
            DB::connection($this->model->getConnectionName())->rollBack();
            return $this->ajaxReturn($e->getMessage());
        }
        throw new \Exception('添加失败');
    }

    public function reOpen($aParam)
    {
        $config = \App\Repository\GuanOpen\CreateNumRepository::getConf($aParam['gameName']);
        if(count($aParam['nums']) !== $config['number'])
            return $this->ajaxReturn('号码位数不对');

        if(array_first($aParam['nums'], function($v) use($config){
            return $v < $config['min'] || $v > $config['max'];
        }, false) === null){
            return $this->ajaxReturn('有号码不符合要求');
        }

        DB::connection($this->connection)->beginTransaction();
        if(!($data = $this->model->reOpen($aParam)))
            DB::connection($this->connection)->commit();
            return $this->ajaxReturn('成功',true);
        DB::connection($this->connection)->rollBack();
        return $this->ajaxReturn($data ?? '失败');
    }


    //------------------------------------------------------------------------------------------------------------------
    public function index($param)
    {
        $aData = [];
        $count = 0;
        if(isset($param['gameName']) && ($gameName = $param['gameName'])){
            $data = $this->model->indexData($param);
            $aData = $data['aData'];
            $count = $data['iCount'];
        }
        $DataTables = DataTables::of($aData);
        $control = ['control','nums'];
        if($param['gameName'] == 'lhc'){
            array_push($control, 'n1','n2','n3','n4','n5','n6','n7');
            for ($i = 0; $i < 7; $i ++){
                $DataTables->editColumn('n'.($i+1), function ($aData) use($i){
                    return $this->lhc(explode(',', $aData->nums)[$i] ?? '');
                });
            }
        }

        return $DataTables
            ->editColumn('nums', function ($aData){
                if($aData->nums == '' || empty($aData->nums))
                    return '';
                if(method_exists($this, $this->gameName))
                    return $this->{$this->gameName}(explode(',',$aData->nums));
                return $aData->nums;
            })
            ->editColumn('control', function ($aData){
                return $this->lineButtonSplice($aData);
            })
            ->rawColumns($control)
            ->setTotalRecords($count)
            ->skipPaging()
            ->make(true);
    }

    private function ahk3($nums)
    {
        $str = '<div class="T_K3">';
        foreach ($nums as $v){
            $str.="<span><b class='b".$v."'></b></span>";
        }
        $str .='</div>';
        return $str;
    }
    private function gsk3($nums)
    {
        return $this->ahk3($nums);
    }
    private function gxk3($nums)
    {
        return $this->ahk3($nums);
    }
    private function gzk3($nums)
    {
        return $this->ahk3($nums);
    }
    private function hbk3($nums)
    {
        return $this->ahk3($nums);
    }
    private function hebeik3($nums)
    {
        return $this->ahk3($nums);
    }
    private function jsk3($nums)
    {
        return $this->ahk3($nums);
    }

    private function lhc($nums)
    {
        if(empty($nums))
            return $nums;
        if(is_string($nums))
            return "<span class='lhc-sb-".$this->sebo($nums)."'>$nums</span>";
        $str = '';
        foreach ($nums as $v){
            $str.="<span class='lhc-sb-".$this->sebo($v)."'>$v</span>";
        }
        return $str;
    }
    private function sebo($num){
        $red = [1,2,7,8,12,13,18,19,23,24,29,30,34,35,40,45,46];
        $blue = [3,4,9,10,14,15,20,25,26,31,36,37,41,42,47,48];
        $green = [5,6,11,16,17,21,22,27,28,32,33,38,39,43,44,49];
        if(in_array($num,$red)){
            return 'r';
        }
        if(in_array($num,$blue)){
            return 'b';
        }
        if(in_array($num,$green)){
            return 'g';
        }
    }

    private function bjkl8($nums)
    {
        $str = '<div class="T_KL8">';
        foreach ($nums as $v){
            $str.="<span><b class='b".($v * 1)."'></b></span>";
        }
        $str .='</div>';
        return $str;
    }
    private function bjpk10($nums)
    {
        $str = '<div class="T_PK10">';
        foreach ($nums as $v){
            $str.="<span><b class='b".$v."'></b></span>";
        }
        $str .='</div>';
        return $str;
    }
    private function cqssc($nums)
    {
        $str = '<div class="T_SSC">';
        foreach ($nums as $v){
            $str.="<span><b class='b".$v."'></b></span>";
        }
        $str .='</div>';
        return $str;
    }
    private function xjssc($nums)
    {
        $str = '<div class="T_SSC">';
        foreach ($nums as $v){
            $str.="<span><b class='b".$v."'></b></span>";
        }
        $str .='</div>';
        return $str;
    }
    private function cqxync($nums)
    {
        $str = '<div class="L_XYNC">';
        foreach ($nums as $v){
            $str.="<span><b class='b".$v."'></b></span>";
        }
        $str .='</div>';
        return $str;
    }
    private function gdklsf($nums)
    {
        $str = '<div class="T_KLSF">';
        foreach ($nums as $v){
            $str.="<span><b class='b".$v."'></b></span>";
        }
        $str .='</div>';
        return $str;
    }
    private function gd11x5($nums)
    {
        return $this->gdklsf($nums);
    }

}
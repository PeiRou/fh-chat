<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/14
 * Time: 15:49
 */

namespace App\Http\Controllers\OpenLottery;


use App\Http\Requests\PlanTask\AddValidate;
use App\Http\Requests\PlanTask\EditValidate;
use Illuminate\Http\Request;

class PlanTaskController extends BaseController
{
    public $modelName = 'ExcelPlan';

    public function index(Request $request)
    {
        if($request->isMethod('post'))
            return $this->repository->index($request->except('_token'));
        $aData = $this->repository->indexSelect($request);
        return $this->viewReturn(compact('aData'));
    }

    public function add(AddValidate $request){
        if($request->isMethod('post'))
            return $this->repository->add($request->except('_token'));
        $aData = $this->repository->indexSelect();
        return $this->viewReturn(compact('aData'));
    }

    public function edit(EditValidate $request,$id){
        if($request->isMethod('post'))
            return $this->repository->edit($request->except('_token'),$id);
        $aData = $this->repository->indexSelect();
        $iInfo = $this->repository->getDataByField($id);
        return $this->viewReturn(compact('aData','iInfo'));
    }

}
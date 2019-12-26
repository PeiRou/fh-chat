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

    //批量修改金额
    public function setMoney(Request $request){
        $data = $request->all();
        if(!(isset($data['ids']))){
            if(!is_numeric($data['moneys'])){
                return response()->json([
                    'status'=>false,
                    'msg'=> '金额为整数'
                ]);
            }
            $result = $this->repository->setAllMoney($data);
        } else{
            $result = $this->repository->setMoney($data);
        }
        if($result){
            return response()->json([
                'status'=>true,
            ]);
        }
        return response()->json([
            'status'=>false,
            'msg'=> 'error'
        ]);


    }

}
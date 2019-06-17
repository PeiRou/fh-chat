<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/3 0003
 * Time: 20:02
 */

namespace App\Repository\OpenLottery;

use App\Repository\BaseRepository as Base;
use App\Service\FactoryService;

class BaseRepository extends Base
{

    public $ModelInstance;

    public function __construct($model)
    {
        if(is_null($this->otherModel))
            $this->otherModel = new \stdClass;
        if(is_null($this->otherRepository))
            $this->otherRepository = new \stdClass;
        if(is_null($this->model)){
            $this->model = app("\\App\\Model\\".$model);
            if(isset(request()->gameName))
                $this->model->setTable(request()->gameName);
        }
    }

    public function __get($value){

    }

    //返回数据形式
    public function ajaxReturn($msg = "",$status = false,$data = [],$code = 200){
        return response()->json([
            'status' => $status,
            'msg' => $msg,
            'data' => $data,
        ],$code);
    }

    public function getOtherModel($model){
        if(empty($this->otherModel->$model))
            $this->otherModel->$model = app('\App\\Model\\'.ucfirst($model));
        return $this->otherModel->$model;
    }

    //获取制定栏位的数据，默认为id
    public function getDataByField($param,$field = 'id'){
        return $this->model->getDataByField($param,$field);
    }

    //修改
    public function edit($aParam,$id){
        if($this->model->edit($aParam,$id))
            return $this->ajaxReturn('修改成功',true);
        return $this->ajaxReturn('修改失败');
    }
    //删除
    public function del($id){
        if($this->model->del($id))
            return $this->ajaxReturn('删除成功',true);
        return $this->ajaxReturn('删除失败');
    }

}
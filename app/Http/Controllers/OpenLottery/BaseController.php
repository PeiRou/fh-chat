<?php

namespace App\Http\Controllers\OpenLottery;

use Illuminate\Http\Request;

class BaseController
{

    public $modelName = 'Base';

    protected $view;

    protected $otherRepository;

    protected $repository;

    public function __construct(Request $request)
    {
        $this->init($request);
        if(is_null($this->otherRepository))
            $this->otherRepository = new \stdClass;
        if(is_null($this->repository)){
            $this->repository = app('\\App\\Repository\\OpenLottery\\'.ucfirst($this->getController()).'Repository');
        }
    }
    /**
     * 初始化
     * @param string $request
     */
    protected function init($request){
        $url = $request->route()->getActionName();
        list($this->currentController,$this->currentAction) = explode('@',substr($url,strripos($url,"\\") + 1));
        $this->view = 'OpenLottery.'.$this->getController().'.'.$this->currentAction;
    }
    protected function getController(){
        return lcfirst(str_replace('Controller','',$this->currentController));
    }
    public function viewReturn($aData = []){
        return view($this->view,$aData);
    }
    public function del($id){
        return $this->repository->del($id);
    }
}

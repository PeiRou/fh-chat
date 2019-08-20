<?php

namespace App\Socket\Http\Controllers;

use App\Socket\Http\Controllers\Traits\Login;
use App\Socket\Model\OtherDb\PersonalLog;

class ChatLog extends Base
{
    use Login;

    public $is_role = false;

    //获取日志
    public function getList()
    {
        if(!($id = (int)$this->get('id')) || empty($type = $this->get('type'))){
            return $this->show(1, '参数错误');
        }
        $param = [];
        $param['page_size'] = (int)$this->get('page_size') ?: 5;
        $this->get('index') && $param['index'] = $this->get('index');
        if($w = $this->get('w')){
            if($w == 'GT'){
                $param['w'] = '>';
            }elseif($w == 'LT'){
                $param['w'] = '<';
            }
        }
        if($type == 'room'){ # 群聊
            if($id !== 2){
                $list = PersonalLog::getRoomLog($id, $param);
            }else{
                $list = PersonalLog::getManyLog($this->user['userId'], $this->user['userId'], $id, $param);
            }
        }elseif($type == 'users'){ #  单聊
            $list = PersonalLog::getPersonalLog($this->user['userId'], $id, $param);
        }elseif($type == 'many'){ # 多对一
            $list = PersonalLog::getManyLog($this->user['userId'], $id, $this->roomId ?? 2, $param);
        }else{
            return $this->show(1, '类型错误');
        }
        foreach ($list as $k => $v){
            if(isset($v['status']) && !in_array($v['status'],array(8,9))) {         //状态非红包
                if($v['k']==md5($this->user['userId']))
                    $list[$k]['status'] = 4;
                else
                    $list[$k]['status'] = 2;
            }else{
                $list[$k]['msg'] = base64_encode(str_replace('+', '%20', urlencode($v['msg'])));
            }
        }
        return $this->show(0, '', array_values($list), false);
    }

}
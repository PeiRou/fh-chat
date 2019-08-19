<?php

namespace App\Socket\Http\Controllers;

use App\Socket\Http\Controllers\Traits\Login;
use App\Socket\Model\OtherDb\PersonalLog;

class ChatLog extends Base
{
    use Login;

    //获取日志
    public function getList()
    {
        if(!($id = (int)$this->get('id')) || empty($type = $this->get('type'))){
            return $this->show(1, '参数错误');
        }
        $param = [];
        $this->get('index') && $param['index'] = $this->get('index');

        if($type == 'room'){ # 群聊
            if($id !== 2){
                $list = PersonalLog::getRoomLog($id, $param);
            }else{
                $list = PersonalLog::getManyLog($this->user['userId'], $this->user['userId'], $id, $param);
            }
        }elseif($type == 'users'){ #  单聊
            $list = PersonalLog::getPersonalLog($this->user['userId'], $id, $param);
        }elseif($type == 'many'){ # 多对一
            $list = PersonalLog::getManyLog($this->user['userId'], $id, $this->roomId ?? 2, []);
        }else{
            return $this->show(1, '类型错误');
        }
        foreach ($list as $k => $v){
            if($v['k']==md5($this->user['userId']))
                $list[$k]['status'] = 4;
            else
                $list[$k]['status'] = 2;
        }
        return $this->show(0, '', $list);
    }

}
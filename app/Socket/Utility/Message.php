<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/29
 * Time: 17:06
 */

namespace App\Socket\Utility;


use App\Socket\Model\ChatUser;

class Message
{

    //过滤消息
    public static function filterMsg($msg, $iRoomInfo)
    {
        //消息过滤HTML标签
        $aMesgRep = urldecode(base64_decode($msg));
        $aMesgRep = trim ($aMesgRep);
        $aMesgRep = strip_tags ($aMesgRep);
        $aMesgRep = htmlspecialchars ($aMesgRep);
        $aMesgRep = addslashes ($aMesgRep);
        $aMesgRep = str_replace('&amp;', '&', $aMesgRep);
        //消息处理违禁词
        if((empty($iRoomInfo['level']) || $iRoomInfo['level'] != 99) && self::isRegSpeaking($aMesgRep))
            $aMesgRep = self::regSpeaking($aMesgRep);
        return $aMesgRep;
    }

    public static function isRegSpeaking($msg)
    {
        if(preg_match('/^img=\/upchat\/dataimg\//', $msg)){
            return false;
        }
        return true;
    }

    //消息处理违禁词
    public static function regSpeaking($str)
    {
        // 不重要 读缓存
        if(!$aRegex = cache('chat_regex_regex')){
            $aRegex = \App\Socket\Pool\MysqlPool::invoke(function (\App\Socket\Pool\MysqlObject $db){
                return $db->get('chat_regex', null, ['regex']);
            });
            count($aRegex) && cache(['chat_regex_regex' => $aRegex], 1);
        }
        if(count($aRegex)){
            $aRegStr = "";
            foreach ($aRegex as $key => $val){
                $aRegStr .= "(".$val['regex'].")|";
            }
            $aRegStr = substr($aRegStr,0,-1);
            $str=preg_replace("/".$aRegStr."/is","***", $str);
        }
        return $str;
    }

    //发言权限
    public static function is_speak(int $fd, array $iRoomInfo, string $type, int $id)
    {
        //不广播被禁言的用户noSpeak 只用到群组
        if($type == 'room' && $iRoomInfo['noSpeak']==1){
            app('swoole')->sendToSerf($fd,5,'此帐户已禁言');
            return false;
        }
        if(!\App\Socket\Pool\MysqlPool::invoke(function (\App\Socket\Pool\MysqlObject $db) use($fd, $iRoomInfo) {
            $status = ChatUser::getUserValue(['users_id' => $iRoomInfo['userId']], 'chat_status');
            if($status !== 0){
                app('swoole')->sendToSerf($fd,5,'此帐户已禁言');
                return false;
            }
            return true;
        }))
            return false;

        return \App\Socket\Pool\RedisPool::invoke(function (\App\Socket\Pool\RedisObject $redis) use($iRoomInfo, $fd, $type, $id) {
            $redis->select(1);
            //如果全局禁言
//            if($type == 'room' && $redis->exists('speak') && $redis->get('speak')=='un'){
//                app('swoole')->sendToSerf($fd,5,'当前聊天室处于禁言状态！');
//                return false;
//            }

            if($redis->setnx($iRoomInfo['userId'].'speaking:', 'no')){
                $redis->expire($iRoomInfo['userId'].'speaking:', 2);
                return true;
            }
            $iRoomCss = app('swoole')->cssText(98,4);
            $Css['name'] = '系统消息';                          //用户显示名称
            $Css['level'] = 0;                                //用户背景颜色1
            $Css['bg1'] = $iRoomCss->bg_color1;                //用户背景颜色1
            $Css['bg2'] = $iRoomCss->bg_color2;                //用户背景颜色2
            $Css['font'] = $iRoomCss->font_color;              //用户会话文字颜色
            $Css['img'] = '/game/images/chat/sys.png';         //用户大头
            app('swoole')->sendToSerf($fd,13,'您说话太快啦，请先休息一会',$Css);
            return false;

//            if($redis->exists($iRoomInfo['userId'].'speaking:')){
//                $iRoomCss = app('swoole')->cssText(98,4);
//                $Css['name'] = '系统消息';                          //用户显示名称
//                $Css['level'] = 0;                                //用户背景颜色1
//                $Css['bg1'] = $iRoomCss->bg_color1;                //用户背景颜色1
//                $Css['bg2'] = $iRoomCss->bg_color2;                //用户背景颜色2
//                $Css['font'] = $iRoomCss->font_color;              //用户会话文字颜色
//                $Css['img'] = '/game/images/chat/sys.png';         //用户大头
//                app('swoole')->sendToSerf($fd,13,'您说话太快啦，请先休息一会',$Css);
//                return false;
//            }
//            $redis->setex($iRoomInfo['userId'].'speaking:',2,'on');
//            return true;
        });
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/29
 * Time: 17:06
 */

namespace App\Socket\Utility;


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
        if(empty($iRoomInfo['level']) || $iRoomInfo['level'] != 99)
            $aMesgRep = self::regSpeaking($aMesgRep);
        return $aMesgRep;
    }

    //消息处理违禁词
    public static function regSpeaking($str)
    {
        // 不重要 读缓存
        if(!$aRegex = cache('chat_regex_regex')){
            $aRegex = \App\Socket\Pool\MysqlPool::invoke(function (\App\Socket\Pool\MysqlObject $db){
                return $db->get('chat_regex', null, ['regex']);
            });
            count($aRegex) && cache(['chat_regex_regex' => $aRegex], 60 * 60 * 2);
        }
        $aRegStr = "";
        foreach ($aRegex as $key => $val){
            $aRegStr .= "(".$val['regex'].")|";
        }
        $aRegStr = substr($aRegStr,0,-1);
        $str=preg_replace("/".$aRegStr."/is","***", $str);
        return $str;
    }
}
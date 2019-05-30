<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/25
 * Time: 16:21
 */

namespace App\Socket\Utility;


class SqlBuild
{

    public static function getUpdateSet($data){
        $str = '';
        foreach ($data as $key => $value){
            $str .= "`".$key."` = '".$value."' ";
        }
        return $str;
    }

    public static function getInsertValues($aData, $isCheckVal = 1){
        $strKey = '';
        $strValue = '';
        $i = 0;
        if(count($aData) == count($aData, 1)){
            $val = array_map(function($v){
                return self::essence($v);
            },array_values($aData));
            $keys = array_keys($aData);
            $strKey = '('.implode(',', $keys).') VALUES';
            $strValue = '('.implode(',', $val).')';
        }else{
            foreach ($aData as $iData){
                if($i === 0){
                    $strKey .= " (";
                }
                $strValue .= " (";
                foreach ($iData as $key => $value){
                    if($i === 0){
                        $strKey .= "`".$key."`,";
                    }
                    $strValue .= self::essence($value).",";
                }
                if($i === 0){
                    $strKey = substr($strKey,0,strlen($strKey)-1).') VALUES';
                }
                $strValue = substr($strValue,0,strlen($strValue)-1).')';
                $i++;
            }
        }

        return $strKey.$strValue;
    }
    function bindParam(&$sql, $location, $var, $type) {
        global $times;
        //确定类型
        switch ($type) {
            //字符串
            default:                    //默认使用字符串类型
            case 'STRING' :
                $var = addslashes($var);  //转义
                $var = "'".$var."'";      //加上单引号.SQL语句中字符串插入必须加单引号
                break;
            case 'INTEGER' :
            case 'INT' :
                $var = (int)$var;         //强制转换成int
            //还可以增加更多类型..
        }
        //寻找问号的位置
        for ($i=1, $pos = 0; $i<= $location; $i++) {
            $pos = strpos($sql, '?', $pos+1);
        }
        //替换问号
        $sql = substr($sql, 0, $pos) . $var . substr($sql, $pos + 1);
    }
    public static function essence($val = '', $isCheckVal = 1)
    {

        if(!$isCheckVal)
            return $val;
        if(is_string($val)) {
            $val = addslashes($val);  //转义
            $val = "'".$val."'";      //加上单引号.SQL语句中字符串插入必须加单引号
        }
        return $val;
    }
}
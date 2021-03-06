<?php
/**
 * Created by PhpStorm.
 * User: BeiYinMei
 * Date: 2018/3/23 0023
 * Time: 20:41
 */


if(!function_exists('curl')){
    function curl($url, $params = false, $ispost = 0, $https = 0)
    {
        //$httpInfo = array();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.118 Safari/537.36');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($https) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
        }
        if ($ispost) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_URL, $url);
        } else {
            if ($params) {
                if (is_array($params)) {
                    $params = http_build_query($params);
                }
                curl_setopt($ch, CURLOPT_URL, $url . '?' . $params);
            } else {
                curl_setopt($ch, CURLOPT_URL, $url);
            }
        }
        $response = curl_exec($ch);
        if ($response === FALSE) {
            //echo "cURL Error: " . curl_error($ch);
            return false;
        }
//        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//        $httpInfo = array_merge($httpInfo, curl_getinfo($ch));
        curl_close($ch);
        return $response;
    }
}

/***
 * @param $str
 * @return string
 */
if(!function_exists('user_substr')){
    function user_substr($str){
        return  mb_substr($str,0,2,'UTF-8').'****';
    }
}

/***
 * @return string
 */
if(!function_exists('orderNumber')){
    function orderNumber(){
        $c = "B";
        $date = date('YmdHis');
        $randnum = rand(10000000,99999999);
        return $c.$date.$randnum;
    }
}

if(!function_exists('payOrderNumber')){
    function payOrderNumber(){
        $c = "PAY";
        $date = date('YmdHis');
        $randnum = rand(10000000,99999999);
        return $c.$date.$randnum;
    }
}

/***
 * @param $collect
 * @param $num
 * @param null $except
 * @return mixed
 */
if(!function_exists('da')){
    function da($collect,$num,$except=null){
        $result = $collect->filter(function($item,$index) use ($num){
            return (int)$item>=$num;
        })->except($except);
        return $result;
    }
}

/***
 * @param $collect
 * @param $num
 * @param null $except
 * @return mixed
 */
if(!function_exists('xiao')){
    function xiao($collect,$num,$except=null){
        $result = $collect->filter(function($item,$index) use ($num){
            return (int)$item<=$num;
        })->except($except);
        return $result;
    }
}

/***
 * @param $collect
 * @return mixed
 */
if(!function_exists('dan')){
    function dan($collect){
        $result = $collect->filter(function ($item, $index) {
            if ((int)$item % 2 === 1) return $item;
        });
        return $result;
    }
}

/***
 * @param $collect
 * @return mixed
 */
if(!function_exists('shuang')){
    function shuang($collect){
        $result = $collect->filter(function($item,$index) {
            if((int)$item%2===0) return $item;
        });
        return $result;
    }
}


/****
 * @param $openArr
 * @param $chunkSize
 * @param $playIds
 * @return array
 */
if(!function_exists('longHu')){
    function longHu($openArr,$chunkSize,$playIds){
        $_arrs    = array_chunk($openArr,$chunkSize);
        $firstArr = reset($_arrs);
        $_lastArr = end($_arrs);
        krsort($_lastArr);
        $result = [];
        $lastArr = array_values($_lastArr);
        for($i = 0;  $i<$chunkSize; $i++){
            if((int)$firstArr[$i]>(int)$lastArr[$i]){
                $result['long'][] = $playIds[$i];
            }else{
                $result['hu'][] = $playIds[$i];
            }
        }
        return $result;
    }
}

/****
 * @param $codes
 * @return string
 */
if(!function_exists('specialSsc')){
    function specialSsc($codes){
        sort($codes); //倒序排序
        $_codes = array_values($codes);//重新索引数组
        $_count = count(array_unique($_codes));
        if ($_count === 1) {    //豹子
            return 'BAOZI';
        } else if ($_count == 2) {   //对子
            return 'DUIZI';
        } else {
            $num1 = (int)reset($_codes);
            $num2 = (int)$_codes[1];
            $num3 = (int)end($_codes);
            $shunZi = ($num1 + 1 === $num2 && $num3 - 1 === $num2) || ($num1 + $num2 === 1 && $num3 === 9);
            $banShun = ($num1 + 1 === $num2 && $num3 - 1 === $num2) || ($num1 + $num2 === 1 && $num3 !== 9) || ($num1 === 0 && $num3 === 9);
            if ($shunZi) return 'SHUNZI'; //顺子
            else if ($banShun) return 'BANSHUN'; //半顺
            else    return 'ZALIU';        //杂六
        }
    }
}
/***
 * @param $table
 * @param $conditions_field
 * @param $values_field
 * @param $conditions
 * @return string
 */
if(!function_exists('betBatchWinUpdate')){
    function betBatchWinUpdate( $conditions)
    {
        if(count($conditions)>1){
            $sql  = 'update bet set  bunko = bunko+ case  bet_id ';
            foreach ($conditions as $key => $v) {
                $id []  = $v['bet_id'];
                $sum    = (float)$v['bet_money'] * (float)$v['play_odds'];
                $sql   .= ' when ' . $v['bet_id'] . ' then '.$sum;
            }
                $sql .= ' end where bet_id in(' . implode(',', $id) . ')';
        }else{
            $sum    = (float)reset($conditions)['bet_money'] * (float)reset($conditions)['play_odds'];
            $sql = 'update bet set  bunko = bunko+'.$sum .' where bet_id = '.reset($conditions)['bet_id'] ;
        }
        return $sql;
    }
}

/***
 * @param $table
 * @param $conditions_field
 * @param $values_field
 * @param $conditions
 * @return string
 */
if(!function_exists('betBatchNoWinUpdate')){
    function betBatchNoWinUpdate( $conditions)
    {
        if(count($conditions)>1){
            $sql  = 'update bet set  bunko = bunko- case  bet_id ';
            foreach ($conditions as $key => $v) {
                $id []  = $v['bet_id'];
                $sum    = (float)$v['bet_money'] ;
                $sql   .= ' when ' . $v['bet_id'] . ' then '.$sum;
            }
            $sql .= ' end where bet_id in(' . implode(',', $id) . ')';
        }else{
            $sum    = (float)reset($conditions)['bet_money'] ;
            $sql = 'update bet set  bunko = bunko+'.$sum .' where bet_id = '.reset($conditions)['bet_id'] ;
        }
        return $sql;
    }
}

/***
 * @param $table
 * @param $conditions_field
 * @param $values_field
 * @param $conditions
 * @return string
 */
if(!function_exists('userBatchUpdate')){
    function userBatchUpdate( $conditions)
    {
        $newArr = [];
        foreach ($conditions as $k=>$v){
            if(isset($newArr[$v['user_id']])){
                $newArr[$v['user_id']] += (float)$v['bunko'];
            }else{
                $newArr[$v['user_id']] = (float)$v['bunko'];
            }
        }
        if(count($newArr)>1){
            $sql  = 'update users set money = money+  case id ';
            foreach ($newArr as $k => $v) {
                $id []  = $k;
                $sql   .= ' when ' . $v['user_id'] . ' then '.$v;
            }
            $sql .= ' end where id in(' . implode(',', $id) . ')';
        }else{
            $sql = 'update users set money = money+ '.reset($newArr).' where id = '.key($newArr);
        }
        return $sql;
    }
}

//接口抛出异常
if(!function_exists('ThrowOut')){
    function ThrowOut($code, $message = '', $data = [], $httpCode = 200)
    {
        $data = [
            'code' => $code,
            'msg' => $message,
            'data' => (object)$data,
        ];
        throw new \App\Exceptions\ApiException($data,$httpCode);
    }
}
/**
 * 获取cdn下的真实ip
 */
if(!function_exists('realIp')){
    function realIp(){

//        return isset($_SERVER['HTTP_X_FORWARDED_FOR'])?$_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR'];
        $ip = false;
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $_SERVER['HTTP_X_FORWARDED_FOR'] = preg_replace('/\s+/','', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            if ($ip) {
                array_unshift($ips, $ip);
                $ip = FALSE;
            }
            for ($i = 0; $i < count($ips); $i++) {
                if (!preg_match('/^(10│172.16│192.168)./', $ips[$i])) {
                    $ip = $ips[$i];
                    break;
                }
            }
        }

        return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
    }
}

//记录日志 可以指定文件夹
if(!function_exists('writeLog')) {
    function writeLog($path = '', ...$args)
    {
        if(isset($args[0]) && (is_array($args[0]) || is_object($args[0])))
            $args[0] = json_encode($args[0], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if(isset($args[1]))
            $args[1] = (array)$args[1];

        try {
            $log = new \Monolog\Logger('before');
            $log->pushHandler(new \Monolog\Handler\StreamHandler(storage_path('logs/' . $path . '/' . date('Y-m-d').'.log'), \Monolog\Logger::DEBUG));
            $log->info(...$args);
        } catch (\Exception $e) {
            \Log::info('日志记录失败：' . $e->getMessage());
        }

    }
}
if(!function_exists('p')){
    function p($var, $is_die = 0)
    {
        if (is_array($var)) {
            echo "<pre style='position:relative;z-index:1000;padding:10px;border-radius:5px;background:#f5f5f5;border:1px solid #aaa;font-size:14px;line-height:18px;opacity:0.9;'>".print_r($var, true).'</pre>';
        } else {
            var_dump($var);
        }
        if ($is_die == 1) {
            exit;
        }
    }
}

//上传base64图片
if(!function_exists('upImg')){
    function upImg($path, $imageName, $base64)
    {
        $image = $base64;    //接收base64的图
        $limit = strpos($image,'base64,');
        $image = substr($image,$limit+7);
        $image = str_replace(' ', '+', $image);
        $docUrl = public_path().$path;
        if(!file_exists($docUrl))                   //如果资料夹不存在，则创建资料夹
            mkdir($docUrl);
        return (\File::put(public_path().$path. $imageName, base64_decode($image)));
    }
}

//上传图片可以上传的格式
if(!function_exists('checkImg')) {
    function checkImg($type = '')
    {
        return in_array(trim($type, '.'), [
            'png', 'jpeg', 'bmp', 'jpg', 'gif'
        ]);
    }
}


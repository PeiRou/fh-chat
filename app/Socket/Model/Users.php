<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/17
 * Time: 21:43
 */

namespace App\Socket\Model;


class Users extends Base
{
    protected static $DB_READ_FUNCTION = ['getUserBetDay','getUserLotteryDay','getUserGamesApiDay','getUserRecharges'];

    /**
     * 获取用户彩票投注
     * @param $db
     * @param $userId
     * @param int $day 多少天
     */
    protected static function getUserLotteryDay($db, $param, $isSaveCache = false)
    {
        return self::RedisCacheData(function () use ($db, $param) {
            $where = '';
            isset($param['userId']) && $where .= " AND `user_id` = {$param['userId']} ";
            isset($param['timeStart'], $param['timeEnd']) && $where .= " AND `created_at` BETWEEN '{$param['timeStart']}' AND '{$param['timeEnd']}' ";
            $sql = "SELECT SUM(`bet_money_all`) AS `bet_money_all` FROM (SELECT
                    SUM( bet_money ) AS `bet_money_all` 
                FROM
                    `bet`  
                WHERE 1
                    {$where}
                UNION
                SELECT
                    SUM( bet_money ) AS bet_money_all 
                FROM
                    `bet_his` 
                WHERE 1
                    {$where}
                    ) AS `uall`";
            $res = $db->rawQuery($sql);
            return $res[0]['bet_money_all'] ?? 0;
        }, 60 * 5, false, $isSaveCache);
    }

    /**
     * 用户第三方投注
     * @param $db
     * @param $userId
     * @param int $day 多少天
     */
    protected static function getUserGamesApiDay($db, $param, $isSaveCache = false)
    {
        return self::RedisCacheData(function () use ($db, $param) {
            $where = '';
            isset($param['userId']) && $where .= " AND `user_id` = {$param['userId']} ";
            isset($param['timeStart'], $param['timeEnd']) && $where .= " AND `updated_at` BETWEEN '{$param['timeStart']}' AND '{$param['timeEnd']}' ";
            $sql = "SELECT SUM(`bet_money_all`) AS `bet_money_all` FROM (
              SELECT
                    SUM( `AllBet` ) AS `bet_money_all` 
                FROM
                    `jq_bet` 
                WHERE 1
                    {$where}
                UNION
                SELECT
                    SUM( `AllBet` ) AS `bet_money_all` 
                FROM
                    `jq_bet_his` 
                WHERE 1
                    {$where}
            ) AS `uall`";
            $res = $db->rawQuery($sql);
            return $res[0]['bet_money_all'] ?? 0;
        }, 60 * 5, false, $isSaveCache);
    }

    /**
     * @param $db
     * @param $userId 会员id
     * @param int  $day
     * @param bool $isSaveCache 是否更新缓存
     * @return mixed
     */
    protected static function getUserBetDay($db, $param, $isSaveCache = false)
    {
        return self::RedisCacheData(function()use($db, $param){
            # 彩票
            $lottery = Users::getUserLotteryDay($db, $param, true);
            # 第三方游戏
            $gamesApi = Users::getUserGamesApiDay($db, $param, true);
            return $lottery + $gamesApi;
        }, 60 * 5, false, $isSaveCache);
    }

    protected static function getUserRecharges($db, $param, $isSaveCache = false)
    {
        return self::RedisCacheData(function()use($db, $param){
            return $db
                    ->where('userId',$param['userId'])
                    ->where('status',2)
                    ->where('addMoney',1)
                    ->where ('created_at', ['BETWEEN' => [date("Y-m-d H:i:s",strtotime("-30 day")), date("Y-m-d H:i:s",time())]])
                    ->getOne('recharges', 'sum(`amount`) as amount')['amount'] ?? 0;

        }, 120, false, $isSaveCache);
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/3 0003
 * Time: 20:10
 */

namespace App\Model;


use Illuminate\Support\Facades\DB;

class ExcelPlan extends Base
{

    protected $table = 'excel_plan';

    public $timestamps = false;

    public function indexData($aParam)
    {
        $iModel = $this->select('excel_plan.*','game.game_name')
            ->where(function ($aSql) use($aParam){
                if(isset($aParam['game_id']) && array_key_exists('game_id',$aParam))
                    $aSql->where('excel_plan.game_id',$aParam['game_id']);
            })
            ->join('excel_base','excel_base.game_id','=','excel_plan.game_id')
            ->leftJoin('game', 'game.game_id', '=', 'excel_base.game_id')
            ->orderBy('excel_plan.game_id','asc')->orderBy('excel_plan.num_digits','asc');
        return [
            'iCount' => $iModel->count(),
            'aData' => $iModel
                ->get(),
        ];
    }

    public function add($aParam){
        $dateTime = date('Y-m-d H:i:s');
        return $this->insert([
            'game_id' => $aParam['game_id'],
            'type' => $aParam['type'],
            'play_name' => $aParam['play_name'],
            'num_digits' => empty($aParam['num_digits'])?0:$aParam['num_digits'],
            'planned_probability' => 40,
            'Winning_count' => 0,
            'total_count' => 1,
            'count_date' => $dateTime,
            'created_at' => $dateTime,
            'updated_at' => $dateTime,
        ]);
    }

    public function edit($aParam,$id){
        return $this->where('id',$id)->update([
            'play_name' => $aParam['play_name'],
            'planned_probability' => 40,
            'Winning_count' => 0,
            'total_count' => 1,
        ]);
    }

    //删除
    public function del($param,$field = ''){
        if(empty($field))   $field = $this->primaryKey;
        DB::beginTransaction();
        try {
            DB::table('plan_record')->where('plan_id', $param)->delete();
            $this->where($field, $param)->delete();
            DB::commit();
            return true;
        }catch (\Exception $e){
            DB::rollback();
            return false;
        }
    }

}
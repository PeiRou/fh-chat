<?php

namespace App\Http\Requests\PlanTask;

use App\Http\Requests\BaseValidate;

class AddValidate extends BaseValidate
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        if($this->isMethod('post')){
            return [
                'game_id' => 'required|integer',
                'play_name' => 'required|max:50',
                'planned_probability' => 'required|numeric|max:100|min:0',
                'type' => 'required|integer',
            ];
        }
        return [];
    }

    public function messages()
    {
        return [
            'game_id.required' => '游戏必须选择',
            'game_id.integer' => '游戏参数必须是整形',
            'play_name.required' => '玩法名字必须填写',
            'play_name.max' => '玩法名字必须小于50位',
            'planned_probability.required' => '计划百分比必须填写',
            'planned_probability.numeric' => '计划百分比必须是数字',
            'planned_probability.max' => '计划百分比必须小于100%',
            'planned_probability.min' => '计划百分比必须大于0',
            'type.required' => '玩法类型必须选择',
            'type.integer' => '玩法类型必须是整形',
        ];
    }
}

<?php

namespace App\Http\Requests\PlanTask;

use App\Http\Requests\BaseValidate;

class EditValidate extends BaseValidate
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
//                'planned_probability' => 'required|numeric|max:100|min:0',
                'plan_num' => 'required|numeric|max:8|min:1',
            ];
        }
        return [];
    }

    public function messages()
    {
        return [
//            'planned_probability.required' => '计划百分比必须填写',
//            'planned_probability.numeric' => '计划百分比必须是数字',
//            'planned_probability.max' => '计划百分比必须小于100%',
//            'planned_probability.min' => '计划百分比必须大于0',
            'plan_num.required' => '计划个数必须填写',
            'plan_num.numeric' => '计划个数必须是数字',
            'plan_num.max' => '计划个数必须小于8',
            'plan_num.min' => '计划个数必须大于1',
        ];
    }
}

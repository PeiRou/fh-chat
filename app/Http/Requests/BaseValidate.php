<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class BaseValidate extends FormRequest
{
    protected function failedValidation(Validator $validator) {
        if($this->ajax() || $this->wantsJson()) {
            exit(json_encode(array(
                'status' => false,
                'msg' => $validator->getMessageBag()->first()
            )));
        }
        parent::failedValidation($validator);
    }
}

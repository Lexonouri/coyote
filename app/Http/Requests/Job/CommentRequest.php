<?php

namespace Coyote\Http\Requests\Job;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CommentRequest extends FormRequest
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
        return [
            'text' => 'required|string',
            'email' => [
                Rule::requiredIf(function () {
                    return $this->user() === null;
                }),
                'email'
            ],
            'job_id' => [
                'int',
                Rule::exists('jobs', 'id')
            ],
            'parent_id' => [
                'sometimes',
                'int',
                Rule::exists('job_comments', 'id')->whereNull('parent_id')
            ]
        ];
    }
}

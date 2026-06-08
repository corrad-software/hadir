<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateJobTitleRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('job_titles', 'name')->ignore($this->route('job_title'))],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateJobStatusRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('job_statuses', 'name')->ignore($this->route('job_status'))],
        ];
    }
}

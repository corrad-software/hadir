<?php

namespace App\Http\Requests;

class StoreJobStatusRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100|unique:job_statuses,name',
        ];
    }
}

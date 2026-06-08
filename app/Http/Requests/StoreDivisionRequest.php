<?php

namespace App\Http\Requests;

class StoreDivisionRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|integer|exists:divisions,id',
            'attendance_policy_id' => 'nullable|integer',
        ];
    }
}

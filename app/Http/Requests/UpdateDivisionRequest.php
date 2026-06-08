<?php

namespace App\Http\Requests;

class UpdateDivisionRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'parent_id' => 'nullable|integer|exists:divisions,id',
            'attendance_policy_id' => 'nullable|integer',
        ];
    }
}

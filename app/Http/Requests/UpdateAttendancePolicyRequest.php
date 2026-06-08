<?php

namespace App\Http\Requests;

class UpdateAttendancePolicyRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'work_days' => 'sometimes|array|min:1',
            'work_days.*' => 'integer|min:1|max:7',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i',
            'grace_period_minutes' => 'nullable|integer|min:0|max:120',
            'is_active' => 'nullable|boolean',
        ];
    }
}

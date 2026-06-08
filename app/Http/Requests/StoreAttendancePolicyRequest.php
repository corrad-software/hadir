<?php

namespace App\Http\Requests;

class StoreAttendancePolicyRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'work_days' => 'required|array|min:1',
            'work_days.*' => 'integer|min:1|max:7',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'grace_period_minutes' => 'nullable|integer|min:0|max:120',
            'is_active' => 'nullable|boolean',
        ];
    }
}

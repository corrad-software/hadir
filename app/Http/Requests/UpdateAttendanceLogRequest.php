<?php

namespace App\Http\Requests;

class UpdateAttendanceLogRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'nullable|in:on_time,late,early_leave,absent,pending',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}

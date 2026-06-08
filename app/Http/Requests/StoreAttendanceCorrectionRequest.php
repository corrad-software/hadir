<?php

namespace App\Http\Requests;

class StoreAttendanceCorrectionRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attendance_log_id'      => 'required|integer',
            'corrected_check_in_at'  => 'nullable|date',
            'corrected_check_out_at' => 'nullable|date',
            'reason'                 => 'required|string|min:10|max:1000',
        ];
    }
}

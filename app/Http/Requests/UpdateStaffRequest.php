<?php

namespace App\Http\Requests;

class UpdateStaffRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'         => 'nullable|string|max:255',
            'dob'          => 'nullable|date',
            'phone'        => 'nullable|string|max:30',
            'sex'          => 'nullable|in:male,female',
            'job_title_id'  => 'nullable|integer|exists:job_titles,id',
            'job_status_id' => 'nullable|integer|exists:job_statuses,id',
            'address_line1'    => 'nullable|string|max:100',
            'address_line2'    => 'nullable|string|max:150',
            'address_township' => 'nullable|string|max:100',
            'address_postcode' => 'nullable|string|max:10',
            'address_state'    => 'nullable|string|max:60',
            'office_id'    => 'nullable|integer',
            'division_id'  => 'nullable|integer|exists:divisions,id',
            'supervisor_id' => 'nullable|integer|exists:users,id',
        ];
    }
}

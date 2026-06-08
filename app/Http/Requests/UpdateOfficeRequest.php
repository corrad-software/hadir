<?php

namespace App\Http\Requests;

class UpdateOfficeRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => 'sometimes|string|max:255',
            'latitude'      => 'sometimes|numeric|between:-90,90',
            'longitude'     => 'sometimes|numeric|between:-180,180',
            'radius_meters' => 'nullable|integer|min:10|max:50000',
            'policy_id'     => 'sometimes|integer',
        ];
    }
}

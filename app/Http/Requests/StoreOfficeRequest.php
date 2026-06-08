<?php

namespace App\Http\Requests;

class StoreOfficeRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => 'required|string|max:255',
            'latitude'      => 'required|numeric|between:-90,90',
            'longitude'     => 'required|numeric|between:-180,180',
            'radius_meters' => 'nullable|integer|min:10|max:50000',
            'policy_id'     => 'required|integer',
        ];
    }
}

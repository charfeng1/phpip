<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreActorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('readwrite');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|max:100',
            'first_name' => 'nullable|max:60',
            'email' => 'email|nullable|max:45',
            'phone' => 'nullable|max:20',
            'fax' => 'nullable|max:20',
            'address' => 'nullable|max:256',
            'address_mailing' => 'nullable|max:256',
            'address_billing' => 'nullable|max:256',
            'country' => 'nullable|max:2',
            'country_mailing' => 'nullable|max:2',
            'country_billing' => 'nullable|max:2',
            'nationality' => 'nullable|max:2',
            'company_id' => 'nullable|integer|exists:actor,id',
            'parent_id' => 'nullable|integer|exists:actor,id',
            'site_id' => 'nullable|integer|exists:actor,id',
            'phy_person' => 'boolean',
            'small_entity' => 'boolean',
            'warn' => 'boolean',
            'ren_discount' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
        ];
    }
}

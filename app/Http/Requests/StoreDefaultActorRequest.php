<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreDefaultActorRequest extends FormRequest
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
            'actor_id' => 'required|exists:actor,id',
            'role' => 'required|exists:actor_role,code',
            'for_client' => 'nullable|exists:actor,id',
            'for_country' => 'nullable|exists:country,iso',
            'for_category' => 'nullable|exists:matter_category,code',
            'shared' => 'boolean',
        ];
    }
}

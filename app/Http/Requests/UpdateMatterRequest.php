<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateMatterRequest extends FormRequest
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
            'category_code' => 'sometimes|required|exists:matter_category,code',
            'caseref' => 'sometimes|required|string|max:30',
            'country' => 'sometimes|required|exists:country,iso',
            'responsible' => 'sometimes|required|string|max:20',
            'origin' => 'nullable|exists:country,iso',
            'type_code' => 'nullable|exists:matter_type,code',
            'term_adjust' => 'nullable|integer',
            'idx' => 'nullable|integer',
            'expire_date' => 'nullable|date',
            'dead' => 'boolean',
            'notes' => 'nullable|string',
        ];
    }
}

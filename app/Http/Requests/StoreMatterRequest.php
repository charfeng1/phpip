<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreMatterRequest extends FormRequest
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
            'category_code' => 'required|exists:matter_category,code',
            'caseref' => 'required|string|max:30',
            'country' => 'required|exists:country,iso',
            'responsible' => 'required|string|max:20',
            'origin' => 'nullable|exists:country,iso',
            'type_code' => 'nullable|exists:matter_type,code',
            'expire_date' => 'nullable|date',
            'dead' => 'boolean',
            'notes' => 'nullable|string',
            'operation' => 'nullable|in:new,clone,descendant',
            'parent_id' => 'nullable|integer|exists:matter,id',
            'priority' => 'nullable|boolean',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRuleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Rule::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'task' => 'required',
            'trigger_event' => 'required',
            'for_category' => 'required',
            'cost' => 'nullable|numeric',
            'years' => 'nullable|integer',
            'months' => 'nullable|integer',
            'days' => 'nullable|integer',
            'fee' => 'nullable|numeric',
            'use_before' => 'nullable|date',
            'use_after' => 'nullable|date',
        ];
    }
}

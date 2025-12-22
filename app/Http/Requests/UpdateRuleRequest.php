<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRuleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('rule'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'task' => 'sometimes|required',
            'trigger_event' => 'sometimes|required',
            'for_category' => 'sometimes|required',
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

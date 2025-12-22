<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('fee'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'use_after' => 'nullable|date',
            'use_before' => 'nullable|date',
            'cost' => 'nullable|numeric',
            'fee' => 'nullable|numeric',
            'cost_reduced' => 'nullable|numeric',
            'fee_reduced' => 'nullable|numeric',
            'cost_sup' => 'nullable|numeric',
            'fee_sup' => 'nullable|numeric',
            'cost_sup_reduced' => 'nullable|numeric',
            'fee_sup_reduced' => 'nullable|numeric',
        ];
    }
}

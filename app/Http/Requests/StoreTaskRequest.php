<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreTaskRequest extends FormRequest
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
            'trigger_id' => 'required|numeric|exists:event,id',
            'due_date' => 'required',
            'done_date' => 'nullable',
            'cost' => 'nullable|numeric',
            'fee' => 'nullable|numeric',
            'assigned_to' => 'nullable|string|max:20',
            'detail' => 'nullable|string',
            'notes' => 'nullable|string',
        ];
    }
}

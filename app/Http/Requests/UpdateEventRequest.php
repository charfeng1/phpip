<?php

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('event'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => 'sometimes|required|exists:event_name,code',
            'event_date' => 'sometimes|required|date',
            'detail' => 'nullable|max:45',
            'notes' => 'nullable|string',
            'alt_matter_id' => 'nullable|integer|exists:matter,id',
        ];
    }
}

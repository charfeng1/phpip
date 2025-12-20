<?php

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Event::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => 'required|exists:event_name,code',
            'matter_id' => 'required|integer|exists:matter,id',
            'event_date' => 'required|date',
            'detail' => 'nullable|max:45',
            'notes' => 'nullable|string',
            'alt_matter_id' => 'nullable|integer|exists:matter,id',
        ];
    }
}

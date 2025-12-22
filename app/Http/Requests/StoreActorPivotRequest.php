<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreActorPivotRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Uses ActorPivotPolicy to check if user can create actor relationships.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\ActorPivot::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'matter_id' => 'required|numeric',
            'actor_id' => 'required|numeric',
            'role' => 'required',
            'date' => 'nullable|date',
        ];
    }
}

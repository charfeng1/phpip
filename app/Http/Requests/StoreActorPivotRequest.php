<?php

namespace App\Http\Requests;

use App\Models\Matter;
use Illuminate\Foundation\Http\FormRequest;

class StoreActorPivotRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Authorization is based on the parent Matter - users who can update
     * a matter can also manage its actor relationships.
     */
    public function authorize(): bool
    {
        $matter = Matter::findOrFail($this->matter_id);

        return $this->user()->can('update', $matter);
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
            'date' => 'date',
        ];
    }
}

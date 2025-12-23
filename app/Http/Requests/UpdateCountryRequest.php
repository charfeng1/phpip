<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateCountryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|required',
            'ep' => 'boolean',
            'wo' => 'boolean',
            'em' => 'boolean',
            'oa' => 'boolean',
            'renewal_first' => 'nullable|integer',
            'renewal_base' => 'nullable|in:FIL,GRT,PUB',
            'renewal_start' => 'nullable|in:FIL,GRT,PUB',
        ];
    }
}

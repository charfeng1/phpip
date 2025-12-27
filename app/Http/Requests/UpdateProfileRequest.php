<?php

namespace App\Http\Requests;

use App\Http\Requests\Traits\ValidatesPassword;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    use ValidatesPassword;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Users can always update their own profile
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password' => $this->nullablePasswordRules(),
            'email' => 'required|email',
            'language' => 'required|string|max:5',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return $this->passwordMessages();
    }
}

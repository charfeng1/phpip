<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|max:100',
            'login' => 'required|unique:users|max:16',
            'email' => 'required|email|unique:users|max:45',
            'password' => $this->passwordRules(),
            'default_role' => 'nullable|max:5|exists:actor_role,code',
            'company_id' => 'nullable|integer|exists:actor,id',
        ];
    }

    /**
     * Get password validation rules.
     */
    protected function passwordRules(): array
    {
        return [
            'required',
            'confirmed',
            'min:8',
            'regex:/[a-z]/',      // at least one lowercase letter
            'regex:/[A-Z]/',      // at least one uppercase letter
            'regex:/[0-9]/',      // at least one digit
            'regex:/[^a-zA-Z0-9]/', // at least one special character
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'password.regex' => 'Password must contain at least one lowercase letter, one uppercase letter, one digit, and one special character.',
        ];
    }
}

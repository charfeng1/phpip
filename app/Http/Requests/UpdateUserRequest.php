<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $targetUser = $this->route('user');

        // Admin can update any user, users can update themselves
        return $this->user()->can('update', $targetUser);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => 'sometimes|required|max:100',
            'login' => [
                'sometimes',
                'required',
                'max:16',
                Rule::unique('users')->ignore($userId),
            ],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:45',
                Rule::unique('users')->ignore($userId),
            ],
            'password' => $this->passwordRules(),
            'default_role' => 'nullable|max:5|exists:actor_role,code',
            'company_id' => 'nullable|integer|exists:actor,id',
        ];
    }

    /**
     * Get password validation rules (optional for updates).
     */
    protected function passwordRules(): array
    {
        return [
            'sometimes',
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

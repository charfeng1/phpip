<?php

namespace App\Http\Requests;

use App\Http\Requests\Traits\ValidatesPassword;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    use ValidatesPassword;

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
     * Only admins can modify sensitive fields like default_role, company_id, parent_id.
     * Non-admin users can only update their own basic profile fields.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id;
        $isAdmin = $this->user()?->isAdmin();

        $rules = [
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
            'password' => $this->optionalPasswordRules(),
            'language' => 'sometimes|required|string|max:5',
        ];

        // Only admins can modify sensitive/privileged fields
        if ($isAdmin) {
            $rules['default_role'] = 'sometimes|required|max:5|exists:actor_role,code';
            $rules['company_id'] = 'nullable|integer|exists:actor,id';
            $rules['parent_id'] = 'nullable|exists:users,id';
        }

        return $rules;
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return $this->passwordMessages();
    }
}

<?php

namespace App\Http\Requests;

use App\Http\Requests\Traits\ValidatesPassword;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    use ValidatesPassword;

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
            'password' => $this->requiredPasswordRules(),
            'default_role' => 'required|max:5|exists:actor_role,code',
            'company_id' => 'nullable|integer|exists:actor,id',
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

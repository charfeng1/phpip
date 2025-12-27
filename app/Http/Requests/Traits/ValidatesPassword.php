<?php

namespace App\Http\Requests\Traits;

/**
 * Provides shared password validation rules for form requests.
 *
 * This trait centralizes password validation requirements to ensure consistent
 * password strength policies across all user-related forms (registration, update, profile).
 *
 * Password requirements:
 * - Minimum 8 characters
 * - At least one lowercase letter
 * - At least one uppercase letter
 * - At least one digit
 * - At least one special character
 */
trait ValidatesPassword
{
    /**
     * Get password validation rules for required password fields.
     *
     * Use this for user creation where password is mandatory.
     *
     * @return array<int, string>
     */
    protected function requiredPasswordRules(): array
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
     * Get password validation rules for optional password fields.
     *
     * Use this for user updates where password change is optional.
     *
     * @return array<int, string>
     */
    protected function optionalPasswordRules(): array
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
     * Get password validation rules for nullable password fields.
     *
     * Use this for profile updates where password change is optional and not required.
     *
     * @return array<int, string>
     */
    protected function nullablePasswordRules(): array
    {
        return [
            'nullable',
            'confirmed',
            'min:8',
            'regex:/[a-z]/',      // at least one lowercase letter
            'regex:/[A-Z]/',      // at least one uppercase letter
            'regex:/[0-9]/',      // at least one digit
            'regex:/[^a-zA-Z0-9]/', // at least one special character
        ];
    }

    /**
     * Get custom validation messages for password fields.
     *
     * @return array<string, string>
     */
    protected function passwordMessages(): array
    {
        return [
            'password.regex' => 'Password must contain at least one lowercase letter, one uppercase letter, one digit, and one special character.',
            'password.min' => 'Password must be at least 8 characters long.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }
}

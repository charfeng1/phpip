<?php

namespace Tests\Unit\Requests\Traits;

use App\Http\Requests\Traits\ValidatesPassword;
use PHPUnit\Framework\TestCase;

class ValidatesPasswordTest extends TestCase
{
    protected TestableValidatesPasswordRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new TestableValidatesPasswordRequest();
    }

    /** @test */
    public function required_password_rules_contains_required()
    {
        $rules = $this->request->getRequiredPasswordRules();

        $this->assertContains('required', $rules);
        $this->assertContains('confirmed', $rules);
        $this->assertContains('min:8', $rules);
    }

    /** @test */
    public function optional_password_rules_contains_sometimes()
    {
        $rules = $this->request->getOptionalPasswordRules();

        $this->assertContains('sometimes', $rules);
        $this->assertContains('confirmed', $rules);
        $this->assertContains('min:8', $rules);
        $this->assertNotContains('required', $rules);
    }

    /** @test */
    public function nullable_password_rules_contains_nullable()
    {
        $rules = $this->request->getNullablePasswordRules();

        $this->assertContains('nullable', $rules);
        $this->assertContains('confirmed', $rules);
        $this->assertContains('min:8', $rules);
        $this->assertNotContains('required', $rules);
    }

    /** @test */
    public function password_rules_enforce_lowercase_letter()
    {
        $rules = $this->request->getRequiredPasswordRules();

        $this->assertContains('regex:/[a-z]/', $rules);
    }

    /** @test */
    public function password_rules_enforce_uppercase_letter()
    {
        $rules = $this->request->getRequiredPasswordRules();

        $this->assertContains('regex:/[A-Z]/', $rules);
    }

    /** @test */
    public function password_rules_enforce_digit()
    {
        $rules = $this->request->getRequiredPasswordRules();

        $this->assertContains('regex:/[0-9]/', $rules);
    }

    /** @test */
    public function password_rules_enforce_special_character()
    {
        $rules = $this->request->getRequiredPasswordRules();

        $this->assertContains('regex:/[^a-zA-Z0-9]/', $rules);
    }

    /** @test */
    public function password_messages_provides_regex_message()
    {
        $messages = $this->request->getPasswordMessages();

        $this->assertArrayHasKey('password.regex', $messages);
        $this->assertStringContainsString('lowercase', $messages['password.regex']);
        $this->assertStringContainsString('uppercase', $messages['password.regex']);
        $this->assertStringContainsString('digit', $messages['password.regex']);
        $this->assertStringContainsString('special character', $messages['password.regex']);
    }

    /** @test */
    public function password_messages_provides_min_message()
    {
        $messages = $this->request->getPasswordMessages();

        $this->assertArrayHasKey('password.min', $messages);
        $this->assertStringContainsString('8 characters', $messages['password.min']);
    }

    /** @test */
    public function password_messages_provides_confirmed_message()
    {
        $messages = $this->request->getPasswordMessages();

        $this->assertArrayHasKey('password.confirmed', $messages);
    }

    /** @test */
    public function all_password_rule_variants_have_same_complexity_requirements()
    {
        $required = $this->request->getRequiredPasswordRules();
        $optional = $this->request->getOptionalPasswordRules();
        $nullable = $this->request->getNullablePasswordRules();

        // All should have the same complexity rules (min, regexes)
        $complexityRules = [
            'confirmed',
            'min:8',
            'regex:/[a-z]/',
            'regex:/[A-Z]/',
            'regex:/[0-9]/',
            'regex:/[^a-zA-Z0-9]/',
        ];

        foreach ($complexityRules as $rule) {
            $this->assertContains($rule, $required, "Required rules missing: $rule");
            $this->assertContains($rule, $optional, "Optional rules missing: $rule");
            $this->assertContains($rule, $nullable, "Nullable rules missing: $rule");
        }
    }
}

/**
 * Testable class to expose protected trait methods.
 */
class TestableValidatesPasswordRequest
{
    use ValidatesPassword;

    public function getRequiredPasswordRules(): array
    {
        return $this->requiredPasswordRules();
    }

    public function getOptionalPasswordRules(): array
    {
        return $this->optionalPasswordRules();
    }

    public function getNullablePasswordRules(): array
    {
        return $this->nullablePasswordRules();
    }

    public function getPasswordMessages(): array
    {
        return $this->passwordMessages();
    }
}

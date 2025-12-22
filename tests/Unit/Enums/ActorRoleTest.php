<?php

namespace Tests\Unit\Enums;

use App\Enums\ActorRole;
use PHPUnit\Framework\TestCase;

class ActorRoleTest extends TestCase
{
    /** @test */
    public function enum_has_expected_values()
    {
        $this->assertEquals('CLI', ActorRole::CLIENT->value);
        $this->assertEquals('APP', ActorRole::APPLICANT->value);
        $this->assertEquals('OWN', ActorRole::OWNER->value);
        $this->assertEquals('AGT', ActorRole::AGENT->value);
        $this->assertEquals('AGT2', ActorRole::SECONDARY_AGENT->value);
        $this->assertEquals('INV', ActorRole::INVENTOR->value);
        $this->assertEquals('DEL', ActorRole::DELEGATE->value);
        $this->assertEquals('CNT', ActorRole::CONTACT->value);
        $this->assertEquals('PAY', ActorRole::PAYOR->value);
        $this->assertEquals('WRI', ActorRole::WRITER->value);
        $this->assertEquals('ANN', ActorRole::ANNUITY_AGENT->value);
    }

    /** @test */
    public function label_returns_human_readable_string()
    {
        $this->assertEquals('Client', ActorRole::CLIENT->label());
        $this->assertEquals('Applicant', ActorRole::APPLICANT->label());
        $this->assertEquals('Owner', ActorRole::OWNER->label());
        $this->assertEquals('Agent', ActorRole::AGENT->label());
        $this->assertEquals('Secondary Agent', ActorRole::SECONDARY_AGENT->label());
        $this->assertEquals('Inventor', ActorRole::INVENTOR->label());
        $this->assertEquals('Delegate', ActorRole::DELEGATE->label());
        $this->assertEquals('Contact', ActorRole::CONTACT->label());
        $this->assertEquals('Payor', ActorRole::PAYOR->label());
        $this->assertEquals('Writer', ActorRole::WRITER->label());
        $this->assertEquals('Annuity Agent', ActorRole::ANNUITY_AGENT->label());
    }

    /** @test */
    public function inheritable_roles_includes_expected_roles()
    {
        $inheritableRoles = ActorRole::inheritableRoles();

        $this->assertContains(ActorRole::CLIENT, $inheritableRoles);
        $this->assertContains(ActorRole::APPLICANT, $inheritableRoles);
        $this->assertContains(ActorRole::OWNER, $inheritableRoles);
        $this->assertContains(ActorRole::AGENT, $inheritableRoles);
        $this->assertContains(ActorRole::CONTACT, $inheritableRoles);
        $this->assertNotContains(ActorRole::INVENTOR, $inheritableRoles);
    }

    /** @test */
    public function inheritable_role_values_returns_strings()
    {
        $values = ActorRole::inheritableRoleValues();

        $this->assertIsArray($values);
        $this->assertContains('CLI', $values);
        $this->assertContains('APP', $values);
        $this->assertContains('OWN', $values);
        $this->assertContains('AGT', $values);
        $this->assertContains('CNT', $values);
    }

    /** @test */
    public function is_shared_role_returns_true_for_client_applicant_owner()
    {
        $this->assertTrue(ActorRole::CLIENT->isSharedRole());
        $this->assertTrue(ActorRole::APPLICANT->isSharedRole());
        $this->assertTrue(ActorRole::OWNER->isSharedRole());
        $this->assertFalse(ActorRole::AGENT->isSharedRole());
        $this->assertFalse(ActorRole::INVENTOR->isSharedRole());
    }

    /** @test */
    public function all_cases_can_be_enumerated()
    {
        $cases = ActorRole::cases();

        $this->assertCount(11, $cases);
    }
}

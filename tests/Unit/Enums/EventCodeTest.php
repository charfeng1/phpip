<?php

namespace Tests\Unit\Enums;

use App\Enums\EventCode;
use PHPUnit\Framework\TestCase;

class EventCodeTest extends TestCase
{
    /** @test */
    public function enum_has_expected_core_lifecycle_values()
    {
        $this->assertEquals('FIL', EventCode::FILING->value);
        $this->assertEquals('PFIL', EventCode::PCT_FILING->value);
        $this->assertEquals('PUB', EventCode::PUBLICATION->value);
        $this->assertEquals('GRT', EventCode::GRANT->value);
        $this->assertEquals('REG', EventCode::REGISTRATION->value);
        $this->assertEquals('PRI', EventCode::PRIORITY->value);
        $this->assertEquals('ENT', EventCode::ENTRY->value);
        $this->assertEquals('REC', EventCode::RECEIVED->value);
        $this->assertEquals('ALL', EventCode::ALLOWANCE->value);
    }

    /** @test */
    public function enum_has_expected_end_of_life_values()
    {
        $this->assertEquals('ABA', EventCode::ABANDONED->value);
        $this->assertEquals('LAP', EventCode::LAPSED->value);
    }

    /** @test */
    public function enum_has_expected_task_related_values()
    {
        $this->assertEquals('REN', EventCode::RENEWAL->value);
        $this->assertEquals('PR', EventCode::PRIORITY_CLAIM->value);
        $this->assertEquals('EXA', EventCode::EXAMINATION->value);
        $this->assertEquals('REP', EventCode::REPLY->value);
        $this->assertEquals('PAY', EventCode::PAYMENT->value);
    }

    /** @test */
    public function enum_has_expected_procedure_step_values()
    {
        $this->assertEquals('EXRE', EventCode::EXAM_REPORT->value);
        $this->assertEquals('RFEE', EventCode::RENEWAL_FEE->value);
        $this->assertEquals('IGRA', EventCode::INTENTION_TO_GRANT->value);
        $this->assertEquals('EXAM52', EventCode::FILING_REQUEST->value);
    }

    /** @test */
    public function label_returns_human_readable_string()
    {
        $this->assertEquals('Filing', EventCode::FILING->label());
        $this->assertEquals('PCT Filing', EventCode::PCT_FILING->label());
        $this->assertEquals('Publication', EventCode::PUBLICATION->label());
        $this->assertEquals('Grant', EventCode::GRANT->label());
        $this->assertEquals('Registration', EventCode::REGISTRATION->label());
        $this->assertEquals('Renewal', EventCode::RENEWAL->label());
    }

    /** @test */
    public function events_with_numbers_includes_filing_pub_grant_reg()
    {
        $events = EventCode::eventsWithNumbers();

        $this->assertContains(EventCode::FILING, $events);
        $this->assertContains(EventCode::PUBLICATION, $events);
        $this->assertContains(EventCode::GRANT, $events);
        $this->assertContains(EventCode::REGISTRATION, $events);
        $this->assertNotContains(EventCode::RENEWAL, $events);
    }

    /** @test */
    public function linkable_events_includes_filing_pub_grant()
    {
        $events = EventCode::linkableEvents();

        $this->assertContains(EventCode::FILING, $events);
        $this->assertContains(EventCode::PUBLICATION, $events);
        $this->assertContains(EventCode::GRANT, $events);
        $this->assertNotContains(EventCode::REGISTRATION, $events);
    }

    /** @test */
    public function is_linkable_returns_true_for_linkable_events()
    {
        $this->assertTrue(EventCode::FILING->isLinkable());
        $this->assertTrue(EventCode::PUBLICATION->isLinkable());
        $this->assertTrue(EventCode::GRANT->isLinkable());
        $this->assertFalse(EventCode::RENEWAL->isLinkable());
        $this->assertFalse(EventCode::REGISTRATION->isLinkable());
    }

    /** @test */
    public function grant_equivalent_events_includes_grant_and_registration()
    {
        $events = EventCode::grantEquivalentEvents();

        $this->assertContains(EventCode::GRANT, $events);
        $this->assertContains(EventCode::REGISTRATION, $events);
        $this->assertCount(2, $events);
    }

    /** @test */
    public function grant_equivalent_values_returns_strings()
    {
        $values = EventCode::grantEquivalentValues();

        $this->assertIsArray($values);
        $this->assertContains('GRT', $values);
        $this->assertContains('REG', $values);
    }

    /** @test */
    public function renewal_trigger_events_includes_filing_grant_priority_claim()
    {
        $events = EventCode::renewalTriggerEvents();

        $this->assertContains(EventCode::FILING, $events);
        $this->assertContains(EventCode::GRANT, $events);
        $this->assertContains(EventCode::PRIORITY_CLAIM, $events);
    }

    /** @test */
    public function is_renewal_trigger_returns_true_for_trigger_events()
    {
        $this->assertTrue(EventCode::FILING->isRenewalTrigger());
        $this->assertTrue(EventCode::GRANT->isRenewalTrigger());
        $this->assertTrue(EventCode::PRIORITY_CLAIM->isRenewalTrigger());
        $this->assertFalse(EventCode::PUBLICATION->isRenewalTrigger());
        $this->assertFalse(EventCode::RENEWAL->isRenewalTrigger());
    }

    /** @test */
    public function end_of_life_events_includes_abandoned_and_lapsed()
    {
        $events = EventCode::endOfLifeEvents();

        $this->assertContains(EventCode::ABANDONED, $events);
        $this->assertContains(EventCode::LAPSED, $events);
        $this->assertCount(2, $events);
    }

    /** @test */
    public function is_end_of_life_returns_true_for_end_of_life_events()
    {
        $this->assertTrue(EventCode::ABANDONED->isEndOfLife());
        $this->assertTrue(EventCode::LAPSED->isEndOfLife());
        $this->assertFalse(EventCode::FILING->isEndOfLife());
        $this->assertFalse(EventCode::GRANT->isEndOfLife());
    }

    /** @test */
    public function end_of_life_label_returns_human_readable_string()
    {
        $this->assertEquals('Abandoned', EventCode::ABANDONED->label());
        $this->assertEquals('Lapsed', EventCode::LAPSED->label());
    }

    /** @test */
    public function all_cases_can_be_enumerated()
    {
        $cases = EventCode::cases();

        $this->assertCount(20, $cases);
    }
}

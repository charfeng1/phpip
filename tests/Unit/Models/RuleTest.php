<?php

namespace Tests\Unit\Models;

use App\Models\Actor;
use App\Models\Category;
use App\Models\Country;
use App\Models\EventName;
use App\Models\Rule;
use Tests\TestCase;

class RuleTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_can_create_a_rule()
    {
        $rule = Rule::factory()->create([
            'task' => 'REN',
            'trigger_event' => 'FIL',
        ]);

        $this->assertDatabaseHas('task_rules', [
            'task' => 'REN',
            'trigger_event' => 'FIL',
        ]);
        $this->assertNotNull($rule->id);
    }

    /** @test */
    public function it_can_be_active_or_inactive()
    {
        $activeRule = Rule::factory()->active()->create();
        $inactiveRule = Rule::factory()->inactive()->create();

        $this->assertTrue((bool) $activeRule->active);
        $this->assertFalse((bool) $inactiveRule->active);
    }

    /** @test */
    public function it_belongs_to_a_trigger_event()
    {
        $rule = Rule::factory()->create(['trigger_event' => 'FIL']);

        $this->assertNotNull($rule->trigger);
        $this->assertEquals('FIL', $rule->trigger->code);
    }

    /** @test */
    public function it_belongs_to_a_task_event_name()
    {
        $rule = Rule::factory()->renewal()->create();

        $this->assertNotNull($rule->taskInfo);
        $this->assertEquals('REN', $rule->taskInfo->code);
    }

    /** @test */
    public function it_can_belong_to_a_country()
    {
        $rule = Rule::factory()->forCountry('US')->create();

        $this->assertEquals('US', $rule->for_country);
        $this->assertNotNull($rule->country);
    }

    /** @test */
    public function it_can_belong_to_a_category()
    {
        $rule = Rule::factory()->forCategory('PAT')->create();

        $this->assertEquals('PAT', $rule->for_category);
        $this->assertNotNull($rule->category);
    }

    /** @test */
    public function it_can_have_origin_country()
    {
        $rule = Rule::factory()->create(['for_origin' => 'EP']);

        $this->assertEquals('EP', $rule->for_origin);
        $this->assertNotNull($rule->origin);
    }

    /** @test */
    public function it_can_have_condition_event()
    {
        $rule = Rule::factory()->withCondition('GRT')->create();

        $this->assertEquals('GRT', $rule->condition_event);
        $this->assertNotNull($rule->condition_eventInfo);
    }

    /** @test */
    public function it_can_have_abort_event()
    {
        $rule = Rule::factory()->abortsOn('ABD')->create();

        $this->assertEquals('ABD', $rule->abort_on);
    }

    /** @test */
    public function it_can_have_deadline_calculation()
    {
        $rule = Rule::factory()->withDeadline(30, 0, 0)->create();

        $this->assertEquals(30, $rule->days);
        $this->assertEquals(0, $rule->months);
        $this->assertEquals(0, $rule->years);
    }

    /** @test */
    public function it_can_have_month_based_deadline()
    {
        $rule = Rule::factory()->withDeadline(0, 6, 0)->create();

        $this->assertEquals(0, $rule->days);
        $this->assertEquals(6, $rule->months);
        $this->assertEquals(0, $rule->years);
    }

    /** @test */
    public function it_can_have_year_based_deadline()
    {
        $rule = Rule::factory()->withDeadline(0, 0, 1)->create();

        $this->assertEquals(0, $rule->days);
        $this->assertEquals(0, $rule->months);
        $this->assertEquals(1, $rule->years);
    }

    /** @test */
    public function it_can_have_end_of_month_adjustment()
    {
        $rule = Rule::factory()->withDeadline(0, 3, 0, true)->create();

        $this->assertTrue((bool) $rule->end_of_month);
    }

    /** @test */
    public function it_can_be_recurring()
    {
        $rule = Rule::factory()->recurring()->create();

        $this->assertTrue((bool) $rule->recurring);
    }

    /** @test */
    public function it_can_use_parent_date()
    {
        $rule = Rule::factory()->usesParent()->create();

        $this->assertTrue((bool) $rule->use_parent);
    }

    /** @test */
    public function it_can_use_priority_date()
    {
        $rule = Rule::factory()->usesPriority()->create();

        $this->assertTrue((bool) $rule->use_priority);
    }

    /** @test */
    public function it_has_translatable_detail()
    {
        $rule = Rule::factory()->create([
            'detail' => json_encode(['en' => 'Pay renewal fee', 'fr' => 'Payer annuité']),
        ]);

        $this->assertContains('detail', $rule->translatable);
    }

    /** @test */
    public function it_can_have_responsible_actor()
    {
        $actor = Actor::factory()->withLogin()->create();
        $rule = Rule::factory()->create(['responsible' => $actor->login]);

        $this->assertEquals($actor->login, $rule->responsible);
    }

    /** @test */
    public function it_can_have_cost_and_fee()
    {
        $rule = Rule::factory()->create([
            'cost' => 100.50,
            'fee' => 250.00,
        ]);

        $this->assertEquals(100.50, $rule->cost);
        $this->assertEquals(250.00, $rule->fee);
    }

    /** @test */
    public function it_hides_system_attributes()
    {
        $rule = Rule::factory()->create();
        $array = $rule->toArray();

        $this->assertArrayNotHasKey('creator', $array);
        $this->assertArrayNotHasKey('updater', $array);
        $this->assertArrayNotHasKey('created_at', $array);
        $this->assertArrayNotHasKey('updated_at', $array);
    }
}

<?php

namespace Tests\Unit\Models;

use App\Models\Actor;
use App\Models\ActorPivot;
use App\Models\Matter;
use App\Models\Role;
use Tests\TestCase;

class ActorPivotTest extends TestCase
{
    /** @test */
    public function it_uses_matter_actor_lnk_table()
    {
        $pivot = new ActorPivot();

        $this->assertEquals('matter_actor_lnk', $pivot->getTable());
    }

    /** @test */
    public function it_belongs_to_a_matter()
    {
        $matter = Matter::factory()->create();
        $actor = Actor::factory()->create();

        $pivot = ActorPivot::create([
            'matter_id' => $matter->id,
            'actor_id' => $actor->id,
            'role' => 'CLI',
        ]);

        $this->assertInstanceOf(Matter::class, $pivot->matter);
        $this->assertEquals($matter->id, $pivot->matter->id);
    }

    /** @test */
    public function it_belongs_to_an_actor()
    {
        $matter = Matter::factory()->create();
        $actor = Actor::factory()->create(['name' => 'Test Actor']);

        $pivot = ActorPivot::create([
            'matter_id' => $matter->id,
            'actor_id' => $actor->id,
            'role' => 'CLI',
        ]);

        $this->assertInstanceOf(Actor::class, $pivot->actor);
        $this->assertEquals($actor->id, $pivot->actor->id);
        $this->assertEquals('Test Actor', $pivot->actor->name);
    }

    /** @test */
    public function it_belongs_to_a_role()
    {
        $matter = Matter::factory()->create();
        $actor = Actor::factory()->create();

        $role = Role::find('CLI');
        if (!$role) {
            $role = Role::create([
                'code' => 'CLI',
                'name' => ['en' => 'Client'],
            ]);
        }

        $pivot = ActorPivot::create([
            'matter_id' => $matter->id,
            'actor_id' => $actor->id,
            'role' => 'CLI',
        ]);

        $pivotRole = $pivot->role();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $pivotRole);
    }

    /** @test */
    public function it_can_have_a_company()
    {
        $matter = Matter::factory()->create();
        $person = Actor::factory()->person()->create();
        $company = Actor::factory()->company()->create(['name' => 'Acme Corp']);

        $pivot = ActorPivot::create([
            'matter_id' => $matter->id,
            'actor_id' => $person->id,
            'role' => 'INV',
            'company_id' => $company->id,
        ]);

        $this->assertInstanceOf(Actor::class, $pivot->company);
        $this->assertEquals($company->id, $pivot->company->id);
        $this->assertEquals('Acme Corp', $pivot->company->name);
    }

    /** @test */
    public function it_touches_parent_matter()
    {
        $pivot = new ActorPivot();
        $touches = $pivot->getTouchedRelations();

        $this->assertContains('matter', $touches);
    }

    /** @test */
    public function it_hides_audit_fields_on_serialization()
    {
        $matter = Matter::factory()->create();
        $actor = Actor::factory()->create();

        $pivot = ActorPivot::create([
            'matter_id' => $matter->id,
            'actor_id' => $actor->id,
            'role' => 'CLI',
        ]);

        $array = $pivot->toArray();

        $this->assertArrayNotHasKey('creator', $array);
        $this->assertArrayNotHasKey('created_at', $array);
        $this->assertArrayNotHasKey('updated_at', $array);
        $this->assertArrayNotHasKey('updater', $array);
    }

    /** @test */
    public function it_guards_id_and_timestamps()
    {
        $pivot = new ActorPivot();
        $guarded = $pivot->getGuarded();

        $this->assertContains('id', $guarded);
        $this->assertContains('created_at', $guarded);
        $this->assertContains('updated_at', $guarded);
    }

    /** @test */
    public function it_can_store_display_order()
    {
        $matter = Matter::factory()->create();
        $actor = Actor::factory()->create();

        $pivot = ActorPivot::create([
            'matter_id' => $matter->id,
            'actor_id' => $actor->id,
            'role' => 'INV',
            'display_order' => 1,
        ]);

        $this->assertEquals(1, $pivot->display_order);
    }

    /** @test */
    public function it_can_store_actor_reference()
    {
        $matter = Matter::factory()->create();
        $actor = Actor::factory()->create();

        $pivot = ActorPivot::create([
            'matter_id' => $matter->id,
            'actor_id' => $actor->id,
            'role' => 'CLI',
            'actor_ref' => 'CLIENT-REF-123',
        ]);

        $this->assertEquals('CLIENT-REF-123', $pivot->actor_ref);
    }

    /** @test */
    public function it_can_be_shared_with_family()
    {
        $matter = Matter::factory()->create();
        $actor = Actor::factory()->create();

        $pivot = ActorPivot::create([
            'matter_id' => $matter->id,
            'actor_id' => $actor->id,
            'role' => 'CLI',
            'shared' => true,
        ]);

        $this->assertTrue((bool) $pivot->shared);
    }

    /** @test */
    public function same_actor_can_have_multiple_roles_in_same_matter()
    {
        $matter = Matter::factory()->create();
        $actor = Actor::factory()->create();

        $pivot1 = ActorPivot::create([
            'matter_id' => $matter->id,
            'actor_id' => $actor->id,
            'role' => 'CLI',
        ]);

        $pivot2 = ActorPivot::create([
            'matter_id' => $matter->id,
            'actor_id' => $actor->id,
            'role' => 'APP',
        ]);

        $this->assertNotEquals($pivot1->id, $pivot2->id);
        $this->assertEquals($actor->id, $pivot1->actor_id);
        $this->assertEquals($actor->id, $pivot2->actor_id);
    }
}

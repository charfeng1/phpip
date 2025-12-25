<?php

namespace Tests\Unit\Traits;

use App\Models\Actor;
use App\Models\Event;
use App\Models\Matter;
use App\Models\User;
use Tests\TestCase;

class HasActorsFromRoleTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_returns_empty_collection_when_no_actors()
    {
        $matter = Matter::factory()->create();

        $actors = $matter->getActorsFromRole('CLI');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $actors);
        $this->assertCount(0, $actors);
    }

    /** @test */
    public function it_returns_null_when_no_actor_for_role()
    {
        $matter = Matter::factory()->create();

        $actor = $matter->getActorFromRole('CLI');

        $this->assertNull($actor);
    }

    /** @test */
    public function it_returns_client_actor_from_role()
    {
        $matter = Matter::factory()->create();
        $client = Actor::factory()->asClient()->create();

        \DB::table('matter_actor_lnk')->insert([
            'matter_id' => $matter->id,
            'actor_id' => $client->id,
            'role' => 'CLI',
            'shared' => 0,
            'display_order' => 1,
        ]);

        $result = $matter->getActorFromRole('CLI');

        $this->assertNotNull($result);
        $this->assertEquals('CLI', $result->role_code);
    }

    /** @test */
    public function it_returns_multiple_actors_for_role()
    {
        $matter = Matter::factory()->create();
        $inventor1 = Actor::factory()->person()->create();
        $inventor2 = Actor::factory()->person()->create();

        \DB::table('matter_actor_lnk')->insert([
            'matter_id' => $matter->id,
            'actor_id' => $inventor1->id,
            'role' => 'INV',
            'shared' => 0,
            'display_order' => 1,
        ]);

        \DB::table('matter_actor_lnk')->insert([
            'matter_id' => $matter->id,
            'actor_id' => $inventor2->id,
            'role' => 'INV',
            'shared' => 0,
            'display_order' => 2,
        ]);

        $actors = $matter->getActorsFromRole('INV');

        $this->assertCount(2, $actors);
    }

    /** @test */
    public function it_orders_actors_by_display_order()
    {
        $matter = Matter::factory()->create();
        $inventor1 = Actor::factory()->person()->create(['name' => 'First']);
        $inventor2 = Actor::factory()->person()->create(['name' => 'Second']);

        \DB::table('matter_actor_lnk')->insert([
            'matter_id' => $matter->id,
            'actor_id' => $inventor2->id,
            'role' => 'INV',
            'shared' => 0,
            'display_order' => 2,
        ]);

        \DB::table('matter_actor_lnk')->insert([
            'matter_id' => $matter->id,
            'actor_id' => $inventor1->id,
            'role' => 'INV',
            'shared' => 0,
            'display_order' => 1,
        ]);

        $actors = $matter->getActorsFromRole('INV');

        $this->assertEquals($inventor1->id, $actors->first()->actor_id);
        $this->assertEquals($inventor2->id, $actors->last()->actor_id);
    }

    /** @test */
    public function it_returns_first_actor_for_get_actor_from_role()
    {
        $matter = Matter::factory()->create();
        $actor1 = Actor::factory()->create();
        $actor2 = Actor::factory()->create();

        \DB::table('matter_actor_lnk')->insert([
            'matter_id' => $matter->id,
            'actor_id' => $actor1->id,
            'role' => 'AGT',
            'shared' => 0,
            'display_order' => 1,
        ]);

        \DB::table('matter_actor_lnk')->insert([
            'matter_id' => $matter->id,
            'actor_id' => $actor2->id,
            'role' => 'AGT',
            'shared' => 0,
            'display_order' => 2,
        ]);

        $result = $matter->getActorFromRole('AGT');

        $this->assertEquals($actor1->id, $result->actor_id);
    }

    /** @test */
    public function client_from_lnk_returns_client()
    {
        $matter = Matter::factory()->create();
        $client = Actor::factory()->asClient()->create();

        \DB::table('matter_actor_lnk')->insert([
            'matter_id' => $matter->id,
            'actor_id' => $client->id,
            'role' => 'CLI',
            'shared' => 0,
            'display_order' => 1,
        ]);

        $result = $matter->clientFromLnk();

        $this->assertNotNull($result);
        $this->assertEquals($client->id, $result->actor_id);
    }

    /** @test */
    public function agent_returns_primary_agent()
    {
        $matter = Matter::factory()->create();
        $agent = Actor::factory()->asAgent()->create();

        \DB::table('matter_actor_lnk')->insert([
            'matter_id' => $matter->id,
            'actor_id' => $agent->id,
            'role' => 'AGT',
            'shared' => 0,
            'display_order' => 1,
        ]);

        $result = $matter->agent();

        $this->assertNotNull($result);
        $this->assertEquals($agent->id, $result->actor_id);
    }

    /** @test */
    public function payor_returns_payor()
    {
        $matter = Matter::factory()->create();
        $payor = Actor::factory()->create();

        \DB::table('matter_actor_lnk')->insert([
            'matter_id' => $matter->id,
            'actor_id' => $payor->id,
            'role' => 'PAY',
            'shared' => 0,
            'display_order' => 1,
        ]);

        $result = $matter->payor();

        $this->assertNotNull($result);
        $this->assertEquals($payor->id, $result->actor_id);
    }

    /** @test */
    public function secondary_agent_returns_agt2_role()
    {
        $matter = Matter::factory()->create();
        $agent2 = Actor::factory()->create();

        \DB::table('matter_actor_lnk')->insert([
            'matter_id' => $matter->id,
            'actor_id' => $agent2->id,
            'role' => 'AGT2',
            'shared' => 0,
            'display_order' => 1,
        ]);

        $result = $matter->secondaryAgent();

        $this->assertNotNull($result);
        $this->assertEquals($agent2->id, $result->actor_id);
    }

    /** @test */
    public function annuity_agent_returns_ann_role()
    {
        $matter = Matter::factory()->create();
        $annAgent = Actor::factory()->create();

        \DB::table('matter_actor_lnk')->insert([
            'matter_id' => $matter->id,
            'actor_id' => $annAgent->id,
            'role' => 'ANN',
            'shared' => 0,
            'display_order' => 1,
        ]);

        $result = $matter->annuityAgent();

        $this->assertNotNull($result);
        $this->assertEquals($annAgent->id, $result->actor_id);
    }

    /** @test */
    public function writer_returns_wri_role()
    {
        $matter = Matter::factory()->create();
        $writer = Actor::factory()->create();

        \DB::table('matter_actor_lnk')->insert([
            'matter_id' => $matter->id,
            'actor_id' => $writer->id,
            'role' => 'WRI',  // WRI is the correct role code for Writer
            'shared' => 0,
            'display_order' => 1,
        ]);

        // Use getActorFromRole since writer() method doesn't exist
        $result = $matter->getActorFromRole('WRI');

        $this->assertNotNull($result);
        $this->assertEquals($writer->id, $result->actor_id);
    }

    /** @test */
    public function owners_returns_own_role_actors()
    {
        $matter = Matter::factory()->create();
        $owner1 = Actor::factory()->company()->create();
        $owner2 = Actor::factory()->company()->create();

        \DB::table('matter_actor_lnk')->insert([
            'matter_id' => $matter->id,
            'actor_id' => $owner1->id,
            'role' => 'OWN',
            'shared' => 0,
            'display_order' => 1,
        ]);

        \DB::table('matter_actor_lnk')->insert([
            'matter_id' => $matter->id,
            'actor_id' => $owner2->id,
            'role' => 'OWN',
            'shared' => 0,
            'display_order' => 2,
        ]);

        $owners = $matter->owners();

        $this->assertCount(2, $owners);
    }

    /** @test */
    public function applicants_from_lnk_returns_app_role_actors()
    {
        $matter = Matter::factory()->create();
        $applicant = Actor::factory()->company()->create();

        \DB::table('matter_actor_lnk')->insert([
            'matter_id' => $matter->id,
            'actor_id' => $applicant->id,
            'role' => 'APP',
            'shared' => 0,
            'display_order' => 1,
        ]);

        $applicants = $matter->applicantsFromLnk();

        $this->assertCount(1, $applicants);
    }
}

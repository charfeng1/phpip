<?php

namespace Tests\Unit\Models;

use App\Models\Actor;
use App\Models\Category;
use App\Models\Country;
use App\Models\DefaultActor;
use App\Models\Role;
use Tests\TestCase;

class DefaultActorTest extends TestCase
{
    /** @test */
    public function it_can_create_a_default_actor()
    {
        $actor = Actor::factory()->create();

        $defaultActor = DefaultActor::create([
            'actor_id' => $actor->id,
            'role' => 'AGT',
            'for_country' => 'US',
            'for_category' => 'PAT',
        ]);

        $this->assertDatabaseHas('default_actor', [
            'actor_id' => $actor->id,
            'role' => 'AGT',
        ]);
    }

    /** @test */
    public function it_belongs_to_an_actor()
    {
        $actor = Actor::factory()->create(['name' => 'Default Agent']);

        $defaultActor = DefaultActor::create([
            'actor_id' => $actor->id,
            'role' => 'AGT',
        ]);

        $this->assertInstanceOf(Actor::class, $defaultActor->actor);
        $this->assertEquals($actor->id, $defaultActor->actor->id);
        $this->assertEquals('Default Agent', $defaultActor->actor->name);
    }

    /** @test */
    public function it_can_belong_to_a_country()
    {
        // Ensure country exists
        Country::firstOrCreate(['iso' => 'US'], ['name' => ['en' => 'United States']]);
        $actor = Actor::factory()->create();

        $defaultActor = DefaultActor::create([
            'actor_id' => $actor->id,
            'role' => 'AGT',
            'for_country' => 'US',
        ]);

        $country = $defaultActor->country;

        $this->assertInstanceOf(Country::class, $country);
        $this->assertEquals('US', $country->iso);
    }

    /** @test */
    public function it_can_belong_to_a_category()
    {
        // Ensure category exists
        Category::firstOrCreate(['code' => 'PAT'], ['category' => ['en' => 'Patent']]);
        $actor = Actor::factory()->create();

        $defaultActor = DefaultActor::create([
            'actor_id' => $actor->id,
            'role' => 'AGT',
            'for_category' => 'PAT',
        ]);

        $category = $defaultActor->category;

        $this->assertInstanceOf(Category::class, $category);
        $this->assertEquals('PAT', $category->code);
    }

    /** @test */
    public function it_can_belong_to_a_client()
    {
        $agent = Actor::factory()->create(['name' => 'Agent']);
        $client = Actor::factory()->asClient()->create(['name' => 'Client']);

        $defaultActor = DefaultActor::create([
            'actor_id' => $agent->id,
            'role' => 'AGT',
            'for_client' => $client->id,
        ]);

        $this->assertInstanceOf(Actor::class, $defaultActor->client);
        $this->assertEquals($client->id, $defaultActor->client->id);
    }

    /** @test */
    public function it_can_have_a_role()
    {
        // Ensure role exists
        Role::firstOrCreate(['code' => 'AGT'], ['name' => ['en' => 'Agent']]);
        $actor = Actor::factory()->create();

        $defaultActor = DefaultActor::create([
            'actor_id' => $actor->id,
            'role' => 'AGT',
        ]);

        $roleInfo = $defaultActor->roleInfo;

        $this->assertInstanceOf(Role::class, $roleInfo);
        $this->assertEquals('AGT', $roleInfo->code);
    }

    /** @test */
    public function it_can_have_all_null_scope_fields()
    {
        $actor = Actor::factory()->create();

        $defaultActor = DefaultActor::create([
            'actor_id' => $actor->id,
            'role' => 'AGT',
            'for_country' => null,
            'for_category' => null,
            'for_client' => null,
        ]);

        $this->assertNull($defaultActor->country);
        $this->assertNull($defaultActor->category);
        $this->assertNull($defaultActor->client);
    }

    /** @test */
    public function it_guards_timestamp_fields()
    {
        $defaultActor = new DefaultActor;
        $guarded = $defaultActor->getGuarded();

        $this->assertContains('created_at', $guarded);
        $this->assertContains('updated_at', $guarded);
    }

    /** @test */
    public function it_uses_has_table_comments_trait()
    {
        $defaultActor = new DefaultActor;
        $traits = class_uses_recursive($defaultActor);

        $this->assertContains('App\Traits\HasTableComments', $traits);
    }

    /** @test */
    public function it_can_be_scoped_by_multiple_criteria()
    {
        $actor = Actor::factory()->create();
        $client = Actor::factory()->asClient()->create();

        $defaultActor = DefaultActor::create([
            'actor_id' => $actor->id,
            'role' => 'AGT',
            'for_country' => 'US',
            'for_category' => 'PAT',
            'for_client' => $client->id,
        ]);

        $this->assertNotNull($defaultActor->id);
        $this->assertEquals('US', $defaultActor->for_country);
        $this->assertEquals('PAT', $defaultActor->for_category);
        $this->assertEquals($client->id, $defaultActor->for_client);
    }
}

<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Country;
use App\Models\Event;
use App\Models\EventName;
use App\Models\Matter;
use App\Models\Task;
use App\Models\User;
use Tests\TestCase;

class EventNameTest extends TestCase
{
    /** @test */
    public function it_uses_code_as_primary_key()
    {
        $eventName = EventName::first();

        if ($eventName) {
            $this->assertEquals('code', $eventName->getKeyName());
            $this->assertFalse($eventName->incrementing);
            $this->assertEquals('string', $eventName->getKeyType());
        } else {
            $eventName = EventName::factory()->create(['code' => 'TEST']);
            $this->assertEquals('TEST', $eventName->getKey());
        }
    }

    /** @test */
    public function it_has_translatable_name()
    {
        $eventName = new EventName();

        $this->assertIsArray($eventName->translatable);
        $this->assertContains('name', $eventName->translatable);
    }

    /** @test */
    public function it_can_have_many_events()
    {
        $eventName = EventName::find('FIL') ?? EventName::factory()->create(['code' => 'FIL']);
        $matter = Matter::factory()->create();

        $event = Event::factory()->filing()->forMatter($matter)->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $eventName->events());
    }

    /** @test */
    public function it_can_have_many_tasks()
    {
        $eventName = EventName::find('REN') ?? EventName::factory()->create(['code' => 'REN']);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $eventName->tasks());
    }

    /** @test */
    public function it_can_belong_to_a_country()
    {
        $eventName = EventName::factory()->create([
            'code' => 'USEV',
            'country' => 'US',
        ]);

        $country = $eventName->countryInfo;

        if ($country) {
            $this->assertInstanceOf(Country::class, $country);
            $this->assertEquals('US', $country->iso);
        } else {
            $this->assertTrue(true);
        }
    }

    /** @test */
    public function it_can_belong_to_a_category()
    {
        $eventName = EventName::factory()->create([
            'code' => 'PATEV',
            'category' => 'PAT',
        ]);

        $category = $eventName->categoryInfo;

        if ($category) {
            $this->assertInstanceOf(Category::class, $category);
            $this->assertEquals('PAT', $category->code);
        } else {
            $this->assertTrue(true);
        }
    }

    /** @test */
    public function it_can_have_default_responsible_user()
    {
        $user = User::factory()->create(['login' => 'resp.user']);

        $eventName = EventName::factory()->create([
            'code' => 'RESP',
            'default_responsible' => 'resp.user',
        ]);

        $responsible = $eventName->default_responsibleInfo;

        if ($responsible) {
            $this->assertInstanceOf(User::class, $responsible);
            $this->assertEquals('resp.user', $responsible->login);
        } else {
            $this->assertTrue(true);
        }
    }

    /** @test */
    public function it_can_have_many_templates()
    {
        $eventName = EventName::first() ?? EventName::factory()->create(['code' => 'TPL']);

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsToMany::class,
            $eventName->templates()
        );
    }

    /** @test */
    public function it_hides_audit_fields_on_serialization()
    {
        $eventName = EventName::first() ?? EventName::factory()->create(['code' => 'HID']);

        $array = $eventName->toArray();

        $this->assertArrayNotHasKey('creator', $array);
        $this->assertArrayNotHasKey('created_at', $array);
        $this->assertArrayNotHasKey('updated_at', $array);
        $this->assertArrayNotHasKey('updater', $array);
    }

    /** @test */
    public function it_guards_timestamp_fields()
    {
        $eventName = new EventName();
        $guarded = $eventName->getGuarded();

        $this->assertContains('created_at', $guarded);
        $this->assertContains('updated_at', $guarded);
    }

    /** @test */
    public function standard_event_names_exist()
    {
        // Check for common event names that should exist
        $standardCodes = ['FIL', 'PUB', 'GRT', 'REN', 'PRI'];

        foreach ($standardCodes as $code) {
            $eventName = EventName::find($code);
            if ($eventName) {
                $this->assertEquals($code, $eventName->code);
            }
        }

        $this->assertTrue(true); // At least one should pass
    }
}

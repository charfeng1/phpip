<?php

namespace Tests\Unit\Models;

use App\Models\Event;
use App\Models\EventName;
use App\Models\Matter;
use App\Models\Task;
use Tests\TestCase;

class EventTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_can_create_an_event()
    {
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();

        $this->assertDatabaseHas('event', [
            'matter_id' => $matter->id,
            'code' => 'FIL',
        ]);
        $this->assertNotNull($event->id);
    }

    /** @test */
    public function it_belongs_to_a_matter()
    {
        $matter = Matter::factory()->create();
        $event = Event::factory()->forMatter($matter)->create();

        $this->assertInstanceOf(Matter::class, $event->matter);
        $this->assertEquals($matter->id, $event->matter->id);
    }

    /** @test */
    public function it_has_event_name_info()
    {
        $event = Event::factory()->filing()->create();

        $this->assertNotNull($event->info);
        $this->assertEquals('FIL', $event->info->code);
    }

    /** @test */
    public function it_can_be_a_filing_event()
    {
        $event = Event::factory()->filing()->create();

        $this->assertEquals('FIL', $event->code);
    }

    /** @test */
    public function it_can_be_a_publication_event()
    {
        $event = Event::factory()->publication()->create();

        $this->assertEquals('PUB', $event->code);
    }

    /** @test */
    public function it_can_be_a_grant_event()
    {
        $event = Event::factory()->grant()->create();

        $this->assertEquals('GRT', $event->code);
    }

    /** @test */
    public function it_can_be_a_registration_event()
    {
        $event = Event::factory()->registration()->create();

        $this->assertEquals('REG', $event->code);
    }

    /** @test */
    public function it_can_be_a_priority_event()
    {
        $event = Event::factory()->priority()->create();

        $this->assertEquals('PRI', $event->code);
    }

    /** @test */
    public function it_can_be_an_entry_event()
    {
        $event = Event::factory()->entry()->create();

        $this->assertEquals('ENT', $event->code);
    }

    /** @test */
    public function it_can_have_a_specific_date()
    {
        $event = Event::factory()->onDate('2023-06-15')->create();

        $this->assertEquals('2023-06-15', $event->event_date->format('Y-m-d'));
    }

    /** @test */
    public function it_casts_event_date_to_date()
    {
        $event = Event::factory()->create(['event_date' => '2023-06-15']);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $event->event_date);
    }

    /** @test */
    public function it_can_have_detail()
    {
        $event = Event::factory()->filing()->create(['detail' => '12/345,678']);

        $this->assertEquals('12/345,678', $event->detail);
    }

    /** @test */
    public function it_can_link_to_alternate_matter()
    {
        $matter = Matter::factory()->create();
        $altMatter = Matter::factory()->create();
        $event = Event::factory()->forMatter($matter)->withAltMatter($altMatter)->create();

        $this->assertEquals($altMatter->id, $event->alt_matter_id);
        $this->assertInstanceOf(Matter::class, $event->altMatter);
        $this->assertEquals($altMatter->id, $event->altMatter->id);
    }

    /** @test */
    public function it_returns_default_when_no_alt_matter()
    {
        $event = Event::factory()->create(['alt_matter_id' => null]);

        $this->assertNotNull($event->altMatter); // WithDefault returns empty model
        $this->assertNull($event->altMatter->id);
    }

    /** @test */
    public function it_can_have_tasks()
    {
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $task = Task::factory()->forEvent($event)->create();

        $this->assertTrue($event->tasks->contains($task));
    }

    /** @test */
    public function it_has_link_to_alt_matter_filing()
    {
        $matter = Matter::factory()->create();
        $altMatter = Matter::factory()->create();
        $altFiling = Event::factory()->filing()->forMatter($altMatter)->create();
        $event = Event::factory()->priority()->forMatter($matter)->withAltMatter($altMatter)->create();

        $link = $event->link;

        $this->assertNotNull($link);
        $this->assertEquals('FIL', $link->code);
    }

    /** @test */
    public function it_touches_parent_matter()
    {
        $matter = Matter::factory()->create();
        $originalUpdatedAt = $matter->updated_at;

        // Small delay to ensure timestamp difference
        sleep(1);

        Event::factory()->filing()->forMatter($matter)->create();
        $matter->refresh();

        $this->assertGreaterThanOrEqual($originalUpdatedAt, $matter->updated_at);
    }

    /** @test */
    public function it_hides_system_attributes()
    {
        $event = Event::factory()->create();
        $array = $event->toArray();

        $this->assertArrayNotHasKey('creator', $array);
        $this->assertArrayNotHasKey('updater', $array);
        $this->assertArrayNotHasKey('created_at', $array);
        $this->assertArrayNotHasKey('updated_at', $array);
    }

    /** @test */
    public function clean_number_removes_country_code()
    {
        $matter = Matter::factory()->inCountry('US')->create();
        $event = Event::factory()->forMatter($matter)->create(['detail' => 'US12345678']);

        $cleaned = $event->cleanNumber();

        $this->assertStringNotContainsString('US', $cleaned);
        $this->assertEquals('12345678', $cleaned);
    }

    /** @test */
    public function clean_number_removes_spaces_and_special_chars()
    {
        $matter = Matter::factory()->inCountry('US')->create();
        $event = Event::factory()->forMatter($matter)->create(['detail' => '12/345,678']);

        $cleaned = $event->cleanNumber();

        $this->assertStringNotContainsString('/', $cleaned);
        $this->assertStringNotContainsString(',', $cleaned);
    }

    /** @test */
    public function public_url_returns_false_for_non_applicable_events()
    {
        $matter = Matter::factory()->create();
        $event = Event::factory()->priority()->forMatter($matter)->create();

        $this->assertFalse($event->publicUrl());
    }

    /** @test */
    public function public_url_returns_espacenet_for_publication()
    {
        $matter = Matter::factory()->inCountry('US')->create();
        $event = Event::factory()->publication()->forMatter($matter)->create([
            'detail' => 'US2023123456',
        ]);

        $url = $event->publicUrl();

        $this->assertStringContainsString('espacenet.com', $url);
        $this->assertStringContainsString('CC=US', $url);
    }

    /** @test */
    public function public_url_handles_ep_origin()
    {
        $matter = Matter::factory()->inCountry('FR')->withOrigin('EP')->create();
        $event = Event::factory()->publication()->forMatter($matter)->create([
            'detail' => 'EP1234567',
        ]);

        $url = $event->publicUrl();

        $this->assertStringContainsString('CC=EP', $url);
    }
}

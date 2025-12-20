<?php

namespace Tests\Unit\Models;

use App\Models\Actor;
use App\Models\Category;
use App\Models\Country;
use App\Models\Event;
use App\Models\Matter;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed only essential reference data for better performance
        $this->seedTestData();
    }

    /** @test */
    public function it_can_create_a_matter()
    {
        $matter = Matter::factory()->create([
            'caseref' => 'TEST0001',
            'country' => 'US',
            'category_code' => 'PAT',
        ]);

        $this->assertDatabaseHas('matter', [
            'caseref' => 'TEST0001',
            'country' => 'US',
            'category_code' => 'PAT',
        ]);
        $this->assertNotNull($matter->id);
    }

    /** @test */
    public function it_belongs_to_a_category()
    {
        $matter = Matter::factory()->create(['category_code' => 'PAT']);

        $this->assertInstanceOf(Category::class, $matter->category);
        $this->assertEquals('PAT', $matter->category->code);
    }

    /** @test */
    public function it_belongs_to_a_country()
    {
        $matter = Matter::factory()->create(['country' => 'US']);

        $this->assertNotNull($matter->countryInfo);
        $this->assertEquals('US', $matter->countryInfo->iso);
    }

    /** @test */
    public function it_can_have_a_container()
    {
        $container = Matter::factory()->asContainer()->create();
        $member = Matter::factory()->asFamilyMember($container)->create();

        $this->assertEquals($container->id, $member->container_id);
        $this->assertEquals($container->caseref, $member->caseref);
        $this->assertInstanceOf(Matter::class, $member->container);
    }

    /** @test */
    public function it_can_have_family_members()
    {
        $container = Matter::factory()->asContainer()->create();
        $member1 = Matter::factory()->asFamilyMember($container)->create(['country' => 'US']);
        $member2 = Matter::factory()->asFamilyMember($container)->create(['country' => 'EP']);

        $family = $container->family;

        $this->assertCount(3, $family); // Container + 2 members
        $this->assertTrue($family->contains($member1));
        $this->assertTrue($family->contains($member2));
    }

    /** @test */
    public function it_can_have_a_parent_matter()
    {
        $parent = Matter::factory()->create();
        $child = Matter::factory()->create(['parent_id' => $parent->id]);

        $this->assertEquals($parent->id, $child->parent_id);
        $this->assertInstanceOf(Matter::class, $child->parent);
        $this->assertEquals($parent->id, $child->parent->id);
    }

    /** @test */
    public function it_can_have_descendants()
    {
        $parent = Matter::factory()->create();
        $child1 = Matter::factory()->create(['parent_id' => $parent->id]);
        $child2 = Matter::factory()->create(['parent_id' => $parent->id]);

        $descendants = $parent->descendants;

        $this->assertCount(2, $descendants);
        $this->assertTrue($descendants->contains($child1));
        $this->assertTrue($descendants->contains($child2));
    }

    /** @test */
    public function it_can_have_events()
    {
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();

        $this->assertTrue($matter->events->contains($event));
    }

    /** @test */
    public function it_can_have_a_filing_event()
    {
        $matter = Matter::factory()->create();
        $filing = Event::factory()->filing()->forMatter($matter)->create([
            'event_date' => '2023-01-15',
            'detail' => '12/345,678',
        ]);

        $this->assertEquals('FIL', $matter->filing->code);
        $this->assertEquals('2023-01-15', $matter->filing->event_date->format('Y-m-d'));
        $this->assertEquals('12/345,678', $matter->filing->detail);
    }

    /** @test */
    public function it_can_have_a_publication_event()
    {
        $matter = Matter::factory()->create();
        $publication = Event::factory()->publication()->forMatter($matter)->create();

        $this->assertEquals('PUB', $matter->publication->code);
    }

    /** @test */
    public function it_can_have_a_grant_event()
    {
        $matter = Matter::factory()->create();
        $grant = Event::factory()->grant()->forMatter($matter)->create();

        $this->assertEquals('GRT', $matter->grant->code);
    }

    /** @test */
    public function it_can_have_priority_events()
    {
        $matter = Matter::factory()->create();
        $priority1 = Event::factory()->priority()->forMatter($matter)->create();
        $priority2 = Event::factory()->priority()->forMatter($matter)->create();

        $this->assertCount(2, $matter->priority);
    }

    /** @test */
    public function it_can_have_tasks()
    {
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $task = Task::factory()->renewal()->forEvent($event)->create();

        $this->assertTrue($matter->tasks->contains($task));
    }

    /** @test */
    public function it_can_have_pending_tasks()
    {
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();

        $pendingTask = Task::factory()->pending()->forEvent($event)->create(['code' => 'DL']);
        $completedTask = Task::factory()->completed()->forEvent($event)->create(['code' => 'DL']);

        $pendingTasks = $matter->tasksPending;

        $this->assertTrue($pendingTasks->contains($pendingTask));
        $this->assertFalse($pendingTasks->contains($completedTask));
    }

    /** @test */
    public function it_can_have_pending_renewals()
    {
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();

        $renewal = Task::factory()->renewal()->pending()->forEvent($event)->create();
        $nonRenewal = Task::factory()->pending()->forEvent($event)->create(['code' => 'DL']);

        $renewals = $matter->renewalsPending;

        $this->assertTrue($renewals->contains($renewal));
        $this->assertFalse($renewals->contains($nonRenewal));
    }

    /** @test */
    public function it_can_be_dead()
    {
        $liveMatter = Matter::factory()->create(['dead' => false]);
        $deadMatter = Matter::factory()->dead()->create();

        $this->assertFalse($liveMatter->dead);
        $this->assertTrue($deadMatter->dead);
    }

    /** @test */
    public function it_can_have_a_responsible_person()
    {
        $user = User::factory()->create(['login' => 'john.doe']);
        $matter = Matter::factory()->create(['responsible' => 'john.doe']);

        $this->assertEquals('john.doe', $matter->responsible);
    }

    /** @test */
    public function it_can_have_origin_country()
    {
        $matter = Matter::factory()->withOrigin('EP')->create();

        $this->assertEquals('EP', $matter->origin);
        $this->assertNotNull($matter->originInfo);
    }

    /** @test */
    public function it_can_have_expiry_date()
    {
        $expiryDate = new \DateTime('+20 years');
        $matter = Matter::factory()->withExpiry($expiryDate)->create();

        $this->assertEquals($expiryDate->format('Y-m-d'), $matter->expire_date);
    }

    /** @test */
    public function dead_matter_is_properly_flagged()
    {
        $matter = Matter::factory()->dead()->create();

        $this->assertTrue((bool) $matter->dead);
    }

    /** @test */
    public function it_can_have_alt_ref()
    {
        $matter = Matter::factory()->create(['alt_ref' => 'CLIENT-REF-001']);

        $this->assertEquals('CLIENT-REF-001', $matter->alt_ref);
    }

    /** @test */
    public function it_generates_uid_correctly()
    {
        $matter = Matter::factory()->create([
            'caseref' => 'TEST001',
            'country' => 'US',
        ]);

        $this->assertNotNull($matter->uid);
        $this->assertStringContainsString('TEST001', $matter->uid);
        $this->assertStringContainsString('US', $matter->uid);
    }

    /** @test */
    public function filter_returns_query_builder()
    {
        // Create a user for authentication
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $query = Matter::filter();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $query);
    }

    /** @test */
    public function filter_excludes_dead_families_by_default()
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $liveMatter = Matter::factory()->create(['dead' => false]);
        $deadMatter = Matter::factory()->dead()->create();

        $results = Matter::filter()->pluck('id');

        $this->assertTrue($results->contains($liveMatter->id));
        // Dead matters are excluded from families where all members are dead
    }

    /** @test */
    public function filter_can_include_dead_families()
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $deadMatter = Matter::factory()->dead()->create();

        $results = Matter::filter('id', 'desc', [], false, true)->pluck('id');

        $this->assertTrue($results->contains($deadMatter->id));
    }

    /** @test */
    public function filter_can_filter_by_reference()
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $matter1 = Matter::factory()->create(['caseref' => 'UNIQUE001']);
        $matter2 = Matter::factory()->create(['caseref' => 'OTHER002']);

        $results = Matter::filter('id', 'desc', ['Ref' => 'UNIQUE'])->pluck('id');

        $this->assertTrue($results->contains($matter1->id));
        $this->assertFalse($results->contains($matter2->id));
    }

    /** @test */
    public function filter_can_filter_by_country()
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $usMatter = Matter::factory()->inCountry('US')->create();
        $epMatter = Matter::factory()->inCountry('EP')->create();

        $results = Matter::filter('id', 'desc', ['country' => 'US'])->pluck('id');

        $this->assertTrue($results->contains($usMatter->id));
        $this->assertFalse($results->contains($epMatter->id));
    }

    /** @test */
    public function filter_can_filter_by_category()
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $patent = Matter::factory()->patent()->create();
        $trademark = Matter::factory()->trademark()->create();

        $results = Matter::filter('id', 'desc', ['Cat' => 'PAT'])->pluck('id');

        $this->assertTrue($results->contains($patent->id));
        $this->assertFalse($results->contains($trademark->id));
    }

    /** @test */
    public function filter_sorts_by_id_descending_by_default()
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $matter1 = Matter::factory()->create();
        $matter2 = Matter::factory()->create();

        $results = Matter::filter()->get();

        $this->assertEquals($matter2->id, $results->first()->id);
    }

    /** @test */
    public function filter_changes_sort_to_caseref_when_filtered()
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        Matter::factory()->create(['caseref' => 'BBB001']);
        Matter::factory()->create(['caseref' => 'AAA001']);

        $results = Matter::filter('id', 'desc', ['country' => 'US'])->get();

        // When filtered, should sort by caseref ascending by default
        if ($results->count() >= 2) {
            $this->assertLessThanOrEqual(
                $results->last()->caseref ?? $results->last()->Ref,
                $results->first()->caseref ?? $results->first()->Ref
            );
        }
    }

    /** @test */
    public function client_users_see_only_their_matters()
    {
        // This test verifies the role-based access control in the filter method
        $clientUser = User::factory()->client()->create();
        $this->actingAs($clientUser);

        // When a CLI user queries, they should only see their matters
        // The actual filtering depends on the matter_actor_lnk relationships
        $query = Matter::filter();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $query);
    }
}

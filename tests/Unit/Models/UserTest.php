<?php

namespace Tests\Unit\Models;

use App\Models\Actor;
use App\Models\Event;
use App\Models\Matter;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_can_create_a_user()
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        $this->assertNotNull($user->id);
    }

    /** @test */
    public function it_can_be_an_admin()
    {
        $user = User::factory()->admin()->create();

        $this->assertEquals('DBA', $user->default_role);
    }

    /** @test */
    public function it_can_have_read_write_role()
    {
        $user = User::factory()->readWrite()->create();

        $this->assertEquals('DBRW', $user->default_role);
    }

    /** @test */
    public function it_can_have_read_only_role()
    {
        $user = User::factory()->readOnly()->create();

        $this->assertEquals('DBRO', $user->default_role);
    }

    /** @test */
    public function it_can_be_a_client()
    {
        $user = User::factory()->client()->create();

        $this->assertEquals('CLI', $user->default_role);
    }

    /** @test */
    public function it_can_be_unverified()
    {
        $user = User::factory()->unverified()->create();

        $this->assertNull($user->email_verified_at);
    }

    /** @test */
    public function it_can_have_specific_language()
    {
        $user = User::factory()->withLanguage('fr')->create();

        $this->assertEquals('fr', $user->language);
    }

    /** @test */
    public function it_has_login()
    {
        $user = User::factory()->create(['login' => 'jdoe']);

        $this->assertEquals('jdoe', $user->login);
    }

    /** @test */
    public function it_hashes_password()
    {
        $user = User::factory()->create(['password' => 'plaintext']);

        // The password should be hashed, not stored as plaintext
        $this->assertNotEquals('plaintext', $user->password);
    }

    /** @test */
    public function it_hides_sensitive_attributes()
    {
        $user = User::factory()->create();
        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
    }

    /** @test */
    public function it_casts_email_verified_at()
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $user->email_verified_at);
    }

    /** @test */
    public function it_can_belong_to_a_company()
    {
        $company = Actor::factory()->company()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Actor::class, $user->company);
        $this->assertEquals($company->id, $user->company->id);
    }

    /** @test */
    public function it_can_have_a_parent_user()
    {
        $parent = Actor::factory()->create();
        $user = User::factory()->create(['parent_id' => $parent->id]);

        $this->assertInstanceOf(Actor::class, $user->parent);
        $this->assertEquals($parent->id, $user->parent->id);
    }

    /** @test */
    public function it_can_have_matters()
    {
        $user = User::factory()->create(['login' => 'responsible.user']);
        $matter = Matter::factory()->create(['responsible' => 'responsible.user']);

        $this->assertTrue($user->matters->contains($matter));
    }

    /** @test */
    public function it_can_get_matters_with_pending_tasks()
    {
        $user = User::factory()->create(['login' => 'task.user']);
        $matter = Matter::factory()->create(['responsible' => 'task.user']);
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $task = Task::factory()->pending()->forEvent($event)->create(['code' => 'DL']);

        $mattersWithTasks = $user->tasks;

        $this->assertTrue($mattersWithTasks->contains($matter));
    }

    /** @test */
    public function it_can_get_matters_with_pending_renewals()
    {
        $user = User::factory()->create(['login' => 'renewal.user']);
        $matter = Matter::factory()->create(['responsible' => 'renewal.user']);
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $renewal = Task::factory()->renewal()->pending()->forEvent($event)->create();

        $mattersWithRenewals = $user->renewals;

        $this->assertTrue($mattersWithRenewals->contains($matter));
    }

    /** @test */
    public function matters_without_pending_tasks_not_in_tasks()
    {
        $user = User::factory()->create(['login' => 'no.task.user']);
        $matter = Matter::factory()->create(['responsible' => 'no.task.user']);
        // Matter with no tasks

        $mattersWithTasks = $user->tasks;

        $this->assertFalse($mattersWithTasks->contains($matter));
    }

    /** @test */
    public function matters_with_only_completed_tasks_not_in_tasks()
    {
        $user = User::factory()->create(['login' => 'complete.user']);
        $matter = Matter::factory()->create(['responsible' => 'complete.user']);
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $task = Task::factory()->completed()->forEvent($event)->create(['code' => 'DL']);

        $mattersWithTasks = $user->tasks;

        $this->assertFalse($mattersWithTasks->contains($matter));
    }

    /** @test */
    public function it_uses_notifiable_trait()
    {
        $user = User::factory()->create();

        $this->assertContains(\Illuminate\Notifications\Notifiable::class, class_uses_recursive($user));
    }
}

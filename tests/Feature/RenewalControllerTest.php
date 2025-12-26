<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Country;
use App\Models\Event;
use App\Models\Matter;
use App\Models\Task;
use App\Models\User;
use Tests\TestCase;

class RenewalControllerTest extends TestCase
{
    protected User $adminUser;
    protected User $readWriteUser;
    protected User $readOnlyUser;
    protected User $clientUser;
    protected Country $country;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users deterministically using factories
        $this->adminUser = User::factory()->admin()->create();
        $this->readWriteUser = User::factory()->readWrite()->create();
        $this->readOnlyUser = User::factory()->readOnly()->create();
        $this->clientUser = User::factory()->client()->create();

        // Create required reference data
        $this->country = Country::factory()->create();
        $this->category = Category::factory()->create();
    }

    /**
     * Helper to create a renewal task for testing
     */
    protected function createRenewalTask(array $attributes = []): Task
    {
        $matter = Matter::factory()->create([
            'category_code' => $this->category->code,
            'country' => $this->country->iso,
        ]);

        $event = Event::factory()->filing()->forMatter($matter)->create();

        return Task::factory()->renewal()->forEvent($event)->create($attributes);
    }

    /** @test */
    public function guest_cannot_access_renewal_index()
    {
        $response = $this->get(route('renewal.index'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function client_cannot_access_renewal_index()
    {
        $response = $this->actingAs($this->clientUser)->get(route('renewal.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_access_renewal_index()
    {
        $response = $this->actingAs($this->adminUser)->get(route('renewal.index'));

        $response->assertStatus(200)
            ->assertViewIs('renewals.index');
    }

    /** @test */
    public function read_write_user_can_access_renewal_index()
    {
        $response = $this->actingAs($this->readWriteUser)->get(route('renewal.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function read_only_user_can_access_renewal_index()
    {
        $response = $this->actingAs($this->readOnlyUser)->get(route('renewal.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function renewal_index_returns_json_when_requested()
    {
        $response = $this->actingAs($this->adminUser)->getJson(route('renewal.index'));

        $response->assertStatus(200)
            ->assertJsonIsArray();
    }

    /** @test */
    public function renewal_index_can_be_filtered_by_step()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('renewal.index', ['step' => 0]));

        $response->assertStatus(200);
    }

    /** @test */
    public function renewal_index_can_be_filtered_by_invoice_step()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('renewal.index', ['invoice_step' => 1]));

        $response->assertStatus(200);
    }

    /** @test */
    public function read_only_user_cannot_call_firstcall()
    {
        $task = $this->createRenewalTask();

        $response = $this->actingAs($this->readOnlyUser)
            ->postJson('/renewal/call/0', ['task_ids' => [$task->id]]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_process_firstcall_with_valid_task()
    {
        $task = $this->createRenewalTask(['step' => null]);

        $response = $this->actingAs($this->adminUser)
            ->postJson('/renewal/call/0', ['task_ids' => [$task->id]]);

        $response->assertStatus(200);

        // Verify database was updated
        $task->refresh();
        $this->assertNotNull($task->step);
    }

    /** @test */
    public function topay_returns_error_when_no_renewals_selected()
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/renewal/topay', ['task_ids' => []]);

        $response->assertStatus(200)
            ->assertJson(['error' => 'No renewal selected.']);
    }

    /** @test */
    public function read_only_user_cannot_call_topay()
    {
        $task = $this->createRenewalTask();

        $response = $this->actingAs($this->readOnlyUser)
            ->postJson('/renewal/topay', ['task_ids' => [$task->id]]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_process_topay_with_valid_task()
    {
        $task = $this->createRenewalTask(['step' => 2]);

        $response = $this->actingAs($this->adminUser)
            ->postJson('/renewal/topay', ['task_ids' => [$task->id]]);

        $response->assertStatus(200);
    }

    /** @test */
    public function paid_returns_error_when_no_renewals_selected()
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/renewal/paid', ['task_ids' => []]);

        $response->assertStatus(200)
            ->assertJson(['error' => 'No renewal selected.']);
    }

    /** @test */
    public function admin_can_process_paid_with_valid_task()
    {
        $task = $this->createRenewalTask(['step' => 4]);

        $response = $this->actingAs($this->adminUser)
            ->postJson('/renewal/paid', ['task_ids' => [$task->id]]);

        $response->assertStatus(200);
    }

    /** @test */
    public function done_returns_error_when_no_renewals_selected()
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/renewal/done', ['task_ids' => []]);

        $response->assertStatus(200)
            ->assertJson(['error' => 'No renewal selected.']);
    }

    /** @test */
    public function admin_can_process_done_with_valid_task()
    {
        $task = $this->createRenewalTask(['step' => 6]);

        $response = $this->actingAs($this->adminUser)
            ->postJson('/renewal/done', ['task_ids' => [$task->id]]);

        $response->assertStatus(200);

        // Verify task was marked as done
        $task->refresh();
        $this->assertTrue((bool) $task->done);
        $this->assertNotNull($task->done_date);
    }

    /** @test */
    public function receipt_returns_error_when_no_renewals_selected()
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/renewal/receipt', ['task_ids' => []]);

        $response->assertStatus(200)
            ->assertJson(['error' => 'No renewal selected.']);
    }

    /** @test */
    public function closing_returns_error_when_no_renewals_selected()
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/renewal/closing', ['task_ids' => []]);

        $response->assertStatus(200)
            ->assertJson(['error' => 'No renewal selected.']);
    }

    /** @test */
    public function abandon_returns_error_when_no_renewals_selected()
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/renewal/abandon', ['task_ids' => []]);

        $response->assertStatus(200)
            ->assertJson(['error' => 'No renewal selected.']);
    }

    /** @test */
    public function lapsing_returns_error_when_no_renewals_selected()
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/renewal/lapsing', ['task_ids' => []]);

        $response->assertStatus(200)
            ->assertJson(['error' => 'No renewal selected.']);
    }

    /** @test */
    public function admin_can_process_receipt_with_valid_task()
    {
        $task = $this->createRenewalTask(['step' => 6]);

        $response = $this->actingAs($this->adminUser)
            ->postJson('/renewal/receipt', ['task_ids' => [$task->id]]);

        $response->assertStatus(200)
            ->assertJson(['success' => '1 receipts registered']);

        $task->refresh();
        $this->assertEquals(8, $task->step);
    }

    /** @test */
    public function admin_can_process_closing_with_valid_task()
    {
        $task = $this->createRenewalTask(['step' => 8, 'done' => 0]);

        $response = $this->actingAs($this->adminUser)
            ->postJson('/renewal/closing', ['task_ids' => [$task->id]]);

        $response->assertStatus(200)
            ->assertJson(['success' => '1 closed']);

        $task->refresh();
        $this->assertEquals(10, $task->step);
    }

    /** @test */
    public function admin_can_process_abandon_with_valid_task()
    {
        $task = $this->createRenewalTask(['step' => 2]);

        $response = $this->actingAs($this->adminUser)
            ->postJson('/renewal/abandon', ['task_ids' => [$task->id]]);

        $response->assertStatus(200)
            ->assertJson(['success' => '1 abandons registered']);

        $task->refresh();
        $this->assertEquals(12, $task->step);

        $this->assertDatabaseHas('event', [
            'matter_id' => $task->trigger_id,
            'code' => 'ABA',
        ]);
    }

    /** @test */
    public function admin_can_process_lapsing_with_valid_task()
    {
        $task = $this->createRenewalTask(['step' => 2]);

        $response = $this->actingAs($this->adminUser)
            ->postJson('/renewal/lapsing', ['task_ids' => [$task->id]]);

        $response->assertStatus(200)
            ->assertJson(['success' => '1 communications registered']);

        $task->refresh();
        $this->assertEquals(14, $task->step);

        $this->assertDatabaseHas('event', [
            'matter_id' => $task->trigger_id,
            'code' => 'LAP',
        ]);
    }

    /** @test */
    public function admin_can_access_renewal_logs()
    {
        $response = $this->actingAs($this->adminUser)->get('/renewal/logs');

        $response->assertStatus(200)
            ->assertViewIs('renewals.logs');
    }

    /** @test */
    public function read_only_user_can_access_renewal_logs()
    {
        $response = $this->actingAs($this->readOnlyUser)->get('/renewal/logs');

        $response->assertStatus(200);
    }

    /** @test */
    public function client_cannot_access_renewal_logs()
    {
        $response = $this->actingAs($this->clientUser)->get('/renewal/logs');

        $response->assertStatus(403);
    }

    /** @test */
    public function renewal_logs_can_be_filtered_by_date()
    {
        $response = $this->actingAs($this->adminUser)
            ->get('/renewal/logs?Fromdate=2024-01-01&Untildate=2024-12-31');

        $response->assertStatus(200);
    }

    /** @test */
    public function renewal_logs_validates_date_format()
    {
        $response = $this->actingAs($this->adminUser)
            ->get('/renewal/logs?Fromdate=invalid-date');

        $response->assertStatus(302);
    }

    /** @test */
    public function admin_can_access_renewal_export()
    {
        $response = $this->actingAs($this->adminUser)->get('/renewal/export');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/csv');
    }

    /** @test */
    public function renewal_export_contains_csv_data()
    {
        // Create a renewal task to export
        $this->createRenewalTask();

        $response = $this->actingAs($this->adminUser)->get('/renewal/export');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/csv');

        // Verify CSV has content (headers at minimum)
        $content = $response->getContent();
        $this->assertNotEmpty($content);
        $this->assertStringContainsString(',', $content);
    }

    /** @test */
    public function read_only_user_can_access_renewal_export()
    {
        $response = $this->actingAs($this->readOnlyUser)->get('/renewal/export');

        $response->assertStatus(200);
    }

    /** @test */
    public function client_cannot_access_renewal_export()
    {
        $response = $this->actingAs($this->clientUser)->get('/renewal/export');

        $response->assertStatus(403);
    }

    /** @test */
    public function invoice_returns_error_when_no_renewals_selected()
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/renewal/invoice/0', ['task_ids' => []]);

        $response->assertStatus(200)
            ->assertJson(['error' => 'No renewal selected.']);
    }

    /** @test */
    public function read_only_user_cannot_call_invoice()
    {
        $task = $this->createRenewalTask();

        $response = $this->actingAs($this->readOnlyUser)
            ->postJson('/renewal/invoice/0', ['task_ids' => [$task->id]]);

        $response->assertStatus(403);
    }

    /** @test */
    public function read_write_user_can_process_renewal_workflow()
    {
        $task = $this->createRenewalTask(['step' => null]);

        // Step 1: First call
        $response = $this->actingAs($this->readWriteUser)
            ->postJson('/renewal/call/0', ['task_ids' => [$task->id]]);

        $response->assertStatus(200);

        // Verify step was updated
        $task->refresh();
        $this->assertNotNull($task->step);
    }
}

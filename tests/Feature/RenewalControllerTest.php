<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Country;
use App\Models\Event;
use App\Models\EventName;
use App\Models\Matter;
use App\Models\Task;
use App\Models\User;
use Tests\TestCase;

class RenewalControllerTest extends TestCase
{
    /** @test */
    public function guest_cannot_access_renewal_index()
    {
        $response = $this->get(route('renewal.index'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function client_cannot_access_renewal_index()
    {
        $user = User::factory()->client()->create();

        $response = $this->actingAs($user)->get(route('renewal.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_access_renewal_index()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('renewal.index'));

        $response->assertStatus(200);
        $response->assertViewIs('renewals.index');
    }

    /** @test */
    public function read_write_user_can_access_renewal_index()
    {
        $user = User::factory()->readWrite()->create();

        $response = $this->actingAs($user)->get(route('renewal.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function read_only_user_can_access_renewal_index()
    {
        $user = User::factory()->readOnly()->create();

        $response = $this->actingAs($user)->get(route('renewal.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function renewal_index_returns_json_when_requested()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->getJson(route('renewal.index'));

        $response->assertStatus(200);
        $response->assertJsonStructure([]);
    }

    /** @test */
    public function renewal_index_can_be_filtered_by_step()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('renewal.index', ['step' => 0]));

        $response->assertStatus(200);
    }

    /** @test */
    public function renewal_index_can_be_filtered_by_invoice_step()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('renewal.index', ['invoice_step' => 1]));

        $response->assertStatus(200);
    }

    /** @test */
    public function read_only_user_cannot_call_firstcall()
    {
        $user = User::factory()->readOnly()->create();

        $response = $this->actingAs($user)->postJson('/renewal/call/0', [
            'task_ids' => [1, 2, 3],
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_call_topay()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->postJson('/renewal/topay', [
            'task_ids' => [],
        ]);

        // Returns error when no renewals selected
        $response->assertStatus(200);
        $response->assertJson(['error' => 'No renewal selected.']);
    }

    /** @test */
    public function read_only_user_cannot_call_topay()
    {
        $user = User::factory()->readOnly()->create();

        $response = $this->actingAs($user)->postJson('/renewal/topay', [
            'task_ids' => [1],
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_call_paid()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->postJson('/renewal/paid', [
            'task_ids' => [],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['error' => 'No renewal selected.']);
    }

    /** @test */
    public function admin_can_call_done()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->postJson('/renewal/done', [
            'task_ids' => [],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['error' => 'No renewal selected.']);
    }

    /** @test */
    public function admin_can_call_receipt()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->postJson('/renewal/receipt', [
            'task_ids' => [],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['error' => 'No renewal selected.']);
    }

    /** @test */
    public function admin_can_call_closing()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->postJson('/renewal/closing', [
            'task_ids' => [],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['error' => 'No renewal selected.']);
    }

    /** @test */
    public function admin_can_call_abandon()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->postJson('/renewal/abandon', [
            'task_ids' => [],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['error' => 'No renewal selected.']);
    }

    /** @test */
    public function admin_can_call_lapsing()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->postJson('/renewal/lapsing', [
            'task_ids' => [],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['error' => 'No renewal selected.']);
    }

    /** @test */
    public function admin_can_access_renewal_logs()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get('/renewal/logs');

        $response->assertStatus(200);
        $response->assertViewIs('renewals.logs');
    }

    /** @test */
    public function read_only_user_can_access_renewal_logs()
    {
        $user = User::factory()->readOnly()->create();

        $response = $this->actingAs($user)->get('/renewal/logs');

        $response->assertStatus(200);
    }

    /** @test */
    public function client_cannot_access_renewal_logs()
    {
        $user = User::factory()->client()->create();

        $response = $this->actingAs($user)->get('/renewal/logs');

        $response->assertStatus(403);
    }

    /** @test */
    public function renewal_logs_can_be_filtered_by_date()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get('/renewal/logs', [
            'Fromdate' => '2024-01-01',
            'Untildate' => '2024-12-31',
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function renewal_logs_validates_date_format()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get('/renewal/logs?Fromdate=invalid-date');

        $response->assertStatus(302); // Validation redirect
    }

    /** @test */
    public function admin_can_access_renewal_export()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get('/renewal/export');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/csv');
    }

    /** @test */
    public function read_only_user_can_access_renewal_export()
    {
        $user = User::factory()->readOnly()->create();

        $response = $this->actingAs($user)->get('/renewal/export');

        $response->assertStatus(200);
    }

    /** @test */
    public function client_cannot_access_renewal_export()
    {
        $user = User::factory()->client()->create();

        $response = $this->actingAs($user)->get('/renewal/export');

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_call_invoice()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->postJson('/renewal/invoice/0', [
            'task_ids' => [],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['error' => 'No renewal selected.']);
    }

    /** @test */
    public function read_only_user_cannot_call_invoice()
    {
        $user = User::factory()->readOnly()->create();

        $response = $this->actingAs($user)->postJson('/renewal/invoice/0', [
            'task_ids' => [1],
        ]);

        $response->assertStatus(403);
    }
}

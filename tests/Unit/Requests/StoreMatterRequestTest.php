<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreMatterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Unit tests for StoreMatterRequest validation rules.
 *
 * Tests all validation rules for matter creation including:
 * - Required fields validation
 * - Foreign key existence validation
 * - Data type validation
 * - Authorization checks
 */
class StoreMatterRequestTest extends TestCase
{
    protected User $adminUser;

    protected User $readWriteUser;

    protected User $readOnlyUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser = User::factory()->admin()->create();
        $this->readWriteUser = User::factory()->readWrite()->create();
        $this->readOnlyUser = User::factory()->readOnly()->create();
    }

    /**
     * Get validation rules from the request class.
     */
    protected function getRules(): array
    {
        return (new StoreMatterRequest)->rules();
    }

    /**
     * Validate data against the request rules.
     */
    protected function validate(array $data): \Illuminate\Validation\Validator
    {
        return Validator::make($data, $this->getRules());
    }

    /** @test */
    public function category_code_is_required()
    {
        $validator = $this->validate([
            'caseref' => 'TEST001',
            'country' => 'US',
            'responsible' => $this->adminUser->login,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category_code', $validator->errors()->toArray());
    }

    /** @test */
    public function category_code_must_exist_in_database()
    {
        $validator = $this->validate([
            'category_code' => 'INVALID',
            'caseref' => 'TEST001',
            'country' => 'US',
            'responsible' => $this->adminUser->login,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category_code', $validator->errors()->toArray());
    }

    /** @test */
    public function category_code_passes_with_valid_value()
    {
        $validator = $this->validate([
            'category_code' => 'PAT',
            'caseref' => 'TEST001',
            'country' => 'US',
            'responsible' => $this->adminUser->login,
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function caseref_is_required()
    {
        $validator = $this->validate([
            'category_code' => 'PAT',
            'country' => 'US',
            'responsible' => $this->adminUser->login,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('caseref', $validator->errors()->toArray());
    }

    /** @test */
    public function caseref_must_be_string()
    {
        $validator = $this->validate([
            'category_code' => 'PAT',
            'caseref' => 12345,
            'country' => 'US',
            'responsible' => $this->adminUser->login,
        ]);

        // Laravel's string rule rejects non-string values
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('caseref', $validator->errors()->toArray());
    }

    /** @test */
    public function caseref_must_not_exceed_30_characters()
    {
        $validator = $this->validate([
            'category_code' => 'PAT',
            'caseref' => str_repeat('A', 31),
            'country' => 'US',
            'responsible' => $this->adminUser->login,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('caseref', $validator->errors()->toArray());
    }

    /** @test */
    public function caseref_with_30_characters_passes()
    {
        $validator = $this->validate([
            'category_code' => 'PAT',
            'caseref' => str_repeat('A', 30),
            'country' => 'US',
            'responsible' => $this->adminUser->login,
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function country_is_required()
    {
        $validator = $this->validate([
            'category_code' => 'PAT',
            'caseref' => 'TEST001',
            'responsible' => $this->adminUser->login,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('country', $validator->errors()->toArray());
    }

    /** @test */
    public function country_must_exist_in_database()
    {
        $validator = $this->validate([
            'category_code' => 'PAT',
            'caseref' => 'TEST001',
            'country' => 'XX',
            'responsible' => $this->adminUser->login,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('country', $validator->errors()->toArray());
    }

    /** @test */
    public function country_passes_with_valid_value()
    {
        $validator = $this->validate([
            'category_code' => 'PAT',
            'caseref' => 'TEST001',
            'country' => 'EP',
            'responsible' => $this->adminUser->login,
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function responsible_is_required()
    {
        $validator = $this->validate([
            'category_code' => 'PAT',
            'caseref' => 'TEST001',
            'country' => 'US',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('responsible', $validator->errors()->toArray());
    }

    /** @test */
    public function responsible_must_not_exceed_20_characters()
    {
        $validator = $this->validate([
            'category_code' => 'PAT',
            'caseref' => 'TEST001',
            'country' => 'US',
            'responsible' => str_repeat('A', 21),
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('responsible', $validator->errors()->toArray());
    }

    /** @test */
    public function origin_is_nullable()
    {
        $validator = $this->validate([
            'category_code' => 'PAT',
            'caseref' => 'TEST001',
            'country' => 'US',
            'responsible' => $this->adminUser->login,
            'origin' => null,
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function origin_must_exist_in_country_table()
    {
        $validator = $this->validate([
            'category_code' => 'PAT',
            'caseref' => 'TEST001',
            'country' => 'US',
            'responsible' => $this->adminUser->login,
            'origin' => 'XX',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('origin', $validator->errors()->toArray());
    }

    /** @test */
    public function origin_passes_with_valid_country()
    {
        $validator = $this->validate([
            'category_code' => 'PAT',
            'caseref' => 'TEST001',
            'country' => 'US',
            'responsible' => $this->adminUser->login,
            'origin' => 'EP',
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function type_code_is_nullable()
    {
        $validator = $this->validate([
            'category_code' => 'PAT',
            'caseref' => 'TEST001',
            'country' => 'US',
            'responsible' => $this->adminUser->login,
            'type_code' => null,
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function type_code_must_exist_in_matter_type_table()
    {
        $validator = $this->validate([
            'category_code' => 'PAT',
            'caseref' => 'TEST001',
            'country' => 'US',
            'responsible' => $this->adminUser->login,
            'type_code' => 'INVALID',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('type_code', $validator->errors()->toArray());
    }

    /** @test */
    public function expire_date_is_nullable()
    {
        $validator = $this->validate([
            'category_code' => 'PAT',
            'caseref' => 'TEST001',
            'country' => 'US',
            'responsible' => $this->adminUser->login,
            'expire_date' => null,
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function expire_date_must_be_valid_date()
    {
        $validator = $this->validate([
            'category_code' => 'PAT',
            'caseref' => 'TEST001',
            'country' => 'US',
            'responsible' => $this->adminUser->login,
            'expire_date' => 'not-a-date',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('expire_date', $validator->errors()->toArray());
    }

    /** @test */
    public function expire_date_passes_with_valid_date()
    {
        $validator = $this->validate([
            'category_code' => 'PAT',
            'caseref' => 'TEST001',
            'country' => 'US',
            'responsible' => $this->adminUser->login,
            'expire_date' => '2040-01-15',
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function dead_must_be_boolean()
    {
        $validator = $this->validate([
            'category_code' => 'PAT',
            'caseref' => 'TEST001',
            'country' => 'US',
            'responsible' => $this->adminUser->login,
            'dead' => 'yes',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('dead', $validator->errors()->toArray());
    }

    /** @test */
    public function dead_passes_with_boolean_values()
    {
        $validator = $this->validate([
            'category_code' => 'PAT',
            'caseref' => 'TEST001',
            'country' => 'US',
            'responsible' => $this->adminUser->login,
            'dead' => true,
        ]);

        $this->assertFalse($validator->fails());

        $validator = $this->validate([
            'category_code' => 'PAT',
            'caseref' => 'TEST001',
            'country' => 'US',
            'responsible' => $this->adminUser->login,
            'dead' => false,
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function notes_is_nullable()
    {
        $validator = $this->validate([
            'category_code' => 'PAT',
            'caseref' => 'TEST001',
            'country' => 'US',
            'responsible' => $this->adminUser->login,
            'notes' => null,
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function notes_must_be_string()
    {
        $validator = $this->validate([
            'category_code' => 'PAT',
            'caseref' => 'TEST001',
            'country' => 'US',
            'responsible' => $this->adminUser->login,
            'notes' => ['array', 'not', 'string'],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('notes', $validator->errors()->toArray());
    }

    /** @test */
    public function operation_must_be_valid_value()
    {
        $validator = $this->validate([
            'category_code' => 'PAT',
            'caseref' => 'TEST001',
            'country' => 'US',
            'responsible' => $this->adminUser->login,
            'operation' => 'invalid',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('operation', $validator->errors()->toArray());
    }

    /** @test */
    public function operation_passes_with_valid_values()
    {
        foreach (['new', 'clone', 'descendant'] as $operation) {
            $validator = $this->validate([
                'category_code' => 'PAT',
                'caseref' => 'TEST001',
                'country' => 'US',
                'responsible' => $this->adminUser->login,
                'operation' => $operation,
            ]);

            $this->assertFalse($validator->fails(), "Operation '$operation' should be valid");
        }
    }

    /** @test */
    public function parent_id_must_exist_in_matter_table()
    {
        $validator = $this->validate([
            'category_code' => 'PAT',
            'caseref' => 'TEST001',
            'country' => 'US',
            'responsible' => $this->adminUser->login,
            'parent_id' => 999999,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('parent_id', $validator->errors()->toArray());
    }

    /** @test */
    public function parent_id_must_be_integer()
    {
        $validator = $this->validate([
            'category_code' => 'PAT',
            'caseref' => 'TEST001',
            'country' => 'US',
            'responsible' => $this->adminUser->login,
            'parent_id' => 'not-an-integer',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('parent_id', $validator->errors()->toArray());
    }

    /** @test */
    public function priority_must_be_boolean()
    {
        $validator = $this->validate([
            'category_code' => 'PAT',
            'caseref' => 'TEST001',
            'country' => 'US',
            'responsible' => $this->adminUser->login,
            'priority' => 'yes',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('priority', $validator->errors()->toArray());
    }

    /** @test */
    public function admin_user_is_authorized()
    {
        $this->actingAs($this->adminUser);

        $request = new StoreMatterRequest;

        $this->assertTrue($request->authorize());
    }

    /** @test */
    public function read_write_user_is_authorized()
    {
        $this->actingAs($this->readWriteUser);

        $request = new StoreMatterRequest;

        $this->assertTrue($request->authorize());
    }

    /** @test */
    public function read_only_user_is_not_authorized()
    {
        $this->actingAs($this->readOnlyUser);

        $request = new StoreMatterRequest;

        $this->assertFalse($request->authorize());
    }

    /** @test */
    public function valid_complete_data_passes_validation()
    {
        $validator = $this->validate([
            'category_code' => 'PAT',
            'caseref' => 'TEST001',
            'country' => 'US',
            'responsible' => $this->adminUser->login,
            'origin' => 'EP',
            'expire_date' => '2040-12-31',
            'dead' => false,
            'notes' => 'Test notes for the matter',
            'operation' => 'new',
            'priority' => true,
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function empty_data_fails_validation()
    {
        $validator = $this->validate([]);

        $this->assertTrue($validator->fails());
        $errors = $validator->errors()->toArray();
        $this->assertArrayHasKey('category_code', $errors);
        $this->assertArrayHasKey('caseref', $errors);
        $this->assertArrayHasKey('country', $errors);
        $this->assertArrayHasKey('responsible', $errors);
    }
}

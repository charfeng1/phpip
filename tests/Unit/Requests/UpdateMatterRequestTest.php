<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\UpdateMatterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Unit tests for UpdateMatterRequest validation rules.
 *
 * Tests all validation rules for matter updates including:
 * - Conditional required fields (sometimes|required)
 * - Foreign key existence validation
 * - Data type validation
 * - Authorization checks
 */
class UpdateMatterRequestTest extends TestCase
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
        return (new UpdateMatterRequest)->rules();
    }

    /**
     * Validate data against the request rules.
     */
    protected function validate(array $data): \Illuminate\Validation\Validator
    {
        return Validator::make($data, $this->getRules());
    }

    /** @test */
    public function empty_data_passes_validation_for_update()
    {
        // Updates can be partial, so empty data should pass
        $validator = $this->validate([]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function category_code_if_present_cannot_be_empty()
    {
        $validator = $this->validate([
            'category_code' => '',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category_code', $validator->errors()->toArray());
    }

    /** @test */
    public function category_code_if_present_must_exist_in_database()
    {
        $validator = $this->validate([
            'category_code' => 'INVALID',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category_code', $validator->errors()->toArray());
    }

    /** @test */
    public function category_code_passes_with_valid_value()
    {
        $validator = $this->validate([
            'category_code' => 'PAT',
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function caseref_if_present_cannot_be_empty()
    {
        $validator = $this->validate([
            'caseref' => '',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('caseref', $validator->errors()->toArray());
    }

    /** @test */
    public function caseref_must_not_exceed_30_characters()
    {
        $validator = $this->validate([
            'caseref' => str_repeat('A', 31),
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('caseref', $validator->errors()->toArray());
    }

    /** @test */
    public function caseref_with_30_characters_passes()
    {
        $validator = $this->validate([
            'caseref' => str_repeat('A', 30),
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function country_if_present_cannot_be_empty()
    {
        $validator = $this->validate([
            'country' => '',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('country', $validator->errors()->toArray());
    }

    /** @test */
    public function country_if_present_must_exist_in_database()
    {
        $validator = $this->validate([
            'country' => 'XX',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('country', $validator->errors()->toArray());
    }

    /** @test */
    public function country_passes_with_valid_value()
    {
        $validator = $this->validate([
            'country' => 'US',
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function responsible_if_present_cannot_be_empty()
    {
        $validator = $this->validate([
            'responsible' => '',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('responsible', $validator->errors()->toArray());
    }

    /** @test */
    public function responsible_must_not_exceed_20_characters()
    {
        $validator = $this->validate([
            'responsible' => str_repeat('A', 21),
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('responsible', $validator->errors()->toArray());
    }

    /** @test */
    public function origin_is_nullable()
    {
        $validator = $this->validate([
            'origin' => null,
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function origin_must_exist_in_country_table()
    {
        $validator = $this->validate([
            'origin' => 'XX',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('origin', $validator->errors()->toArray());
    }

    /** @test */
    public function origin_passes_with_valid_country()
    {
        $validator = $this->validate([
            'origin' => 'EP',
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function type_code_is_nullable()
    {
        $validator = $this->validate([
            'type_code' => null,
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function type_code_must_exist_in_matter_type_table()
    {
        $validator = $this->validate([
            'type_code' => 'INVALID',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('type_code', $validator->errors()->toArray());
    }

    /** @test */
    public function term_adjust_is_nullable()
    {
        $validator = $this->validate([
            'term_adjust' => null,
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function term_adjust_must_be_integer()
    {
        $validator = $this->validate([
            'term_adjust' => 'not-an-integer',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('term_adjust', $validator->errors()->toArray());
    }

    /** @test */
    public function term_adjust_passes_with_integer()
    {
        $validator = $this->validate([
            'term_adjust' => 30,
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function term_adjust_accepts_negative_values()
    {
        $validator = $this->validate([
            'term_adjust' => -10,
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function idx_is_nullable()
    {
        $validator = $this->validate([
            'idx' => null,
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function idx_must_be_integer()
    {
        $validator = $this->validate([
            'idx' => 'not-an-integer',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('idx', $validator->errors()->toArray());
    }

    /** @test */
    public function idx_passes_with_integer()
    {
        $validator = $this->validate([
            'idx' => 2,
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function expire_date_is_nullable()
    {
        $validator = $this->validate([
            'expire_date' => null,
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function expire_date_must_be_valid_date()
    {
        $validator = $this->validate([
            'expire_date' => 'not-a-date',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('expire_date', $validator->errors()->toArray());
    }

    /** @test */
    public function expire_date_passes_with_valid_date()
    {
        $validator = $this->validate([
            'expire_date' => '2040-01-15',
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function dead_must_be_boolean()
    {
        $validator = $this->validate([
            'dead' => 'yes',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('dead', $validator->errors()->toArray());
    }

    /** @test */
    public function dead_passes_with_boolean_true()
    {
        $validator = $this->validate([
            'dead' => true,
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function dead_passes_with_boolean_false()
    {
        $validator = $this->validate([
            'dead' => false,
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function dead_passes_with_integer_1()
    {
        $validator = $this->validate([
            'dead' => 1,
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function dead_passes_with_integer_0()
    {
        $validator = $this->validate([
            'dead' => 0,
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function notes_is_nullable()
    {
        $validator = $this->validate([
            'notes' => null,
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function notes_must_be_string()
    {
        $validator = $this->validate([
            'notes' => ['array', 'not', 'string'],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('notes', $validator->errors()->toArray());
    }

    /** @test */
    public function notes_passes_with_long_text()
    {
        $validator = $this->validate([
            'notes' => str_repeat('This is a long note. ', 1000),
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function admin_user_is_authorized()
    {
        $this->actingAs($this->adminUser);

        $request = new UpdateMatterRequest;

        $this->assertTrue($request->authorize());
    }

    /** @test */
    public function read_write_user_is_authorized()
    {
        $this->actingAs($this->readWriteUser);

        $request = new UpdateMatterRequest;

        $this->assertTrue($request->authorize());
    }

    /** @test */
    public function read_only_user_is_not_authorized()
    {
        $this->actingAs($this->readOnlyUser);

        $request = new UpdateMatterRequest;

        $this->assertFalse($request->authorize());
    }

    /** @test */
    public function partial_update_with_single_field_passes()
    {
        $validator = $this->validate([
            'notes' => 'Updated notes only',
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function partial_update_with_multiple_fields_passes()
    {
        $validator = $this->validate([
            'dead' => true,
            'notes' => 'Matter is now dead',
            'expire_date' => '2025-01-01',
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function full_update_with_all_fields_passes()
    {
        $validator = $this->validate([
            'category_code' => 'PAT',
            'caseref' => 'UPDATED001',
            'country' => 'US',
            'responsible' => $this->adminUser->login,
            'origin' => 'EP',
            'term_adjust' => 30,
            'idx' => 2,
            'expire_date' => '2040-12-31',
            'dead' => false,
            'notes' => 'Complete update with all fields',
        ]);

        $this->assertFalse($validator->fails());
    }
}

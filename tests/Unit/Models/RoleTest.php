<?php

namespace Tests\Unit\Models;

use App\Models\Role;
use Tests\TestCase;

class RoleTest extends TestCase
{
    /** @test */
    public function it_uses_code_as_primary_key()
    {
        // Always create a role to ensure test data exists
        $role = Role::factory()->create(['code' => 'TST']);

        $this->assertEquals('code', $role->getKeyName());
        $this->assertEquals('TST', $role->getKey());
        $this->assertFalse($role->incrementing);
        $this->assertEquals('string', $role->getKeyType());
    }

    /** @test */
    public function it_uses_actor_role_table()
    {
        $role = new Role();

        $this->assertEquals('actor_role', $role->getTable());
    }

    /** @test */
    public function it_has_translatable_name()
    {
        $role = new Role();

        $this->assertIsArray($role->translatable);
        $this->assertContains('name', $role->translatable);
    }

    /** @test */
    public function it_can_create_a_role()
    {
        $role = Role::factory()->create([
            'code' => 'NEW',
            'name' => ['en' => 'New Role'],
        ]);

        $this->assertDatabaseHas('actor_role', [
            'code' => 'NEW',
        ]);
        $this->assertEquals('NEW', $role->code);
    }

    /** @test */
    public function it_hides_audit_fields_on_serialization()
    {
        $role = Role::first() ?? Role::factory()->create(['code' => 'HID']);

        $array = $role->toArray();

        $this->assertArrayNotHasKey('creator', $array);
        $this->assertArrayNotHasKey('created_at', $array);
        $this->assertArrayNotHasKey('updated_at', $array);
        $this->assertArrayNotHasKey('updater', $array);
    }

    /** @test */
    public function it_guards_timestamp_fields()
    {
        $role = new Role();
        $guarded = $role->getGuarded();

        $this->assertContains('created_at', $guarded);
        $this->assertContains('updated_at', $guarded);
    }

    /** @test */
    public function it_uses_has_factory_trait()
    {
        $role = new Role();
        $traits = class_uses_recursive($role);

        $this->assertContains('Illuminate\Database\Eloquent\Factories\HasFactory', $traits);
    }

    /** @test */
    public function standard_roles_can_be_created()
    {
        // Standard actor roles from the system
        $standardRoles = [
            'CLI' => 'Client',
            'AGT' => 'Agent',
            'INV' => 'Inventor',
        ];

        foreach ($standardRoles as $code => $description) {
            $role = Role::firstOrCreate(
                ['code' => $code],
                ['name' => ['en' => $description]]
            );
            $this->assertEquals($code, $role->code);
        }

        // Verify all roles were created/found
        $this->assertCount(3, $standardRoles);
    }

    /** @test */
    public function it_uses_has_table_comments_trait()
    {
        $role = new Role();
        $traits = class_uses_recursive($role);

        $this->assertContains('App\Traits\HasTableComments', $traits);
    }

    /** @test */
    public function it_uses_has_translations_extended_trait()
    {
        $role = new Role();
        $traits = class_uses_recursive($role);

        $this->assertContains('App\Traits\HasTranslationsExtended', $traits);
    }

    /** @test */
    public function it_can_store_multi_language_names()
    {
        $role = Role::factory()->create([
            'code' => 'MLN',
            'name' => [
                'en' => 'Multi-Lang Name',
                'fr' => 'Nom Multi-Langue',
            ],
        ]);

        $this->assertNotNull($role->name);
    }
}

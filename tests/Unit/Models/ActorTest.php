<?php

namespace Tests\Unit\Models;

use App\Models\Actor;
use App\Models\Country;
use App\Models\Matter;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_can_create_an_actor()
    {
        $actor = Actor::factory()->create([
            'name' => 'Test Company Inc.',
            'country' => 'US',
        ]);

        $this->assertDatabaseHas('actor', [
            'name' => 'Test Company Inc.',
            'country' => 'US',
        ]);
        $this->assertNotNull($actor->id);
    }

    /** @test */
    public function it_can_create_a_person_actor()
    {
        $actor = Actor::factory()->person()->create([
            'name' => 'Doe',
            'first_name' => 'John',
        ]);

        $this->assertTrue((bool) $actor->phy_person);
        $this->assertEquals('Doe', $actor->name);
        $this->assertEquals('John', $actor->first_name);
    }

    /** @test */
    public function it_can_create_a_company_actor()
    {
        $actor = Actor::factory()->company()->create([
            'name' => 'Acme Corp',
            'legal_form' => 'Inc.',
        ]);

        $this->assertFalse((bool) $actor->phy_person);
        $this->assertEquals('Acme Corp', $actor->name);
        $this->assertEquals('Inc.', $actor->legal_form);
    }

    /** @test */
    public function it_can_have_a_company_parent()
    {
        $company = Actor::factory()->company()->create();
        $employee = Actor::factory()->person()->create(['company_id' => $company->id]);

        $this->assertEquals($company->id, $employee->company_id);
        $this->assertInstanceOf(Actor::class, $employee->company);
        $this->assertEquals($company->id, $employee->company->id);
    }

    /** @test */
    public function it_can_have_a_parent_actor()
    {
        $parent = Actor::factory()->create();
        $child = Actor::factory()->create(['parent_id' => $parent->id]);

        $this->assertEquals($parent->id, $child->parent_id);
        $this->assertInstanceOf(Actor::class, $child->parent);
    }

    /** @test */
    public function it_can_have_a_site()
    {
        $site = Actor::factory()->create(['name' => 'Headquarters']);
        $actor = Actor::factory()->create(['site_id' => $site->id]);

        $this->assertEquals($site->id, $actor->site_id);
        $this->assertInstanceOf(Actor::class, $actor->site);
    }

    /** @test */
    public function it_can_have_a_default_role()
    {
        $actor = Actor::factory()->asClient()->create();

        $this->assertEquals('CLI', $actor->default_role);
    }

    /** @test */
    public function it_can_have_admin_role()
    {
        $actor = Actor::factory()->asAdmin()->create();

        $this->assertEquals('DBA', $actor->default_role);
        $this->assertNotNull($actor->login);
    }

    /** @test */
    public function it_belongs_to_a_country()
    {
        $actor = Actor::factory()->create(['country' => 'US']);

        $this->assertNotNull($actor->countryInfo);
        $this->assertEquals('US', $actor->countryInfo->iso);
    }

    /** @test */
    public function it_can_have_mailing_country()
    {
        $actor = Actor::factory()->create([
            'country' => 'US',
            'country_mailing' => 'GB',
        ]);

        $this->assertNotNull($actor->country_mailingInfo);
        $this->assertEquals('GB', $actor->country_mailingInfo->iso);
    }

    /** @test */
    public function it_can_have_billing_country()
    {
        $actor = Actor::factory()->create([
            'country' => 'US',
            'country_billing' => 'DE',
        ]);

        $this->assertNotNull($actor->country_billingInfo);
        $this->assertEquals('DE', $actor->country_billingInfo->iso);
    }

    /** @test */
    public function it_can_have_nationality()
    {
        $actor = Actor::factory()->person()->create(['nationality' => 'FR']);

        $this->assertNotNull($actor->nationalityInfo);
        $this->assertEquals('FR', $actor->nationalityInfo->iso);
    }

    /** @test */
    public function it_can_be_a_small_entity()
    {
        $actor = Actor::factory()->smallEntity()->create();

        $this->assertTrue((bool) $actor->small_entity);
    }

    /** @test */
    public function it_can_have_warning_flag()
    {
        $actor = Actor::factory()->withWarning()->create();

        $this->assertTrue((bool) $actor->warn);
        $this->assertNotNull($actor->notes);
    }

    /** @test */
    public function it_can_have_login_credentials()
    {
        $actor = Actor::factory()->withLogin()->create();

        $this->assertNotNull($actor->login);
        $this->assertNotNull($actor->password);
    }

    /** @test */
    public function it_returns_default_language_if_not_set()
    {
        $actor = Actor::factory()->create(['language' => null]);

        $this->assertEquals(config('app.locale'), $actor->getLanguage());
    }

    /** @test */
    public function it_returns_set_language()
    {
        $actor = Actor::factory()->create(['language' => 'fr']);

        $this->assertEquals('fr', $actor->getLanguage());
    }

    /** @test */
    public function it_can_have_matters()
    {
        $actor = Actor::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $actor->matters());
    }

    /** @test */
    public function it_can_have_matters_with_link()
    {
        $actor = Actor::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $actor->mattersWithLnk());
    }

    /** @test */
    public function it_hides_sensitive_attributes()
    {
        $actor = Actor::factory()->withLogin()->create();
        $array = $actor->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
        $this->assertArrayNotHasKey('login', $array);
    }

    /** @test */
    public function it_has_contact_information()
    {
        $actor = Actor::factory()->create([
            'email' => 'test@example.com',
            'phone' => '+1-555-123-4567',
            'address' => '123 Main St',
            'url' => 'https://example.com',
        ]);

        $this->assertEquals('test@example.com', $actor->email);
        $this->assertEquals('+1-555-123-4567', $actor->phone);
        $this->assertEquals('123 Main St', $actor->address);
        $this->assertEquals('https://example.com', $actor->url);
    }

    /** @test */
    public function it_can_have_display_name()
    {
        $actor = Actor::factory()->create([
            'name' => 'Very Long Company Name International Corporation',
            'display_name' => 'VLC Corp',
        ]);

        $this->assertEquals('VLC Corp', $actor->display_name);
    }

    /** @test */
    public function it_can_have_vat_number()
    {
        $actor = Actor::factory()->company()->create([
            'VAT_number' => 'DE123456789',
        ]);

        $this->assertEquals('DE123456789', $actor->VAT_number);
    }

    /** @test */
    public function it_can_have_renewal_discount()
    {
        $actor = Actor::factory()->create(['ren_discount' => 15]);

        $this->assertEquals(15, $actor->ren_discount);
    }
}

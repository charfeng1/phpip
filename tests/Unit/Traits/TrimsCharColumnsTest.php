<?php

namespace Tests\Unit\Traits;

use App\Models\Actor;
use App\Traits\TrimsCharColumns;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class TrimsCharColumnsTest extends TestCase
{
    /** @test */
    public function it_trims_char_columns()
    {
        // Actor model uses TrimsCharColumns trait
        $actor = Actor::factory()->create([
            'country' => 'US',
        ]);

        // Even if PostgreSQL pads the value, it should be trimmed
        $this->assertEquals('US', $actor->country);
        $this->assertNotEquals('US  ', $actor->country);
    }

    /** @test */
    public function it_preserves_null_values()
    {
        $actor = Actor::factory()->create([
            'nationality' => null,
        ]);

        $this->assertNull($actor->nationality);
    }

    /** @test */
    public function it_trims_whitespace_only_strings()
    {
        // Create a test model class that uses the trait
        $testModel = new class extends Model {
            use TrimsCharColumns;

            protected array $charColumns = ['test_column'];

            protected $attributes = [
                'test_column' => '   ',
            ];
        };

        $result = $testModel->getAttribute('test_column');

        $this->assertEquals('', $result);
    }

    /** @test */
    public function it_only_trims_defined_char_columns()
    {
        $testModel = new class extends Model {
            use TrimsCharColumns;

            protected array $charColumns = ['trimmed_column'];

            protected $attributes = [
                'trimmed_column' => 'value   ',
                'untrimmed_column' => 'value   ',
            ];
        };

        $this->assertEquals('value', $testModel->getAttribute('trimmed_column'));
        $this->assertEquals('value   ', $testModel->getAttribute('untrimmed_column'));
    }

    /** @test */
    public function it_handles_non_string_values()
    {
        $testModel = new class extends Model {
            use TrimsCharColumns;

            protected array $charColumns = ['test_column'];

            protected $attributes = [
                'test_column' => 123,
            ];
        };

        // Non-string values should be returned as-is
        $this->assertEquals(123, $testModel->getAttribute('test_column'));
    }

    /** @test */
    public function is_char_column_returns_false_without_property()
    {
        $testModel = new class extends Model {
            use TrimsCharColumns;
        };

        $reflection = new \ReflectionClass($testModel);
        $method = $reflection->getMethod('isCharColumn');
        $method->setAccessible(true);

        $result = $method->invoke($testModel, 'any_column');

        $this->assertFalse($result);
    }

    /** @test */
    public function is_char_column_returns_true_for_defined_columns()
    {
        $testModel = new class extends Model {
            use TrimsCharColumns;

            protected array $charColumns = ['test_column'];
        };

        $reflection = new \ReflectionClass($testModel);
        $method = $reflection->getMethod('isCharColumn');
        $method->setAccessible(true);

        $result = $method->invoke($testModel, 'test_column');

        $this->assertTrue($result);
    }

    /** @test */
    public function actor_model_trims_default_role()
    {
        $actor = Actor::factory()->asClient()->create();

        // CLI should be trimmed to 3 characters even if stored as 'CLI   '
        // Do NOT use trim() here - that would defeat the purpose of testing the trait
        $this->assertEquals('CLI', $actor->default_role);
        $this->assertStringNotContainsString(' ', $actor->default_role ?? '');
    }

    /** @test */
    public function actor_model_trims_login()
    {
        $actor = Actor::factory()->withLogin()->create(['login' => 'testuser']);

        $this->assertEquals('testuser', $actor->login);
    }

    /** @test */
    public function actor_model_trims_language()
    {
        $actor = Actor::factory()->create(['language' => 'en']);

        $this->assertEquals('en', $actor->language);
    }

    /** @test */
    public function it_preserves_empty_strings()
    {
        $testModel = new class extends Model {
            use TrimsCharColumns;

            protected array $charColumns = ['test_column'];

            protected $attributes = [
                'test_column' => '',
            ];
        };

        $result = $testModel->getAttribute('test_column');

        $this->assertEquals('', $result);
    }

    /** @test */
    public function it_handles_mixed_whitespace()
    {
        $testModel = new class extends Model {
            use TrimsCharColumns;

            protected array $charColumns = ['test_column'];

            protected $attributes = [
                'test_column' => "  value\t  ",
            ];
        };

        $result = $testModel->getAttribute('test_column');

        $this->assertEquals('value', $result);
    }
}

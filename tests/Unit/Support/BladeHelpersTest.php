<?php

namespace Tests\Unit\Support;

use App\Support\BladeHelpers;
use Tests\TestCase;

/**
 * Tests for BladeHelpers utility class.
 */
class BladeHelpersTest extends TestCase
{
    /**
     * Test formatAttributes with simple key-value pairs.
     */
    public function test_format_attributes_with_simple_values(): void
    {
        $attributes = [
            'id' => 'myId',
            'class' => 'btn btn-primary',
            'data-value' => '123',
        ];

        $result = BladeHelpers::formatAttributes($attributes);

        $this->assertStringContainsString('id="myId"', $result);
        $this->assertStringContainsString('class="btn btn-primary"', $result);
        $this->assertStringContainsString('data-value="123"', $result);
    }

    /**
     * Test formatAttributes with boolean values.
     */
    public function test_format_attributes_with_boolean_values(): void
    {
        $attributes = [
            'required' => true,
            'disabled' => false,
            'readonly' => true,
        ];

        $result = BladeHelpers::formatAttributes($attributes);

        $this->assertStringContainsString('required', $result);
        $this->assertStringNotContainsString('disabled', $result);
        $this->assertStringContainsString('readonly', $result);
    }

    /**
     * Test formatAttributes properly escapes HTML entities.
     */
    public function test_format_attributes_escapes_html(): void
    {
        $attributes = [
            'data-value' => '<script>alert("XSS")</script>',
            'title' => 'Test & "quoted"',
        ];

        $result = BladeHelpers::formatAttributes($attributes);

        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('alert', $result);
        $this->assertStringContainsString('&lt;', $result);
        $this->assertStringContainsString('&amp;', $result);
        $this->assertStringContainsString('&quot;', $result);
    }

    /**
     * Test formatAttributes with empty array.
     */
    public function test_format_attributes_with_empty_array(): void
    {
        $result = BladeHelpers::formatAttributes([]);

        $this->assertSame('', $result);
    }

    /**
     * Test formatAttributes with mixed types.
     */
    public function test_format_attributes_with_mixed_types(): void
    {
        $attributes = [
            'id' => 'test',
            'required' => true,
            'data-index' => '0',
            'disabled' => false,
            'class' => 'form-control',
        ];

        $result = BladeHelpers::formatAttributes($attributes);

        $this->assertStringContainsString('id="test"', $result);
        $this->assertStringContainsString('required', $result);
        $this->assertStringContainsString('data-index="0"', $result);
        $this->assertStringNotContainsString('disabled', $result);
        $this->assertStringContainsString('class="form-control"', $result);
    }
}

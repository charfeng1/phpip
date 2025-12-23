<?php

namespace Tests\Feature\Components;

use Illuminate\Support\HtmlString;
use Tests\TestCase;

/**
 * Tests for Blade view components.
 */
class BladeComponentsTest extends TestCase
{
    /**
     * Test list-with-panel component renders correctly.
     */
    public function test_list_with_panel_component_renders(): void
    {
        $view = view('components.list-with-panel', [
            'title' => 'Test Title',
            'createUrl' => '/test/create',
            'createLabel' => 'Create New',
            'panelTitle' => 'Details Panel',
            'panelMessage' => 'No item selected',
            'list' => 'Test list content',
        ]);

        $html = $view->render();

        $this->assertStringContainsString('Test Title', $html);
        $this->assertStringContainsString('/test/create', $html);
        $this->assertStringContainsString('Create New', $html);
        $this->assertStringContainsString('Details Panel', $html);
        $this->assertStringContainsString('No item selected', $html);
        $this->assertStringContainsString('Test list content', $html);
    }

    /**
     * Test list-with-panel component uses titleSlot when provided.
     */
    public function test_list_with_panel_uses_title_slot(): void
    {
        $view = view('components.list-with-panel', [
            'title' => 'Fallback Title',
            'panelTitle' => 'Details',
            'list' => 'Content',
            'titleSlot' => 'Custom <strong>HTML</strong> Title',
        ]);

        $html = $view->render();

        $this->assertStringContainsString('Custom', $html);
        $this->assertStringContainsString('HTML', $html);
    }

    /**
     * Test autocomplete-field component renders correctly.
     */
    public function test_autocomplete_field_component_renders(): void
    {
        $options = [
            ['code' => 'US', 'name' => 'United States'],
            ['code' => 'CA', 'name' => 'Canada'],
            ['code' => 'UK', 'name' => 'United Kingdom'],
        ];

        $view = view('components.autocomplete-field', [
            'id' => 'country',
            'name' => 'country_code',
            'label' => 'Country',
            'options' => $options,
            'optionValue' => 'code',
            'optionLabel' => 'name',
            'selectedValue' => 'US',
            'selectedLabel' => 'United States',
        ]);

        $html = $view->render();

        $this->assertStringContainsString('Country', $html);
        $this->assertStringContainsString('country_code', $html);
        $this->assertStringContainsString('United States', $html);
        $this->assertStringContainsString('Canada', $html);
        $this->assertStringContainsString('United Kingdom', $html);
    }

    /**
     * Test autocomplete-field component marks field as required.
     */
    public function test_autocomplete_field_required_attribute(): void
    {
        $view = view('components.autocomplete-field', [
            'id' => 'test',
            'name' => 'test_field',
            'label' => 'Test',
            'options' => [],
            'required' => true,
        ]);

        $html = $view->render();

        $this->assertStringContainsString('required', $html);
    }

    /**
     * Test form-generator component renders text inputs.
     */
    public function test_form_generator_renders_text_inputs(): void
    {
        $rows = [
            [
                [
                    'label' => 'Name',
                    'name' => 'name',
                    'type' => 'text',
                    'value' => 'Test Name',
                ],
                [
                    'label' => 'Email',
                    'name' => 'email',
                    'type' => 'email',
                    'value' => 'test@example.com',
                ],
            ],
        ];

        $view = view('components.form-generator', ['rows' => $rows]);
        $html = $view->render();

        $this->assertStringContainsString('Name', $html);
        $this->assertStringContainsString('name="name"', $html);
        $this->assertStringContainsString('Test Name', $html);
        $this->assertStringContainsString('Email', $html);
        $this->assertStringContainsString('type="email"', $html);
        $this->assertStringContainsString('test@example.com', $html);
    }

    /**
     * Test form-generator component renders textarea.
     */
    public function test_form_generator_renders_textarea(): void
    {
        $rows = [
            [
                [
                    'label' => 'Notes',
                    'name' => 'notes',
                    'type' => 'textarea',
                    'value' => 'Test notes content',
                ],
            ],
        ];

        $view = view('components.form-generator', ['rows' => $rows]);
        $html = $view->render();

        $this->assertStringContainsString('Notes', $html);
        $this->assertStringContainsString('<textarea', $html);
        $this->assertStringContainsString('name="notes"', $html);
        $this->assertStringContainsString('Test notes content', $html);
    }

    /**
     * Test form-generator component renders custom HTML.
     */
    public function test_form_generator_renders_custom_content(): void
    {
        $customHtml = new HtmlString('<input type="hidden" name="custom_field" value="custom_value">');

        $rows = [
            [
                [
                    'label' => 'Custom Field',
                    'type' => 'custom',
                    'content' => $customHtml,
                ],
            ],
        ];

        $view = view('components.form-generator', ['rows' => $rows]);
        $html = $view->render();

        $this->assertStringContainsString('Custom Field', $html);
        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('name="custom_field"', $html);
        $this->assertStringContainsString('value="custom_value"', $html);
    }

    /**
     * Test form-generator component applies custom CSS classes.
     */
    public function test_form_generator_applies_custom_classes(): void
    {
        $rows = [
            [
                [
                    'label' => 'Test',
                    'name' => 'test',
                    'labelClass' => 'fw-bold text-primary',
                    'inputClass' => 'custom-input-class',
                ],
            ],
        ];

        $view = view('components.form-generator', ['rows' => $rows]);
        $html = $view->render();

        $this->assertStringContainsString('fw-bold text-primary', $html);
        $this->assertStringContainsString('custom-input-class', $html);
    }
}

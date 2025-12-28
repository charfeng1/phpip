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

    // =====================================================
    // Tests for new Phase 2 components
    // =====================================================

    /**
     * Test form-field component renders text input.
     */
    public function test_form_field_renders_text_input(): void
    {
        $html = \Illuminate\Support\Facades\View::make('components.form-field', [
            'name' => 'test_field',
            'label' => 'Test Label',
        ])->render();

        $this->assertStringContainsString('Test Label', $html);
        $this->assertStringContainsString('name="test_field"', $html);
        $this->assertStringContainsString('type="text"', $html);
    }

    /**
     * Test form-field component renders required indicator.
     */
    public function test_form_field_renders_required_indicator(): void
    {
        $html = \Illuminate\Support\Facades\View::make('components.form-field', [
            'name' => 'test',
            'label' => 'Required Field',
            'required' => true,
        ])->render();

        $this->assertStringContainsString('Required Field *', $html);
        $this->assertStringContainsString('required', $html);
        $this->assertStringContainsString('fw-bold', $html);
    }

    /**
     * Test form-field component renders textarea.
     */
    public function test_form_field_renders_textarea(): void
    {
        $html = \Illuminate\Support\Facades\View::make('components.form-field', [
            'name' => 'notes',
            'label' => 'Notes',
            'type' => 'textarea',
            'value' => 'Initial text',
        ])->render();

        $this->assertStringContainsString('<textarea', $html);
        $this->assertStringContainsString('name="notes"', $html);
        $this->assertStringContainsString('Initial text', $html);
    }

    /**
     * Test form-field component renders select with options.
     */
    public function test_form_field_renders_select_with_options(): void
    {
        $html = \Illuminate\Support\Facades\View::make('components.form-field', [
            'name' => 'status',
            'label' => 'Status',
            'type' => 'select',
            'options' => ['active' => 'Active', 'inactive' => 'Inactive'],
            'value' => 'active',
        ])->render();

        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('name="status"', $html);
        $this->assertStringContainsString('Active', $html);
        $this->assertStringContainsString('Inactive', $html);
        $this->assertStringContainsString('selected', $html);
    }

    /**
     * Test form-field component renders helper text.
     */
    public function test_form_field_renders_helper_text(): void
    {
        $html = \Illuminate\Support\Facades\View::make('components.form-field', [
            'name' => 'email',
            'label' => 'Email',
            'helper' => 'Enter your email address',
        ])->render();

        $this->assertStringContainsString('form-text text-muted', $html);
        $this->assertStringContainsString('Enter your email address', $html);
    }

    /**
     * Test status-badge component renders overdue state.
     */
    public function test_status_badge_renders_overdue_state(): void
    {
        $pastDate = now()->subDay()->toDateString();

        $html = \Illuminate\Support\Facades\View::make('components.status-badge', [
            'date' => $pastDate,
        ])->render();

        $this->assertStringContainsString('bg-danger', $html);
        $this->assertStringContainsString('Overdue', $html);
    }

    /**
     * Test status-badge component renders warning state.
     */
    public function test_status_badge_renders_warning_state(): void
    {
        $soonDate = now()->addDays(7)->toDateString();

        $html = \Illuminate\Support\Facades\View::make('components.status-badge', [
            'date' => $soonDate,
        ])->render();

        $this->assertStringContainsString('bg-warning', $html);
    }

    /**
     * Test status-badge component renders success state.
     */
    public function test_status_badge_renders_success_state(): void
    {
        $futureDate = now()->addDays(30)->toDateString();

        $html = \Illuminate\Support\Facades\View::make('components.status-badge', [
            'date' => $futureDate,
        ])->render();

        $this->assertStringContainsString('bg-success', $html);
    }

    /**
     * Test status-badge component renders custom label.
     */
    public function test_status_badge_renders_custom_label(): void
    {
        $html = \Illuminate\Support\Facades\View::make('components.status-badge', [
            'type' => 'status',
            'status' => 'info',
            'label' => 'Custom Label',
        ])->render();

        $this->assertStringContainsString('bg-info', $html);
        $this->assertStringContainsString('Custom Label', $html);
    }

    /**
     * Test modal-button component renders with correct attributes.
     */
    public function test_modal_button_renders_with_correct_attributes(): void
    {
        $html = \Illuminate\Support\Facades\View::make('components.modal-button', [
            'href' => '/create',
            'label' => 'Create Item',
        ])->render();

        $this->assertStringContainsString('href="/create"', $html);
        $this->assertStringContainsString('Create Item', $html);
        $this->assertStringContainsString('data-bs-toggle="modal"', $html);
        $this->assertStringContainsString('data-bs-target="#ajaxModal"', $html);
    }

    /**
     * Test modal-button component renders with icon.
     */
    public function test_modal_button_renders_with_icon(): void
    {
        $html = \Illuminate\Support\Facades\View::make('components.modal-button', [
            'href' => '/create',
            'label' => 'Add',
            'icon' => 'plus',
        ])->render();

        $this->assertStringContainsString('<svg', $html);
    }

    /**
     * Test modal-button component renders with modal size.
     */
    public function test_modal_button_renders_with_modal_size(): void
    {
        $html = \Illuminate\Support\Facades\View::make('components.modal-button', [
            'href' => '/create',
            'label' => 'Open',
            'modalSize' => 'modal-lg',
        ])->render();

        $this->assertStringContainsString('data-size="modal-lg"', $html);
    }

    /**
     * Test modal-button component renders outline variant.
     */
    public function test_modal_button_renders_outline_variant(): void
    {
        $html = \Illuminate\Support\Facades\View::make('components.modal-button', [
            'href' => '/create',
            'label' => 'Open',
            'outline' => true,
        ])->render();

        $this->assertStringContainsString('btn-outline-primary', $html);
    }

    /**
     * Test date-input component renders basic input.
     */
    public function test_date_input_renders_basic_input(): void
    {
        $html = \Illuminate\Support\Facades\View::make('components.date-input', [
            'name' => 'event_date',
            'label' => 'Event Date',
        ])->render();

        $this->assertStringContainsString('Event Date', $html);
        $this->assertStringContainsString('name="event_date"', $html);
    }

    /**
     * Test date-input component renders native type.
     */
    public function test_date_input_renders_native_type(): void
    {
        $html = \Illuminate\Support\Facades\View::make('components.date-input', [
            'name' => 'event_date',
            'useNative' => true,
            'label' => 'Event Date',
        ])->render();

        $this->assertStringContainsString('type="date"', $html);
    }

    /**
     * Test date-input component renders inline mode.
     */
    public function test_date_input_renders_inline_mode(): void
    {
        $html = \Illuminate\Support\Facades\View::make('components.date-input', [
            'name' => 'filter_date',
            'inline' => true,
        ])->render();

        // Inline mode should not have the row wrapper with the label column layout
        $this->assertStringNotContainsString('col-form-label', $html);
    }

    /**
     * Test table-filter component renders basic input.
     */
    public function test_table_filter_renders_basic_input(): void
    {
        $html = \Illuminate\Support\Facades\View::make('components.table-filter', [
            'name' => 'Name',
            'placeholder' => 'Filter by name',
        ])->render();

        $this->assertStringContainsString('name="Name"', $html);
        $this->assertStringContainsString('placeholder="Filter by name"', $html);
        $this->assertStringContainsString('filter-input', $html);
    }

    /**
     * Test table-filter component renders with sort button.
     */
    public function test_table_filter_renders_with_sort_button(): void
    {
        $html = \Illuminate\Support\Facades\View::make('components.table-filter', [
            'name' => 'Ref',
            'sortable' => true,
            'sortKey' => 'caseref',
        ])->render();

        $this->assertStringContainsString('sort-btn', $html);
        $this->assertStringContainsString('data-sortkey="caseref"', $html);
    }

    /**
     * Test table-filter component renders clear button when has value.
     */
    public function test_table_filter_renders_clear_button_when_has_value(): void
    {
        $html = \Illuminate\Support\Facades\View::make('components.table-filter', [
            'name' => 'Name',
            'value' => 'test',
        ])->render();

        $this->assertStringContainsString('clear-filter', $html);
        $this->assertStringContainsString('data-target="Name"', $html);
    }

    /**
     * Test table-filter component hides clear button when empty.
     */
    public function test_table_filter_hides_clear_button_when_empty(): void
    {
        $html = \Illuminate\Support\Facades\View::make('components.table-filter', [
            'name' => 'Name',
            'value' => '',
            'clearable' => true,
        ])->render();

        $this->assertStringNotContainsString('clear-filter', $html);
    }

    /**
     * Test form-errors component renders when errors exist.
     */
    public function test_form_errors_renders_when_errors_exist(): void
    {
        // Create a view with errors in the error bag using TestView assertions
        $this->withViewErrors(['name' => 'The name field is required.'])
            ->view('components.form-errors')
            ->assertSee('alert-danger', false)
            ->assertSee('The name field is required.');
    }

    /**
     * Test form-errors component does not render when no errors.
     */
    public function test_form_errors_does_not_render_when_no_errors(): void
    {
        // Use View facade directly for rendering without errors
        $html = \Illuminate\Support\Facades\View::make('components.form-errors')->render();

        $this->assertStringNotContainsString('alert-danger', $html);
    }
}

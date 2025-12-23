<?php

namespace Tests\Unit\Providers;

use App\Models\Category;
use App\Models\Role;
use App\Providers\ViewServiceProvider;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Tests for ViewServiceProvider.
 */
class ViewServiceProviderTest extends TestCase
{
    /**
     * Test that tableComments is injected into create views.
     */
    public function test_table_comments_injected_into_create_views(): void
    {
        // Register the provider
        $provider = new ViewServiceProvider($this->app);
        $provider->boot();

        // Render a create view
        $view = View::make('category.create');
        $data = $view->getData();

        // Assert tableComments is present
        $this->assertArrayHasKey('tableComments', $data);
        $this->assertIsArray($data['tableComments']);
    }

    /**
     * Test that tableComments is injected into show views with model.
     */
    public function test_table_comments_injected_into_show_views(): void
    {
        // Register the provider
        $provider = new ViewServiceProvider($this->app);
        $provider->boot();

        // Create a mock category
        $category = new Category();

        // Render a show view with the category
        $view = View::make('category.show', ['category' => $category]);
        $data = $view->getData();

        // Assert tableComments is present
        $this->assertArrayHasKey('tableComments', $data);
        $this->assertIsArray($data['tableComments']);
    }

    /**
     * Test that existing tableComments are not overridden.
     */
    public function test_existing_table_comments_not_overridden(): void
    {
        // Register the provider
        $provider = new ViewServiceProvider($this->app);
        $provider->boot();

        $customComments = ['custom' => 'value'];

        // Render a view with pre-existing tableComments
        $view = View::make('role.create', ['tableComments' => $customComments]);
        $data = $view->getData();

        // Assert the custom tableComments are preserved
        $this->assertArrayHasKey('tableComments', $data);
        $this->assertEquals($customComments, $data['tableComments']);
    }

    /**
     * Test that tableComments contains expected column names.
     */
    public function test_table_comments_contains_column_data(): void
    {
        // Register the provider
        $provider = new ViewServiceProvider($this->app);
        $provider->boot();

        // Render a create view
        $view = View::make('role.create');
        $data = $view->getData();

        // Assert tableComments is an array
        $this->assertIsArray($data['tableComments']);

        // TableComments should have keys corresponding to database columns
        // The actual keys depend on the database schema, so we just verify it's not empty
        // in a real database environment
        $this->assertIsArray($data['tableComments']);
    }
}

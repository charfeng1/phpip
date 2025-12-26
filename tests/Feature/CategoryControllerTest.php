<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    /** @test */
    public function guest_cannot_access_categories()
    {
        $response = $this->get(route('category.index'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function admin_can_access_category_index()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('category.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function read_only_user_can_access_category_index()
    {
        $user = User::factory()->readOnly()->create();

        $response = $this->actingAs($user)->get(route('category.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function client_cannot_access_category_index()
    {
        $user = User::factory()->client()->create();

        $response = $this->actingAs($user)->get(route('category.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_create_category()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->post(route('category.store'), [
            'code' => 'TST',
            'category' => 'Test Category',
            'display_with' => 0,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('matter_category', ['code' => 'TST']);
    }

    /** @test */
    public function read_write_user_cannot_create_category()
    {
        $user = User::factory()->readWrite()->create();

        $response = $this->actingAs($user)->post(route('category.store'), [
            'code' => 'NEW',
            'category' => 'New Category',
            'display_with' => 0,
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_update_category()
    {
        $user = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($user)->put(route('category.update', $category), [
            'category' => 'Updated Category',
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function read_write_user_cannot_update_category()
    {
        $user = User::factory()->readWrite()->create();
        $category = Category::first() ?? Category::factory()->create();

        $response = $this->actingAs($user)->put(route('category.update', $category), [
            'category' => 'Changed Category',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_delete_category()
    {
        $user = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($user)->delete(route('category.destroy', $category));

        $response->assertStatus(200);
        $this->assertDatabaseMissing('matter_category', ['code' => $category->code]);
    }

    /** @test */
    public function category_index_returns_categories()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('category.index'));

        $response->assertStatus(200);
        $response->assertViewIs('category.index');
    }
}

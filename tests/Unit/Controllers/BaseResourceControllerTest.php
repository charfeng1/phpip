<?php

namespace Tests\Unit\Controllers;

use App\Http\Controllers\BaseResourceController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for BaseResourceController.
 *
 * Note: Methods that require the Laravel container (response(), app(), etc.)
 * are tested in feature tests. These unit tests focus on pure logic.
 */
class BaseResourceControllerTest extends TestCase
{
    protected TestableBaseResourceController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new TestableBaseResourceController();
    }

    /** @test */
    public function wants_json_returns_true_for_json_request()
    {
        $request = Request::create('/', 'GET');
        $request->headers->set('Accept', 'application/json');

        $this->assertTrue($this->controller->callWantsJson($request));
    }

    /** @test */
    public function wants_json_returns_false_for_html_request()
    {
        $request = Request::create('/', 'GET');
        $request->headers->set('Accept', 'text/html');

        $this->assertFalse($this->controller->callWantsJson($request));
    }

    /** @test */
    public function perform_destroy_deletes_model_and_returns_it()
    {
        $model = $this->createMock(Model::class);
        $model->expects($this->once())->method('delete');

        $result = $this->controller->callPerformDestroy($model);

        $this->assertSame($model, $result);
    }

    /** @test */
    public function get_excluded_fields_returns_default_fields()
    {
        $excluded = $this->controller->callGetExcludedFields();

        $this->assertContains('_token', $excluded);
        $this->assertContains('_method', $excluded);
    }

    /** @test */
    public function pagination_config_key_has_default_value()
    {
        $this->assertEquals('pagination.default', $this->controller->getPaginationConfigKey());
    }

    /** @test */
    public function default_per_page_is_21()
    {
        $this->assertEquals(21, $this->controller->getDefaultPerPage());
    }
}

/**
 * Testable controller to expose protected methods.
 */
class TestableBaseResourceController extends BaseResourceController
{
    public function callWantsJson(Request $request): bool
    {
        return $this->wantsJson($request);
    }

    public function callPerformDestroy(Model $model): Model
    {
        return $this->performDestroy($model);
    }

    public function callGetExcludedFields(): array
    {
        return $this->getExcludedFields();
    }

    public function getPaginationConfigKey(): string
    {
        return $this->paginationConfigKey;
    }

    public function getDefaultPerPage(): int
    {
        return $this->defaultPerPage;
    }
}

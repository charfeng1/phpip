<?php

namespace Tests\Unit\Traits;

use App\Traits\JsonResponses;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;

class JsonResponsesTest extends TestCase
{
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an anonymous class that uses the trait
        $this->controller = new class {
            use JsonResponses;
        };
    }

    /** @test */
    public function success_response_returns_json_response()
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('successResponse');
        $method->setAccessible(true);

        $response = $method->invoke($this->controller);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /** @test */
    public function success_response_has_success_status()
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('successResponse');
        $method->setAccessible(true);

        $response = $method->invoke($this->controller);
        $data = $response->getData(true);

        $this->assertEquals('success', $data['status']);
    }

    /** @test */
    public function success_response_includes_message()
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('successResponse');
        $method->setAccessible(true);

        $response = $method->invoke($this->controller, null, 'Custom message');
        $data = $response->getData(true);

        $this->assertEquals('Custom message', $data['message']);
    }

    /** @test */
    public function success_response_includes_data()
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('successResponse');
        $method->setAccessible(true);

        $response = $method->invoke($this->controller, ['key' => 'value']);
        $data = $response->getData(true);

        $this->assertArrayHasKey('data', $data);
        $this->assertEquals('value', $data['data']['key']);
    }

    /** @test */
    public function success_response_excludes_data_when_null()
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('successResponse');
        $method->setAccessible(true);

        $response = $method->invoke($this->controller, null);
        $data = $response->getData(true);

        $this->assertArrayNotHasKey('data', $data);
    }

    /** @test */
    public function success_response_uses_correct_status_code()
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('successResponse');
        $method->setAccessible(true);

        $response = $method->invoke($this->controller, null, 'Created', 201);

        $this->assertEquals(201, $response->getStatusCode());
    }

    /** @test */
    public function error_response_returns_json_response()
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('errorResponse');
        $method->setAccessible(true);

        $response = $method->invoke($this->controller);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /** @test */
    public function error_response_has_error_status()
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('errorResponse');
        $method->setAccessible(true);

        $response = $method->invoke($this->controller);
        $data = $response->getData(true);

        $this->assertEquals('error', $data['status']);
    }

    /** @test */
    public function error_response_includes_errors()
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('errorResponse');
        $method->setAccessible(true);

        $response = $method->invoke($this->controller, 'Error', 400, ['field' => 'error']);
        $data = $response->getData(true);

        $this->assertArrayHasKey('errors', $data);
        $this->assertEquals('error', $data['errors']['field']);
    }

    /** @test */
    public function error_response_excludes_errors_when_null()
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('errorResponse');
        $method->setAccessible(true);

        $response = $method->invoke($this->controller, 'Error', 400, null);
        $data = $response->getData(true);

        $this->assertArrayNotHasKey('errors', $data);
    }

    /** @test */
    public function redirect_response_includes_url()
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('redirectResponse');
        $method->setAccessible(true);

        $response = $method->invoke($this->controller, '/dashboard');
        $data = $response->getData(true);

        $this->assertArrayHasKey('redirect', $data);
        $this->assertEquals('/dashboard', $data['redirect']);
    }

    /** @test */
    public function redirect_response_includes_optional_message()
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('redirectResponse');
        $method->setAccessible(true);

        $response = $method->invoke($this->controller, '/dashboard', 'Redirecting...');
        $data = $response->getData(true);

        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Redirecting...', $data['message']);
    }

    /** @test */
    public function validation_error_response_returns_422()
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('validationErrorResponse');
        $method->setAccessible(true);

        $response = $method->invoke($this->controller, ['field' => 'required']);

        $this->assertEquals(422, $response->getStatusCode());
    }

    /** @test */
    public function not_found_response_returns_404()
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('notFoundResponse');
        $method->setAccessible(true);

        $response = $method->invoke($this->controller);

        $this->assertEquals(404, $response->getStatusCode());
    }

    /** @test */
    public function unauthorized_response_returns_401()
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('unauthorizedResponse');
        $method->setAccessible(true);

        $response = $method->invoke($this->controller);

        $this->assertEquals(401, $response->getStatusCode());
    }

    /** @test */
    public function forbidden_response_returns_403()
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('forbiddenResponse');
        $method->setAccessible(true);

        $response = $method->invoke($this->controller);

        $this->assertEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function forbidden_response_has_default_message()
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('forbiddenResponse');
        $method->setAccessible(true);

        $response = $method->invoke($this->controller);
        $data = $response->getData(true);

        $this->assertEquals('Forbidden', $data['message']);
    }

    /** @test */
    public function forbidden_response_accepts_custom_message()
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('forbiddenResponse');
        $method->setAccessible(true);

        $response = $method->invoke($this->controller, 'Custom forbidden message');
        $data = $response->getData(true);

        $this->assertEquals('Custom forbidden message', $data['message']);
    }
}

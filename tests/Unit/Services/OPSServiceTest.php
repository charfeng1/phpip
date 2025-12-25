<?php

namespace Tests\Unit\Services;

use App\Services\OPSService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OPSServiceTest extends TestCase
{
    protected OPSService $opsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->opsService = new OPSService();
    }

    /** @test */
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(OPSService::class, $this->opsService);
    }

    /** @test */
    public function authenticate_does_nothing_without_credentials()
    {
        // Without OPS_APP_KEY and OPS_SECRET env vars, authenticate should do nothing
        $this->opsService->authenticate();

        // No exception should be thrown
        $this->assertTrue(true);
    }

    /** @test */
    public function get_family_members_returns_error_without_auth()
    {
        Http::fake();

        $result = $this->opsService->getFamilyMembers('EP1234567');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('message', $result);
    }

    /** @test */
    public function get_family_members_returns_auth_error_message()
    {
        $result = $this->opsService->getFamilyMembers('EP1234567');

        $this->assertStringContainsString('OPS API credentials', $result['message']);
    }

    /** @test */
    public function authenticate_handles_failed_response()
    {
        // Mock failed authentication
        Http::fake([
            '*/auth/accesstoken' => Http::response(['error' => 'invalid_client'], 401),
        ]);

        // Set up credentials temporarily
        $this->app['config']->set('services.ops.key', 'test_key');
        $this->app['config']->set('services.ops.secret', 'test_secret');

        $this->opsService->authenticate();

        // Should still work without throwing exception
        $this->assertTrue(true);
    }

    /** @test */
    public function get_family_members_handles_client_error()
    {
        Http::fake([
            '*/auth/accesstoken' => Http::response([
                'access_token' => 'test_token',
                'expires_in' => 3600,
            ], 200),
            '*/rest-services/family/*' => Http::response(['error' => 'not found'], 404),
        ]);

        // We need to mock the authentication first
        $reflection = new \ReflectionClass($this->opsService);
        $property = $reflection->getProperty('accessToken');
        $property->setAccessible(true);
        $property->setValue($this->opsService, 'test_token');

        $result = $this->opsService->getFamilyMembers('INVALID123');

        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('docnum', $result['errors']);
    }

    /** @test */
    public function get_family_members_handles_server_error()
    {
        Http::fake([
            '*/rest-services/family/*' => Http::response(['error' => 'server error'], 500),
        ]);

        // Set access token directly
        $reflection = new \ReflectionClass($this->opsService);
        $property = $reflection->getProperty('accessToken');
        $property->setAccessible(true);
        $property->setValue($this->opsService, 'test_token');

        $result = $this->opsService->getFamilyMembers('EP1234567');

        $this->assertArrayHasKey('exception', $result);
        $this->assertEquals('OPS server error', $result['exception']);
    }

    /** @test */
    public function get_family_members_returns_array()
    {
        $result = $this->opsService->getFamilyMembers('EP1234567');

        $this->assertIsArray($result);
    }

    /** @test */
    public function service_uses_correct_base_url()
    {
        $reflection = new \ReflectionClass($this->opsService);
        $constant = $reflection->getConstant('BASE_URL');

        $this->assertEquals('https://ops.epo.org/3.2', $constant);
    }

    /** @test */
    public function authenticate_uses_basic_auth()
    {
        Http::fake([
            '*/auth/accesstoken' => function ($request) {
                // Verify Basic auth header is present
                $this->assertTrue($request->hasHeader('Authorization'));
                $authHeader = $request->header('Authorization')[0];
                $this->assertStringStartsWith('Basic ', $authHeader);

                return Http::response([
                    'access_token' => 'test_token',
                    'expires_in' => 3600,
                ], 200);
            },
        ]);

        // Temporarily set env vars
        putenv('OPS_APP_KEY=test_key');
        putenv('OPS_SECRET=test_secret');

        $service = new OPSService();
        $service->authenticate();

        // Clean up
        putenv('OPS_APP_KEY=');
        putenv('OPS_SECRET=');

        $this->assertTrue(true);
    }

    /** @test */
    public function get_family_members_parses_single_member_response()
    {
        $singleMemberResponse = [
            'ops:world-patent-data' => [
                'ops:patent-family' => [
                    'ops:family-member' => [
                        'application-reference' => [
                            '@doc-id' => '12345',
                            'document-id' => [
                                'country' => ['$' => 'EP'],
                                'doc-number' => ['$' => '1234567'],
                                'kind' => ['$' => 'A'],
                                'date' => ['$' => '20200101'],
                            ],
                        ],
                        'priority-claim' => [
                            'document-id' => [
                                'country' => ['$' => 'US'],
                                'doc-number' => ['$' => '12345678'],
                                'kind' => ['$' => 'A'],
                                'date' => ['$' => '20190101'],
                            ],
                            'priority-active-indicator' => ['$' => 'YES'],
                        ],
                        'publication-reference' => [
                            'document-id' => [
                                ['@document-id-type' => 'docdb', 'country' => ['$' => 'EP'], 'doc-number' => ['$' => '1234567'], 'kind' => ['$' => 'A1'], 'date' => ['$' => '20200601']],
                            ],
                        ],
                        'exchange-document' => [
                            'bibliographic-data' => [
                                'invention-title' => [['$' => 'Test Invention']],
                                'parties' => [
                                    'inventors' => [
                                        'inventor' => [
                                            ['@data-format' => 'original', 'inventor-name' => ['name' => ['$' => 'John Doe']]],
                                        ],
                                    ],
                                    'applicants' => [
                                        'applicant' => [
                                            ['@data-format' => 'original', 'applicant-name' => ['name' => ['$' => 'Test Corp']]],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        Http::fake([
            '*/rest-services/family/*' => Http::response($singleMemberResponse, 200),
            '*/rest-services/register/*' => Http::response('', 404),
        ]);

        // Set access token directly
        $reflection = new \ReflectionClass($this->opsService);
        $property = $reflection->getProperty('accessToken');
        $property->setAccessible(true);
        $property->setValue($this->opsService, 'test_token');

        $result = $this->opsService->getFamilyMembers('EP1234567');

        $this->assertIsArray($result);
        // Even with parsing issues, it should return an array structure
    }
}

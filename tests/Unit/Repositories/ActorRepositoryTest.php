<?php

namespace Tests\Unit\Repositories;

use App\Repositories\ActorRepository;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Unit tests for ActorRepository.
 *
 * Tests the repository's helper methods for selector filtering.
 * Database-dependent tests require integration tests.
 */
class ActorRepositoryTest extends TestCase
{
    protected ActorRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ActorRepository;
    }

    /**
     * Helper method to test protected method return values by checking the expected behavior.
     * Since applySelectorFilter requires a query builder, we test the match statement logic indirectly.
     */
    protected function callProtectedMethod(string $method, array $args = [])
    {
        $reflection = new ReflectionClass($this->repository);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs($this->repository, $args);
    }

    /** @test */
    public function repository_can_be_instantiated(): void
    {
        $repository = new ActorRepository;

        $this->assertInstanceOf(ActorRepository::class, $repository);
    }

    /** @test */
    public function repository_has_find_method(): void
    {
        $this->assertTrue(method_exists($this->repository, 'find'));
    }

    /** @test */
    public function repository_has_find_with_company_method(): void
    {
        $this->assertTrue(method_exists($this->repository, 'findWithCompany'));
    }

    /** @test */
    public function repository_has_find_many_method(): void
    {
        $this->assertTrue(method_exists($this->repository, 'findMany'));
    }

    /** @test */
    public function repository_has_find_by_phonetic_match_method(): void
    {
        $this->assertTrue(method_exists($this->repository, 'findByPhoneticMatch'));
    }

    /** @test */
    public function repository_has_get_by_phonetic_match_method(): void
    {
        $this->assertTrue(method_exists($this->repository, 'getByPhoneticMatch'));
    }

    /** @test */
    public function repository_has_find_by_name_method(): void
    {
        $this->assertTrue(method_exists($this->repository, 'findByName'));
    }

    /** @test */
    public function repository_has_find_by_name_like_method(): void
    {
        $this->assertTrue(method_exists($this->repository, 'findByNameLike'));
    }

    /** @test */
    public function repository_has_get_by_name_prefix_method(): void
    {
        $this->assertTrue(method_exists($this->repository, 'getByNamePrefix'));
    }

    /** @test */
    public function repository_has_get_emails_by_ids_method(): void
    {
        $this->assertTrue(method_exists($this->repository, 'getEmailsByIds'));
    }

    /** @test */
    public function repository_has_query_method(): void
    {
        $this->assertTrue(method_exists($this->repository, 'query'));
    }

    /** @test */
    public function repository_has_query_with_company_method(): void
    {
        $this->assertTrue(method_exists($this->repository, 'queryWithCompany'));
    }

    /** @test */
    public function repository_has_apply_selector_filter_method(): void
    {
        $reflection = new ReflectionClass($this->repository);
        $this->assertTrue($reflection->hasMethod('applySelectorFilter'));
    }

    /** @test */
    public function repository_has_create_method(): void
    {
        $this->assertTrue(method_exists($this->repository, 'create'));
    }

    /** @test */
    public function repository_has_update_method(): void
    {
        $this->assertTrue(method_exists($this->repository, 'update'));
    }

    /** @test */
    public function apply_selector_filter_is_protected(): void
    {
        $reflection = new ReflectionClass($this->repository);
        $method = $reflection->getMethod('applySelectorFilter');

        $this->assertTrue($method->isProtected());
    }

    /** @test */
    public function get_by_name_prefix_has_default_limit_of_ten(): void
    {
        $reflection = new ReflectionClass($this->repository);
        $method = $reflection->getMethod('getByNamePrefix');
        $params = $method->getParameters();

        $limitParam = $params[1] ?? null;
        $this->assertNotNull($limitParam);
        $this->assertEquals('limit', $limitParam->getName());
        $this->assertTrue($limitParam->isDefaultValueAvailable());
        $this->assertEquals(10, $limitParam->getDefaultValue());
    }
}

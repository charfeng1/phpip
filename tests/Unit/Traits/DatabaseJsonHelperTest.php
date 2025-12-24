<?php

namespace Tests\Unit\Traits;

use App\Traits\DatabaseJsonHelper;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseJsonHelperTest extends TestCase
{
    use DatabaseJsonHelper;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function get_db_driver_returns_string()
    {
        $driver = $this->getDbDriver();

        $this->assertIsString($driver);
        $this->assertNotEmpty($driver);
    }

    /** @test */
    public function is_postgres_returns_boolean()
    {
        $result = $this->isPostgres();

        $this->assertIsBool($result);
    }

    /** @test */
    public function json_extract_returns_expression()
    {
        $result = self::jsonExtract('column_name', 'key');

        $this->assertInstanceOf(Expression::class, $result);
    }

    /** @test */
    public function json_extract_with_alias_includes_alias()
    {
        $result = self::jsonExtract('column_name', 'key', 'my_alias');

        $sql = $result->getValue(DB::connection()->getQueryGrammar());

        $this->assertStringContainsString('my_alias', $sql);
    }

    /** @test */
    public function json_extract_produces_valid_sql_for_mysql()
    {
        // This test only makes sense for MySQL driver
        if ($this->getDbDriver() !== 'mysql') {
            $this->markTestSkipped('This test is for MySQL only');
        }

        $result = self::jsonExtract('detail', 'en');
        $sql = $result->getValue(DB::connection()->getQueryGrammar());

        $this->assertStringContainsString('JSON_UNQUOTE', $sql);
        $this->assertStringContainsString('JSON_EXTRACT', $sql);
    }

    /** @test */
    public function json_extract_produces_valid_sql_for_postgres()
    {
        // This test only makes sense for PostgreSQL driver
        if ($this->getDbDriver() !== 'pgsql') {
            $this->markTestSkipped('This test is for PostgreSQL only');
        }

        $result = self::jsonExtract('detail', 'en');
        $sql = $result->getValue(DB::connection()->getQueryGrammar());

        $this->assertStringContainsString('->>', $sql);
    }

    /** @test */
    public function json_extract_int_returns_expression()
    {
        $result = self::jsonExtractInt('column_name', 'key');

        $this->assertInstanceOf(Expression::class, $result);
    }

    /** @test */
    public function json_extract_int_casts_to_integer()
    {
        $result = self::jsonExtractInt('detail', 'en');
        $sql = $result->getValue(DB::connection()->getQueryGrammar());

        if ($this->getDbDriver() === 'pgsql') {
            $this->assertStringContainsString('INTEGER', $sql);
        } else {
            $this->assertStringContainsString('UNSIGNED', $sql);
        }
    }

    /** @test */
    public function group_concat_returns_expression()
    {
        $result = self::groupConcat('column_name');

        $this->assertInstanceOf(Expression::class, $result);
    }

    /** @test */
    public function group_concat_with_separator()
    {
        $result = self::groupConcat('name', '; ');
        $sql = $result->getValue(DB::connection()->getQueryGrammar());

        $this->assertStringContainsString('; ', $sql);
    }

    /** @test */
    public function group_concat_with_distinct()
    {
        $result = self::groupConcat('name', ',', true);
        $sql = $result->getValue(DB::connection()->getQueryGrammar());

        $this->assertStringContainsString('DISTINCT', $sql);
    }

    /** @test */
    public function group_concat_with_alias()
    {
        $result = self::groupConcat('name', ',', false, 'names');
        $sql = $result->getValue(DB::connection()->getQueryGrammar());

        $this->assertStringContainsString('names', $sql);
    }

    /** @test */
    public function group_concat_uses_string_agg_for_postgres()
    {
        if ($this->getDbDriver() !== 'pgsql') {
            $this->markTestSkipped('This test is for PostgreSQL only');
        }

        $result = self::groupConcat('name');
        $sql = $result->getValue(DB::connection()->getQueryGrammar());

        $this->assertStringContainsString('STRING_AGG', $sql);
    }

    /** @test */
    public function group_concat_uses_group_concat_for_mysql()
    {
        if ($this->getDbDriver() !== 'mysql') {
            $this->markTestSkipped('This test is for MySQL only');
        }

        $result = self::groupConcat('name');
        $sql = $result->getValue(DB::connection()->getQueryGrammar());

        $this->assertStringContainsString('GROUP_CONCAT', $sql);
    }

    /** @test */
    public function json_order_by_returns_string()
    {
        $result = self::jsonOrderBy('column_name', 'key');

        $this->assertIsString($result);
    }

    /** @test */
    public function json_order_by_for_mysql()
    {
        if ($this->getDbDriver() !== 'mysql') {
            $this->markTestSkipped('This test is for MySQL only');
        }

        $result = self::jsonOrderBy('detail', 'en');

        $this->assertStringContainsString('JSON_UNQUOTE', $result);
        $this->assertStringContainsString('JSON_EXTRACT', $result);
    }

    /** @test */
    public function json_order_by_for_postgres()
    {
        if ($this->getDbDriver() !== 'pgsql') {
            $this->markTestSkipped('This test is for PostgreSQL only');
        }

        $result = self::jsonOrderBy('detail', 'en');

        $this->assertStringContainsString('->>', $result);
    }

    /** @test */
    public function json_where_like_returns_array()
    {
        $result = self::jsonWhereLike('column_name', 'key', 'value');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    /** @test */
    public function json_where_like_includes_sql_and_bindings()
    {
        $result = self::jsonWhereLike('name', 'en', 'test');

        [$sql, $bindings] = $result;

        $this->assertIsString($sql);
        $this->assertIsArray($bindings);
    }

    /** @test */
    public function json_where_like_for_mysql()
    {
        if ($this->getDbDriver() !== 'mysql') {
            $this->markTestSkipped('This test is for MySQL only');
        }

        [$sql, $bindings] = self::jsonWhereLike('name', 'en', 'test');

        $this->assertStringContainsString('JSON_UNQUOTE', $sql);
        $this->assertStringContainsString('LIKE', $sql);
        $this->assertCount(1, $bindings);
        $this->assertEquals('%test%', $bindings[0]);
    }

    /** @test */
    public function json_where_like_for_postgres()
    {
        if ($this->getDbDriver() !== 'pgsql') {
            $this->markTestSkipped('This test is for PostgreSQL only');
        }

        [$sql, $bindings] = self::jsonWhereLike('name', 'en', 'test');

        $this->assertStringContainsString('ILIKE', $sql);
        $this->assertCount(2, $bindings);
    }
}

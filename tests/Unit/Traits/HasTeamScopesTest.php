<?php

namespace Tests\Unit\Traits;

use App\Traits\HasTeamScopes;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class HasTeamScopesTest extends TestCase
{
    /** @test */
    public function default_team_scope_column_is_responsible()
    {
        $model = new DefaultTeamScopesModel();

        $this->assertEquals('responsible', $model->getTeamScopeColumnPublic());
    }

    /** @test */
    public function default_should_include_matter_is_false()
    {
        $model = new DefaultTeamScopesModel();

        $this->assertFalse($model->shouldIncludeMatterInTeamScopePublic());
    }

    /** @test */
    public function can_override_team_scope_column()
    {
        $model = new CustomTeamScopesModel();

        $this->assertEquals('assigned_to', $model->getTeamScopeColumnPublic());
    }

    /** @test */
    public function can_override_should_include_matter()
    {
        $model = new CustomTeamScopesModel();

        $this->assertTrue($model->shouldIncludeMatterInTeamScopePublic());
    }

    // Note: Tests for scopeForTeam/scopeForUser require Laravel's container
    // and database access. These are covered in feature tests.
}

/**
 * Test model with default HasTeamScopes configuration.
 */
class DefaultTeamScopesModel extends Model
{
    use HasTeamScopes;

    public function getTeamScopeColumnPublic(): string
    {
        return $this->getTeamScopeColumn();
    }

    public function shouldIncludeMatterInTeamScopePublic(): bool
    {
        return $this->shouldIncludeMatterInTeamScope();
    }
}

/**
 * Test model with custom HasTeamScopes configuration.
 */
class CustomTeamScopesModel extends Model
{
    use HasTeamScopes;

    protected function getTeamScopeColumn(): string
    {
        return 'assigned_to';
    }

    protected function shouldIncludeMatterInTeamScope(): bool
    {
        return true;
    }

    public function getTeamScopeColumnPublic(): string
    {
        return $this->getTeamScopeColumn();
    }

    public function shouldIncludeMatterInTeamScopePublic(): bool
    {
        return $this->shouldIncludeMatterInTeamScope();
    }
}

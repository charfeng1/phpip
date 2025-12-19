# Issue: Add tests for SQL injection fixes

**Labels:** testing, security

## Context

SQL injection vulnerabilities were fixed by converting string concatenation to parameterized queries. Tests should verify these fixes.

## Test Cases Needed

### 1. Matter filtering with malicious input
```php
public function test_matter_filter_escapes_sql_injection()
{
    $maliciousInput = "'; DROP TABLE matters; --";

    $response = $this->actingAs($user)
        ->get('/matter?Ref=' . urlencode($maliciousInput));

    $response->assertStatus(200);
    $this->assertDatabaseHas('matter', ['id' => $existingMatter->id]);
}
```

### 2. Actor search with special characters
```php
public function test_actor_search_handles_special_characters()
{
    $input = "O'Brien & Associates";

    $response = $this->actingAs($user)
        ->get('/actor?name=' . urlencode($input));

    $response->assertStatus(200);
}
```

### 3. JSON field queries
```php
public function test_json_search_parameterized()
{
    $maliciousInput = '{"en": "\'); DROP TABLE --"}';

    // Should not throw SQL error
    $response = $this->get('/country?name=' . urlencode($maliciousInput));
    $response->assertStatus(200);
}
```

## Priority
Low - fixes are straightforward parameterization, code review sufficient

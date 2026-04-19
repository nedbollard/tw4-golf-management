# TW4 Golf Management System - Testing Guide

## Testing Framework Setup

This project uses PHPUnit for unit and integration testing.

## Running Tests

### Install Dependencies
```bash
composer install --dev
```

### Run All Tests
```bash
./vendor/bin/phpunit
```

### Run Specific Test Suite
```bash
# Unit tests only
./vendor/bin/phpunit --testsuite Unit

# Integration tests only
./vendor/bin/phpunit --testsuite Integration
```

### Run Specific Test File
```bash
./vendor/bin/phpunit tests/Unit/StaffTest.php
```

### Run Tests with Coverage
```bash
./vendor/bin/phpunit --coverage-html coverage
```

### Run Migration Replay + Schema Parity Test
```bash
./test-runner.sh migrations
```

This command:
- creates a temporary database `tw4_migration_test`
- replays canonical SQL migrations from `src/migrations` (excluding `999_current_schema.sql`)
- compares table inventory and `SHOW CREATE TABLE` output against `TW4`
- ignores `AUTO_INCREMENT` numeric drift during comparison

## Test Structure

### Unit Tests (`tests/Unit/`)
- **StaffTest.php** - Tests Staff model methods and properties
- **AuthServiceTest.php** - Tests authentication service functionality
- **DatabaseTest.php** - Tests database operations
- **StaffControllerTest.php** - Tests controller validation logic

### Integration Tests (`tests/Integration/`)
- **StaffIntegrationTest.php** - Tests complete staff management workflow

### Bootstrap Tests
- **BootstrapTest.php** - Tests that autoloading and basic setup works

## Test Database

Tests use a separate test database `tw4_test` to avoid affecting production data.

### Setup Test Database
```bash
docker compose exec -e MYSQL_PWD=${DB_PASSWORD} db mysql -u root -e "CREATE DATABASE tw4_test;"
```

### Run Migrations on Test Database
```bash
docker compose exec -e MYSQL_PWD=${DB_PASSWORD} db mysql -u root tw4_test < src/migrations/001_create_tables.sql
```

## Writing New Tests

### Unit Test Example
```php
public function testMethodName(): void
{
    // Arrange - Set up test data
    $expected = 'expected_value';
    
    // Act - Execute the method being tested
    $actual = $object->method();
    
    // Assert - Verify the result
    $this->assertEquals($expected, $actual);
}
```

### Integration Test Example
```php
public function testCompleteWorkflow(): void
{
    // Create test data
    $staff = new Staff('test', 'hash', 'Test', 'User', 'admin', true, null);
    
    // Save to database
    $id = $staff->save($this->database);
    
    // Retrieve and verify
    $retrieved = Staff::findById($this->database, $id);
    $this->assertEquals('test', $retrieved->getUsername());
}
```

## Best Practices

1. **Test Naming**: Use descriptive test names that explain what is being tested
2. **Arrange-Act-Assert**: Structure tests in this pattern
3. **Isolation**: Each test should be independent and not rely on other tests
4. **Cleanup**: Clean up test data in tearDown() methods
5. **Mock Dependencies**: Use mocks for external dependencies in unit tests
6. **Test Coverage**: Aim for high test coverage of critical business logic

## Continuous Integration

Add this to your CI/CD pipeline:

```yaml
- name: Run Tests
  run: |
    composer install --dev
    ./vendor/bin/phpunit --coverage-clover coverage.xml
```

## Test Commands Reference

```bash
# Run all tests with verbose output
./vendor/bin/phpunit -v

# Run tests and generate coverage report
./vendor/bin/phpunit --coverage-text --coverage-html=coverage

# Run tests in parallel (if installed)
./vendor/bin/phpunit --parallel

# Run migration replay/schema parity check
./test-runner.sh migrations

# Filter tests by name
./vendor/bin/phpunit --filter testStaffCreation

# Run tests with specific group
./vendor/bin/phpunit --group staff
```

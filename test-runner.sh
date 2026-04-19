#!/bin/bash

# TW4 Test Runner - Comprehensive Testing Script
# Author: Ned Bollard
# Description: Run all TW4 tests with proper setup and reporting

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Load DB_PASSWORD from .env when not explicitly provided by caller.
if [ -z "${DB_PASSWORD-}" ] && [ -f ".env" ]; then
    DB_PASSWORD="$(awk -F= '/^DB_PASSWORD=/{print substr($0, index($0,"=")+1); exit}' .env)"
fi

: "${DB_PASSWORD:?DB_PASSWORD is required (set it in .env or export it)}"

print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_header() {
    echo -e "${BLUE}================================${NC}"
    echo -e "${CYAN}TW4 Test Runner${NC}"
    echo -e "${BLUE}================================${NC}"
}

# Check if we're in the right directory
check_directory() {
    if [ ! -f "phpunit.xml" ]; then
        print_error "phpunit.xml not found. Please run from TW4 root directory."
        exit 1
    fi
    
    if [ ! -d "tests" ]; then
        print_error "tests directory not found."
        exit 1
    fi
}

# Check if Docker containers are running
check_docker() {
    if ! docker compose ps | grep -q "Up"; then
        print_warning "Docker containers are not running. Starting them..."
        ./tw4-manage.sh start
        sleep 5
    fi
}

# Validate DB credentials before running setup or parity checks.
validate_db_connection() {
    if ! docker compose exec -T -e MYSQL_PWD="$DB_PASSWORD" db mysql -u root -e "SELECT 1;" >/dev/null 2>&1; then
        print_error "Cannot connect to MySQL with the provided DB_PASSWORD."
        print_error "Set DB_PASSWORD in .env (or export it) to match docker-compose MySQL credentials."
        return 1
    fi
    return 0
}

# Ensure TW4_base has schema when running against a freshly recreated volume.
ensure_reference_database_schema() {
    local table_count
    table_count=$(docker compose exec -T -e MYSQL_PWD="$DB_PASSWORD" db mysql -N -s -u root -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='TW4_base' AND table_type='BASE TABLE';" 2>/dev/null)

    if [ "$table_count" = "0" ]; then
        print_status "TW4_base is empty; applying baseline migrations..."
        for migration in $(ls src/migrations/*.sql | grep -v '017_create_live_database_schema.sql' | grep -v '018_seed_live_round.sql' | grep -v '019_round_workflow_and_lock.sql' | grep -v '021_live_round_start_defaults.sql' | grep -v '022_live_card_tables.sql' | grep -v '999_current_schema.sql' | sort); do
            print_status "Applying $(basename "$migration") to TW4_base..."
            if ! docker compose exec -T -e MYSQL_PWD="$DB_PASSWORD" db mysql -u root TW4_base < "$migration"; then
                print_error "Failed applying $(basename "$migration") to TW4_base."
                return 1
            fi
        done
    fi

    return 0
}

# Check test database
check_test_database() {
    print_status "Checking test database..."

    # Always recreate test DB to avoid stale schema from prior runs.
    print_status "Recreating test database..."
    if ! docker compose exec -T -e MYSQL_PWD="$DB_PASSWORD" db mysql -u root -e "DROP DATABASE IF EXISTS tw4_test; CREATE DATABASE tw4_test;"; then
        print_error "Failed to recreate tw4_test database."
        print_error "Check DB_PASSWORD in .env or export DB_PASSWORD before running tests."
        return 1
    fi

    # Apply migrations to test database
    print_status "Applying migrations to test database..."
    for migration in src/migrations/*.sql; do
        if [ -f "$migration" ]; then
            if [ "$(basename "$migration")" = "017_create_live_database_schema.sql" ] || [ "$(basename "$migration")" = "018_seed_live_round.sql" ] || [ "$(basename "$migration")" = "019_round_workflow_and_lock.sql" ] || [ "$(basename "$migration")" = "021_live_round_start_defaults.sql" ] || [ "$(basename "$migration")" = "022_live_card_tables.sql" ]; then
                continue
            fi
            print_status "Applying $(basename "$migration")..."
            if ! docker compose exec -T -e MYSQL_PWD="$DB_PASSWORD" db mysql -u root tw4_test < "$migration"; then
                print_error "Failed applying migration $(basename "$migration") to tw4_test."
                return 1
            fi
        fi
    done

    return 0
}

# Run unit tests only
run_unit_tests() {
    print_status "Running Unit Tests..."
    echo ""
    
    docker compose exec -T app ./vendor/bin/phpunit --testsuite Unit --color=always
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}Unit Tests: PASSED${NC}"
        return 0
    else
        echo -e "${RED}Unit Tests: FAILED${NC}"
        return 1
    fi
}

# Run integration tests only
run_integration_tests() {
    print_status "Running Integration Tests..."
    echo ""
    
    docker compose exec -T app ./vendor/bin/phpunit --testsuite Integration --color=always
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}Integration Tests: PASSED${NC}"
        return 0
    else
        echo -e "${RED}Integration Tests: FAILED${NC}"
        return 1
    fi
}

# Run all tests
run_all_tests() {
    print_status "Running All Tests..."
    echo ""
    
    docker compose exec -T app ./vendor/bin/phpunit --color=always
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}All Tests: PASSED${NC}"
        return 0
    else
        echo -e "${RED}All Tests: FAILED${NC}"
        return 1
    fi
}

# Run tests with coverage
run_tests_with_coverage() {
    print_status "Running Tests with Coverage..."
    echo ""
    
    docker compose exec -T app ./vendor/bin/phpunit --color=always --coverage-html=coverage
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}Tests with Coverage: PASSED${NC}"
        echo -e "${CYAN}Coverage report: coverage/index.html${NC}"
        return 0
    else
        echo -e "${RED}Tests with Coverage: FAILED${NC}"
        return 1
    fi
}

# Run specific test file
run_specific_test() {
    local test_file="$1"
    
    if [ ! -f "$test_file" ]; then
        print_error "Test file not found: $test_file"
        return 1
    fi
    
    print_status "Running specific test: $test_file"
    echo ""
    
    docker compose exec -T app ./vendor/bin/phpunit "$test_file" --color=always
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}Test: PASSED${NC}"
        return 0
    else
        echo -e "${RED}Test: FAILED${NC}"
        return 1
    fi
}

# Show test statistics
show_test_stats() {
    print_status "Test Statistics..."
    echo ""
    
    echo -e "${CYAN}Available Tests:${NC}"
    echo "Unit Tests:"
    find tests/Unit -name "*.php" -exec echo "  - {}" \;
    echo ""
    echo "Integration Tests:"
    find tests/Integration -name "*.php" -exec echo "  - {}" \;
    echo ""
    
    echo -e "${CYAN}Test Configuration:${NC}"
    echo "  PHPUnit Config: phpunit.xml"
    echo "  Test Database: tw4_test"
    echo "  Test Environment: testing"
    echo ""
    
    echo -e "${CYAN}Docker Status:${NC}"
    ./tw4-manage.sh status
}

# Clean test artifacts
clean_tests() {
    print_status "Cleaning test artifacts..."
    
    # Clean coverage reports
    rm -rf coverage/
    
    # Clean PHPUnit cache
    rm -rf .phpunit.cache/
    
    # Clean test logs
    rm -f tests.log
    
    print_status "Test artifacts cleaned"
}

# Run migration replay and schema parity test
run_migration_schema_test() {
    print_status "Running migration replay/schema parity test..."

    local temp_db="tw4_migration_test"
    local migrate_log
    migrate_log=$(mktemp)
    local tw4_tables
    tw4_tables=$(mktemp)
    local test_tables
    test_tables=$(mktemp)
    local tw4_schema
    tw4_schema=$(mktemp)
    local test_schema
    test_schema=$(mktemp)

    # Ensure temp DB exists fresh
    if ! docker compose exec -T -e MYSQL_PWD="$DB_PASSWORD" db mysql -u root -e "DROP DATABASE IF EXISTS ${temp_db}; CREATE DATABASE ${temp_db};" >/dev/null 2>&1; then
        print_error "Failed to create temporary database ${temp_db}."
        print_error "Check DB_PASSWORD in .env or export DB_PASSWORD before running tests."
        rm -f "$migrate_log" "$tw4_tables" "$test_tables" "$tw4_schema" "$test_schema"
        return 1
    fi

    # Replay canonical migration chain (exclude snapshot schema)
    for migration in $(ls src/migrations/*.sql | grep -v '999_current_schema.sql' | sort); do
        if [ "$(basename "$migration")" = "017_create_live_database_schema.sql" ] || [ "$(basename "$migration")" = "018_seed_live_round.sql" ] || [ "$(basename "$migration")" = "019_round_workflow_and_lock.sql" ] || [ "$(basename "$migration")" = "021_live_round_start_defaults.sql" ] || [ "$(basename "$migration")" = "022_live_card_tables.sql" ]; then
            continue
        fi
        echo "Applying $(basename "$migration")" >> "$migrate_log"
        if ! docker compose exec -T -e MYSQL_PWD="$DB_PASSWORD" db mysql -u root "$temp_db" < "$migration" >> "$migrate_log" 2>&1; then
            print_error "Migration replay failed at $(basename "$migration")."
            cat "$migrate_log"
            rm -f "$migrate_log" "$tw4_tables" "$test_tables" "$tw4_schema" "$test_schema"
            return 1
        fi
    done

    # Compare table sets
    if ! docker compose exec -T -e MYSQL_PWD="$DB_PASSWORD" db mysql -u root -e "USE TW4_base; SHOW TABLES;" | tail -n +2 | sort > "$tw4_tables"; then
        print_error "Failed to read TW4_base table list."
        rm -f "$migrate_log" "$tw4_tables" "$test_tables" "$tw4_schema" "$test_schema"
        return 1
    fi

    if ! docker compose exec -T -e MYSQL_PWD="$DB_PASSWORD" db mysql -u root -e "USE ${temp_db}; SHOW TABLES;" | tail -n +2 | sort > "$test_tables"; then
        print_error "Failed to read ${temp_db} table list."
        rm -f "$migrate_log" "$tw4_tables" "$test_tables" "$tw4_schema" "$test_schema"
        return 1
    fi

    if ! diff -u "$tw4_tables" "$test_tables" >/dev/null; then
        print_error "Table set mismatch between TW4 and ${temp_db}."
        diff -u "$tw4_tables" "$test_tables"
        rm -f "$migrate_log" "$tw4_tables" "$test_tables" "$tw4_schema" "$test_schema"
        return 1
    fi

    # Compare full CREATE TABLE output while normalizing AUTO_INCREMENT drift
    while IFS= read -r table_name; do
        if ! docker compose exec -T -e MYSQL_PWD="$DB_PASSWORD" db mysql -u root -e "USE TW4_base; SHOW CREATE TABLE ${table_name};" \
            | sed -E 's/ AUTO_INCREMENT=[0-9]+//g' >> "$tw4_schema"; then
            print_error "Failed to read SHOW CREATE TABLE for TW4_base.${table_name}"
            rm -f "$migrate_log" "$tw4_tables" "$test_tables" "$tw4_schema" "$test_schema"
            return 1
        fi

        if ! docker compose exec -T -e MYSQL_PWD="$DB_PASSWORD" db mysql -u root -e "USE ${temp_db}; SHOW CREATE TABLE ${table_name};" \
            | sed -E 's/ AUTO_INCREMENT=[0-9]+//g' >> "$test_schema"; then
            print_error "Failed to read SHOW CREATE TABLE for ${temp_db}.${table_name}"
            rm -f "$migrate_log" "$tw4_tables" "$test_tables" "$tw4_schema" "$test_schema"
            return 1
        fi
    done < "$tw4_tables"

    if ! diff -u "$tw4_schema" "$test_schema" >/dev/null; then
        print_error "Schema mismatch found between TW4 and ${temp_db}."
        diff -u "$tw4_schema" "$test_schema"
        rm -f "$migrate_log" "$tw4_tables" "$test_tables" "$tw4_schema" "$test_schema"
        return 1
    fi

    print_status "Migration replay/schema parity test passed"

    rm -f "$migrate_log" "$tw4_tables" "$test_tables" "$tw4_schema" "$test_schema"
    return 0
}

# Show help
show_help() {
    print_header
    echo -e "${PURPLE}TW4 Test Runner Commands:${NC}"
    echo ""
    echo -e "${GREEN}  all${NC}           - Run all tests (Unit + Integration)"
    echo -e "${GREEN}  unit${NC}          - Run unit tests only"
    echo -e "${GREEN}  integration${NC}   - Run integration tests only"
    echo -e "${GREEN}  coverage${NC}      - Run tests with coverage report"
    echo -e "${GREEN}  migrations${NC}    - Replay migrations and compare schema parity"
    echo -e "${GREEN}  specific <file>${NC} - Run specific test file"
    echo -e "${GREEN}  stats${NC}         - Show test statistics"
    echo -e "${GREEN}  clean${NC}         - Clean test artifacts"
    echo -e "${GREEN}  help${NC}          - Show this help menu"
    echo ""
    echo -e "${YELLOW}Examples:${NC}"
    echo "  ./test-runner.sh all                    # Run all tests"
    echo "  ./test-runner.sh unit                   # Unit tests only"
    echo "  ./test-runner.sh specific tests/Unit/StaffTest.php"
    echo ""
    echo -e "${YELLOW}Test Files:${NC}"
    echo "  Unit Tests:"
    echo "    - tests/Unit/StaffTest.php"
    echo "    - tests/Unit/AuthServiceTest.php"
    echo "    - tests/Unit/DatabaseTest.php"
    echo "    - tests/Unit/StaffControllerTest.php"
    echo "  Integration Tests:"
    echo "    - tests/Integration/StaffIntegrationTest.php"
    echo ""
}

# Main script logic
main() {
    check_directory
    
    case "${1:-help}" in
        "all")
            check_docker && validate_db_connection && ensure_reference_database_schema && check_test_database && run_migration_schema_test && run_all_tests
            ;;
        "unit")
            check_docker && validate_db_connection && ensure_reference_database_schema && check_test_database && run_unit_tests
            ;;
        "integration")
            check_docker && validate_db_connection && ensure_reference_database_schema && check_test_database && run_integration_tests
            ;;
        "coverage")
            check_docker && validate_db_connection && ensure_reference_database_schema && check_test_database && run_tests_with_coverage
            ;;
        "migrations")
            check_docker && validate_db_connection && ensure_reference_database_schema && run_migration_schema_test
            ;;
        "specific")
            if [ -z "$2" ]; then
                print_error "Please specify a test file"
                echo "Usage: ./test-runner.sh specific <test_file>"
                exit 1
            fi
            check_docker && validate_db_connection && ensure_reference_database_schema && check_test_database && run_specific_test "$2"
            ;;
        "stats")
            show_test_stats
            ;;
        "clean")
            clean_tests
            ;;
        "help"|"-h"|"--help")
            show_help
            ;;
        *)
            print_error "Unknown command: $1"
            echo ""
            show_help
            exit 1
            ;;
    esac
}

# Run main function with all arguments
main "$@"

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

# Check test database
check_test_database() {
    print_status "Checking test database..."
    
    # Check if test database exists
    if ! docker compose exec -T db mysql -u root -psecretpassword -e "USE tw4_test;" 2>/dev/null; then
        print_status "Creating test database..."
        docker compose exec -T db mysql -u root -psecretpassword -e "CREATE DATABASE tw4_test;"
        
        # Apply migrations to test database
        print_status "Applying migrations to test database..."
        for migration in src/migrations/*.sql; do
            if [ -f "$migration" ]; then
                print_status "Applying $(basename "$migration")..."
                docker compose exec -T db mysql -u root -psecretpassword tw4_test < "$migration"
            fi
        done
    fi
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

# Show help
show_help() {
    print_header
    echo -e "${PURPLE}TW4 Test Runner Commands:${NC}"
    echo ""
    echo -e "${GREEN}  all${NC}           - Run all tests (Unit + Integration)"
    echo -e "${GREEN}  unit${NC}          - Run unit tests only"
    echo -e "${GREEN}  integration${NC}   - Run integration tests only"
    echo -e "${GREEN}  coverage${NC}      - Run tests with coverage report"
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
            check_docker
            check_test_database
            run_all_tests
            ;;
        "unit")
            check_docker
            check_test_database
            run_unit_tests
            ;;
        "integration")
            check_docker
            check_test_database
            run_integration_tests
            ;;
        "coverage")
            check_docker
            check_test_database
            run_tests_with_coverage
            ;;
        "specific")
            if [ -z "$2" ]; then
                print_error "Please specify a test file"
                echo "Usage: ./test-runner.sh specific <test_file>"
                exit 1
            fi
            check_docker
            check_test_database
            run_specific_test "$2"
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

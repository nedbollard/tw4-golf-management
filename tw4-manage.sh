#!/bin/bash

# TW4 Golf Management System - Management Scripts
# Author: Ned Bollard
# Description: Comprehensive application management scripts

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Function to print colored output
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
    echo -e "${CYAN}TW4 Golf Management System${NC}"
    echo -e "${BLUE}================================${NC}"
}

# Check if we're in the right directory
check_directory() {
    if [ ! -f "docker-compose.yml" ]; then
        print_error "docker-compose.yml not found. Please run from project root."
        exit 1
    fi
}

# Show help menu
show_help() {
    print_header
    echo -e "${PURPLE}Available Commands:${NC}"
    echo -e "${GREEN}  start${NC}     - Start TW4 application (Docker containers)"
    echo -e "${GREEN}  stop${NC}      - Stop TW4 application (Docker containers)"
    echo -e "${GREEN}  restart${NC}    - Restart TW4 application"
    echo -e "${GREEN}  status${NC}     - Show status of Docker containers"
    echo -e "${GREEN}  logs${NC}       - Show application logs"
    echo -e "${GREEN}  test${NC}       - Run unit tests"
    echo -e "${GREEN}  deploy${NC}     - Deploy changes to GitHub"
    echo -e "${GREEN}  db${NC}         - Connect to database"
    echo -e "${GREEN}  clean${NC}      - Clean up Docker containers and images"
    echo -e "${GREEN}  backup${NC}     - Create database backup"
    echo -e "${GREEN}  help${NC}       - Show this help menu"
    echo ""
}

# Start application
start_app() {
    print_status "Starting TW4 application..."
    check_directory
    
    # Check if containers are already running
    if docker compose ps | grep -q "Up"; then
        print_warning "Containers are already running!"
        return
    fi
    
    # Start containers
    docker compose up -d
    
    if [ $? -eq 0 ]; then
        print_status "✅ TW4 application started successfully!"
        echo -e "${GREEN}Application URLs:${NC}"
        echo -e "${CYAN}  TW4 App:${NC} http://localhost:8084"
        echo -e "${CYAN}  phpMyAdmin:${NC} http://localhost:8085"
        echo ""
        echo -e "${GREEN}Database Access:${NC}"
        echo -e "${CYAN}  Host:${NC} localhost"
        echo -e "${CYAN}  Database:${NC} TW4"
        echo -e "${CYAN}  User:${NC} root"
        echo ""
        echo -e "${GREEN}Admin Credentials:${NC}"
        echo -e "${CYAN}  Username:${NC} admin"
        echo -e "${CYAN}  Password:${NC} hash_house"
    else
        print_error "❌ Failed to start application!"
        exit 1
    fi
}

# Stop application
stop_app() {
    print_status "Stopping TW4 application..."
    check_directory
    
    docker compose down
    
    if [ $? -eq 0 ]; then
        print_status "✅ TW4 application stopped successfully!"
    else
        print_error "❌ Failed to stop application!"
        exit 1
    fi
}

# Restart application
restart_app() {
    print_status "Restarting TW4 application..."
    stop_app
    sleep 2
    start_app
}

# Show status
show_status() {
    print_status "TW4 Application Status:"
    check_directory
    
    echo ""
    docker compose ps
    echo ""
    
    # Check if main services are running
    if docker compose ps | grep -q "app.*Up"; then
        echo -e "${GREEN}✅ Web Application: Running${NC}"
    else
        echo -e "${RED}❌ Web Application: Stopped${NC}"
    fi
    
    if docker compose ps | grep -q "db.*Up"; then
        echo -e "${GREEN}✅ Database: Running${NC}"
    else
        echo -e "${RED}❌ Database: Stopped${NC}"
    fi
    
    if docker compose ps | grep -q "phpmyadmin.*Up"; then
        echo -e "${GREEN}✅ phpMyAdmin: Running${NC}"
    else
        echo -e "${YELLOW}⚠️  phpMyAdmin: Stopped${NC}"
    fi
}

# Show logs
show_logs() {
    print_status "Showing TW4 application logs..."
    check_directory
    
    echo -e "${CYAN}Press Ctrl+C to exit logs${NC}"
    echo ""
    docker compose logs -f app
}

# Run tests
run_tests() {
    print_status "Running unit tests..."
    check_directory
    
    # Ensure containers are running
    if ! docker compose ps | grep -q "app.*Up"; then
        print_warning "Starting containers for testing..."
        docker compose up -d
        sleep 5
    fi
    
    # Run tests
    docker compose exec app ./vendor/bin/phpunit
    
    if [ $? -eq 0 ]; then
        print_status "✅ All tests passed!"
    else
        print_error "❌ Some tests failed!"
        exit 1
    fi
}

# Deploy changes
deploy_changes() {
    print_status "Deploying changes to GitHub..."
    check_directory
    
    if [ -f "deploy.sh" ]; then
        ./deploy.sh "$1"
    else
        print_error "deploy.sh script not found!"
        exit 1
    fi
}

# Connect to database
connect_db() {
    print_status "Connecting to database..."
    check_directory
    
    # Ensure database container is running
    if ! docker compose ps | grep -q "db.*Up"; then
        print_warning "Starting database container..."
        docker compose up -d db
        sleep 5
    fi
    
    echo -e "${CYAN}Connecting to MySQL database...${NC}"
    docker compose exec db mysql -uroot -psecretpassword TW4
}

# Clean up
clean_up() {
    print_status "Cleaning up Docker environment..."
    check_directory
    
    echo -e "${YELLOW}Stopping and removing containers...${NC}"
    docker compose down -v --remove-orphans
    
    echo -e "${YELLOW}Removing unused Docker images...${NC}"
    docker image prune -f
    
    echo -e "${YELLOW}Removing Docker volumes (be careful!)...${NC}"
    docker volume prune -f
    
    if [ $? -eq 0 ]; then
        print_status "✅ Cleanup completed!"
    else
        print_error "❌ Cleanup failed!"
        exit 1
    fi
}

# Create backup
create_backup() {
    print_status "Creating database backup..."
    check_directory
    
    # Ensure database is running
    if ! docker compose ps | grep -q "db.*Up"; then
        print_warning "Starting database for backup..."
        docker compose up -d db
        sleep 5
    fi
    
    # Create backup filename with timestamp
    BACKUP_FILE="backup/tw4_backup_$(date +%Y%m%d_%H%M%S).sql"
    
    # Create backup directory if it doesn't exist
    mkdir -p backup
    
    # Create backup
    docker compose exec -T db mysqldump -uroot -psecretpassword TW4 > "$BACKUP_FILE"
    
    if [ $? -eq 0 ]; then
        print_status "✅ Backup created: $BACKUP_FILE"
        
        # Compress backup
        gzip "$BACKUP_FILE"
        print_status "✅ Backup compressed: ${BACKUP_FILE}.gz"
    else
        print_error "❌ Backup failed!"
        exit 1
    fi
}

# Main script logic
main() {
    case "${1:-help}" in
        "start")
            start_app
            ;;
        "stop")
            stop_app
            ;;
        "restart")
            restart_app
            ;;
        "status")
            show_status
            ;;
        "logs")
            show_logs
            ;;
        "test")
            run_tests
            ;;
        "deploy")
            deploy_changes "$2"
            ;;
        "db")
            connect_db
            ;;
        "clean")
            clean_up
            ;;
        "backup")
            create_backup
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

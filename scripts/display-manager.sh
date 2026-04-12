#!/usr/bin/env bash

# TW4 Display Manager - Master Script
# Author: Ned Bollard
# Description: Central management for all display tools

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

print_header() {
    echo -e "${BLUE}================================${NC}"
    echo -e "${CYAN}TW4 Display Manager${NC}"
    echo -e "${BLUE}================================${NC}"
}

# Show current status
show_status() {
    print_status "Checking current display status..."
    ./display-safe.sh status
}

# Safe triple monitor setup
triple() {
    print_status "Setting up triple monitors (safe mode)..."
    ./display-safe.sh triple
}

# Safe dual monitor setup
dual() {
    print_status "Setting up dual monitors (safe mode)..."
    ./display-safe.sh dual
}

# Laptop only
laptop() {
    print_status "Setting up laptop display only..."
    ./display-safe.sh laptop
}

# HDMI force setup
hdmi_force() {
    print_status "Force-activating HDMI displays..."
    ./display-hdmi.sh force
}

# HDMI triple setup
hdmi_triple() {
    print_status "Setting up HDMI triple monitors..."
    ./display-hdmi.sh triple
}

# Run diagnostics
diagnose() {
    print_status "Running display diagnostics..."
    echo ""
    echo -e "${CYAN}Connectivity Check:${NC}"
    ./display-diagnostic.sh connectivity
    echo ""
    echo -e "${CYAN}Activation Test:${NC}"
    ./display-diagnostic.sh activation
    echo ""
}

# Show all available scripts
list_scripts() {
    print_header
    echo -e "${PURPLE}Available Display Scripts:${NC}"
    echo ""
    echo -e "${GREEN}Core Scripts:${NC}"
    echo "  display-safe.sh        - Safe display management (works with GNOME)"
    echo "  display-hdmi.sh        - HDMI-specific management"
    echo "  display-diagnostic.sh   - Diagnostic and troubleshooting tools"
    echo "  display-persistent.sh  - Persistent configuration (bypasses GNOME)"
    echo "  display-setup.sh        - Original setup script"
    echo "  test-reboot.sh        - Reboot testing tools"
    echo ""
    echo -e "${YELLOW}Usage Examples:${NC}"
    echo "  ./display-manager.sh triple    # Safe 3-monitor setup"
    echo "  ./display-manager.sh diagnose   # Run full diagnostics"
    echo "  ./display-manager.sh hdmi-force # Force HDMI activation"
    echo ""
}

# Show help
show_help() {
    print_header
    echo -e "${PURPLE}Display Manager Commands:${NC}"
    echo -e "${GREEN}  status${NC}      - Show current display status"
    echo -e "${GREEN}  triple${NC}      - Safe triple monitor setup"
    echo -e "${GREEN}  dual${NC}        - Safe dual monitor setup"
    echo -e "${GREEN}  laptop${NC}      - Laptop display only"
    echo -e "${GREEN}  hdmi-force${NC} - Force HDMI activation"
    echo -e "${GREEN}  hdmi-triple${NC} - HDMI triple setup"
    echo -e "${GREEN}  diagnose${NC}    - Run full diagnostics"
    echo -e "${GREEN}  list${NC}        - List all available scripts"
    echo -e "${GREEN}  help${NC}        - Show this help menu"
    echo ""
    echo -e "${YELLOW}Quick Start:${NC}"
    echo "  ./display-manager.sh triple    # Most common use case"
    echo ""
}

# Main script logic
main() {
    case "${1:-help}" in
        "status")
            show_status
            ;;
        "triple")
            triple
            ;;
        "dual")
            dual
            ;;
        "laptop")
            laptop
            ;;
        "hdmi-force")
            hdmi_force
            ;;
        "hdmi-triple")
            hdmi_triple
            ;;
        "diagnose")
            diagnose
            ;;
        "list")
            list_scripts
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

main "$@"

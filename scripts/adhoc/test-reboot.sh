#!/bin/bash

# TW4 Reboot Test Script
# Author: Ned Bollard
# Description: Test display configuration after reboot

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

print_header() {
    echo -e "${BLUE}================================${NC}"
    echo -e "${CYAN}TW4 Reboot Test${NC}"
    echo -e "${BLUE}================================${NC}"
}

# Save current state
save_current_state() {
    print_status "Saving current display state for comparison..."
    
    xrandr --current > /tmp/display_before_reboot.txt
    ./display-persistent.sh status > /tmp/persistent_status_before_reboot.txt
    
    print_status "✅ Current state saved"
}

# Test after reboot
test_after_reboot() {
    print_status "Testing display configuration after reboot..."
    
    echo -e "${CYAN}Current Display State:${NC}"
    xrandr --current
    echo ""
    
    echo -e "${CYAN}Expected vs Actual:${NC}"
    if [ -f /tmp/display_before_reboot.txt ]; then
        echo -e "${YELLOW}Before reboot:${NC}"
        cat /tmp/display_before_reboot.txt
    fi
    
    echo ""
    echo -e "${CYAN}Persistent Setup Status:${NC}"
    if [ -f /tmp/persistent_status_before_reboot.txt ]; then
        echo -e "${YELLOW}Before reboot:${NC}"
        cat /tmp/persistent_status_before_reboot.txt
    fi
    
    echo ""
    
    # Check if displays are working
    if xrandr --listactivemonitors | grep -q "DP-3.*connected" && xrandr --listactivemonitors | grep -q "DP-4.*connected"; then
        echo -e "${GREEN}✅ External displays are working${NC}"
    else
        echo -e "${RED}❌ External displays not detected${NC}"
    fi
    
    # Cleanup
    rm -f /tmp/display_before_reboot.txt
    rm -f /tmp/persistent_status_before_reboot.txt
}

# Show help
show_help() {
    print_header
    echo -e "${PURPLE}Reboot Test Commands:${NC}"
    echo -e "${GREEN}  save${NC}        - Save current state before reboot"
    echo -e "${GREEN}  test${NC}        - Test configuration after reboot"
    echo -e "${GREEN}  help${NC}         - Show this help menu"
    echo ""
}

# Main script logic
main() {
    case "${1:-help}" in
        "save")
            save_current_state
            ;;
        "test")
            test_after_reboot
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

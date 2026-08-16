#!/bin/bash

# TW4 Display Diagnostic Script
# Author: Ned Bollard
# Description: Diagnose display issues with Kensington dock

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

print_header() {
    echo -e "${BLUE}================================${NC}"
    echo -e "${CYAN}TW4 Display Diagnostics${NC}"
    echo -e "${BLUE}================================${NC}"
}

# Check display connectivity
check_connectivity() {
    print_header
    echo -e "${PURPLE}Checking Display Connectivity...${NC}"
    echo ""
    
    # Check if displays are connected but not active
    echo -e "${CYAN}Display Connection Status:${NC}"
    for output in $(xrandr --listactivemonitors | grep -E "(DP|HDMI|eDP)" | cut -d" " -f1); do
        if [ "$output" != "eDP-1" ]; then
            # Check if external display has signal
            if xrandr --query --output "$output" | grep -q "connected"; then
                echo -e "${GREEN}✅ $output: Connected and configured${NC}"
            else
                echo -e "${RED}❌ $output: Not receiving signal${NC}"
            fi
        fi
    done
    echo ""
}

# Check display capabilities
check_capabilities() {
    print_status "Checking display capabilities..."
    echo ""
    
    # List all available modes for external displays
    echo -e "${CYAN}Available Display Modes:${NC}"
    for output in $(xrandr --listmonitors | grep -E "(DP|HDMI)" | cut -d" " -f1); do
        if [ "$output" != "eDP-1" ]; then
            echo -e "${YELLOW}$output modes:${NC}"
            xrandr --query --output "$output" | grep -E "Mode:" | head -5
        fi
    done
    echo ""
}

# Test display activation
test_activation() {
    print_status "Testing display activation..."
    echo ""
    
    # Try to activate external displays one by one
    echo -e "${CYAN}Testing DP-3 activation...${NC}"
    if xrandr --output DP-3 --mode 1920x1080 --rate 60; then
        echo -e "${GREEN}✅ DP-3: Successfully activated${NC}"
    else
        echo -e "${RED}❌ DP-3: Failed to activate${NC}"
    fi
    
    sleep 2
    
    echo -e "${CYAN}Testing DP-4 activation...${NC}"
    if xrandr --output DP-4 --mode 1920x1080 --rate 60; then
        echo -e "${GREEN}✅ DP-4: Successfully activated${NC}"
    else
        echo -e "${RED}❌ DP-4: Failed to activate${NC}"
    fi
    echo ""
}

# Check physical connections
check_physical() {
    print_status "Checking physical connections..."
    echo ""
    
    # Check USB devices
    echo -e "${CYAN}USB Display Devices:${NC}"
    lsusb | grep -E "(Display|Dock|Monitor)" | sed 's/^/  /'
    echo ""
    
    # Check DisplayPort connections
    echo -e "${CYAN}DisplayPort Status:${NC}"
    if command -v displayport >/dev/null 2>&1; then
        displayport
        echo -e "${GREEN}✅ DisplayPort utility available${NC}"
        $displayport | head -10
    else
        echo -e "${YELLOW}⚠️  DisplayPort utility not available${NC}"
    fi
    echo ""
}

# Check for common issues
check_issues() {
    print_status "Checking for common display issues..."
    echo ""
    
    # Check for X11 errors
    echo -e "${CYAN}X11 Error Log:${NC}"
    if [ -f ~/.local/share/xorg/Xorg.0.log ]; then
        echo -e "${YELLOW}Recent X11 errors:${NC}"
        tail -20 ~/.local/share/xorg/Xorg.0.log | grep -i "error\|warn\|fail" | tail -5
    else
        echo -e "${GREEN}No X11 error log found${NC}"
    fi
    echo ""
    
    # Check for display manager conflicts
    echo -e "${CYAN}Display Manager Conflicts:${NC}"
    if pgrep -f "gnome-shell" >/dev/null; then
        echo -e "${YELLOW}⚠️  GNOME Shell running (may conflict)${NC}"
    fi
    
    if pgrep -f "kwin" >/dev/null; then
        echo -e "${YELLOW}⚠️  KWin running (may conflict)${NC}"
    fi
    
    if pgrep -f "xfwm4" >/dev/null; then
        echo -e "${YELLOW}⚠️  XFWM running (may conflict)${NC}"
    fi
    echo ""
}

# Show help menu
show_help() {
    print_header
    echo -e "${PURPLE}Diagnostic Commands:${NC}"
    echo -e "${GREEN}  connectivity${NC}  - Check display connectivity"
    echo -e "${GREEN}  capabilities${NC}  - Check display capabilities"
    echo -e "${GREEN}  activation${NC}    - Test display activation"
    echo -e "${GREEN}  physical${NC}     - Check physical connections"
    echo -e "${GREEN}  issues${NC}        - Check for common issues"
    echo -e "${GREEN}  help${NC}         - Show this help menu"
    echo ""
}

# Main script logic
main() {
    case "${1:-help}" in
        "connectivity")
            check_connectivity
            ;;
        "capabilities")
            check_capabilities
            ;;
        "activation")
            test_activation
            ;;
        "physical")
            check_physical
            ;;
        "issues")
            check_issues
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

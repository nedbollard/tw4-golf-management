#!/bin/bash

# TW4 Safe Display Management Script
# Author: Ned Bollard
# Description: Work with GNOME, not against it

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
    echo -e "${CYAN}TW4 Safe Display Management${NC}"
    echo -e "${BLUE}================================${NC}"
}

# Apply display configuration safely (working with GNOME)
apply_safe() {
    print_status "Applying display configuration safely..."
    
    # Use GNOME's own tools when available
    if command -v gsettings >/dev/null 2>&1; then
        print_status "Using GNOME display management..."
        
        # Apply xrandr settings (GNOME will detect and adapt)
        xrandr --output eDP-1 --mode 1920x1080 --pos 0x0 --rotate normal --primary
        sleep 1
        xrandr --output DP-3 --mode 1920x1080 --pos 1920x0 --rotate normal
        sleep 1
        xrandr --output DP-4 --mode 1920x1080 --pos 3840x0 --rotate normal
        
        # Let GNOME know about the changes
        gsettings set org.gnome.desktop.interface scaling-factor 1 2>/dev/null
        
        print_status "Display configuration applied"
    else
        print_status "Using xrandr directly..."
        xrandr --output eDP-1 --mode 1920x1080 --pos 0x0 --rotate normal --primary
        sleep 1
        xrandr --output DP-3 --mode 1920x1080 --pos 1920x0 --rotate normal
        sleep 1
        xrandr --output DP-4 --mode 1920x1080 --pos 3840x0 --rotate normal
        print_status "Display configuration applied"
    fi
}

# Quick setup for triple monitors
triple() {
    print_status "Setting up triple monitors..."
    apply_safe
}

# Quick setup for dual monitors
dual() {
    print_status "Setting up dual monitors..."
    xrandr --output eDP-1 --mode 1920x1080 --pos 0x0 --rotate normal --primary
    sleep 1
    xrandr --output DP-3 --mode 1920x1080 --pos 1920x0 --rotate normal
    xrandr --output DP-4 --off
    print_status "Dual monitor setup applied"
}

# Laptop only
laptop() {
    print_status "Setting up laptop display only..."
    xrandr --output eDP-1 --mode 1920x1080 --pos 0x0 --rotate normal --primary
    xrandr --output DP-3 --off
    xrandr --output DP-4 --off
    print_status "Laptop display setup applied"
}

# Show current status
status() {
    print_header
    echo -e "${PURPLE}Current Display Status:${NC}"
    echo ""
    xrandr --current
    echo ""
    
    echo -e "${CYAN}Active Displays:${NC}"
    xrandr --listactivemonitors
    echo ""
    
    echo -e "${CYAN}Kensington Dock:${NC}"
    if lsusb | grep -q "Kensington.*Dock"; then
        echo -e "${GREEN}Connected${NC}"
    else
        echo -e "${RED}Not detected${NC}"
    fi
    echo ""
}

# Show help
show_help() {
    print_header
    echo -e "${PURPLE}Safe Display Commands:${NC}"
    echo -e "${GREEN}  triple${NC}     - Triple monitors (laptop + 2 externals)"
    echo -e "${GREEN}  dual${NC}       - Dual monitors (laptop + 1 external)"
    echo -e "${GREEN}  laptop${NC}     - Laptop display only"
    echo -e "${GREEN}  apply${NC}      - Apply current configuration"
    echo -e "${GREEN}  status${NC}     - Show current status"
    echo -e "${GREEN}  help${NC}        - Show this help menu"
    echo ""
    echo -e "${YELLOW}Note: This works WITH GNOME, not against it${NC}"
    echo ""
}

# Main script logic
main() {
    case "${1:-help}" in
        "triple")
            triple
            ;;
        "dual")
            dual
            ;;
        "laptop")
            laptop
            ;;
        "apply")
            apply_safe
            ;;
        "status")
            status
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

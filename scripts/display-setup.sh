#!/bin/bash

# TW4 Display Management Script
# Author: Ned Bollard
# Description: Manage multiple display setups with Kensington dock

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
    echo -e "${CYAN}TW4 Display Management${NC}"
    echo -e "${BLUE}================================${NC}"
}

# Show current display configuration
show_current() {
    print_header
    echo -e "${PURPLE}Current Display Configuration:${NC}"
    echo ""
    
    # Show xrandr output
    xrandr --current
    echo ""
    
    # Show connected displays
    echo -e "${CYAN}Connected Displays:${NC}"
    xrandr --listmonitors | grep -E "(DP|HDMI|eDP)" | sed 's/^/  /'
    echo ""
    
    # Show Kensington dock status
    echo -e "${CYAN}Kensington Dock Status:${NC}"
    if lsusb | grep -q "Kensington.*Dock"; then
        echo -e "${GREEN}✅ Kensington SD4849Pv Dock: Connected${NC}"
    else
        echo -e "${RED}❌ Kensington SD4849Pv Dock: Not detected${NC}"
    fi
    echo ""
    
    # Show USB devices
    echo -e "${CYAN}USB Display Devices:${NC}"
    lsusb | grep -E "(Display|Dock|Monitor)" | sed 's/^/  /'
    echo ""
}

# Setup for laptop only (undock)
setup_laptop() {
    print_status "Setting up laptop display configuration..."
    
    # Reset to laptop display only
    xrandr --output eDP-1 --mode 1920x1080 --pos 0x0 --rotate normal
    
    # Disable external displays
    xrandr --output DP-1 --off 2>/dev/null
    xrandr --output DP-2 --off 2>/dev/null
    xrandr --output DP-3 --off 2>/dev/null
    xrandr --output DP-4 --off 2>/dev/null
    xrandr --output DP-5 --off 2>/dev/null
    xrandr --output HDMI-A-1 --off 2>/dev/null
    xrandr --output HDMI-A-2 --off 2>/dev/null
    
    print_status "✅ Laptop display configured (1920x1080)"
}

# Setup for dual monitors with dock
setup_dual_monitors() {
    print_status "Setting up dual monitor configuration with dock..."
    
    # Configure laptop as primary
    xrandr --output eDP-1 --mode 1920x1080 --pos 0x0 --rotate normal --primary
    
    # Configure external monitor (using DP-3 as first external)
    xrandr --output DP-3 --mode 1920x1080 --pos 1920x0 --rotate normal
    
    print_status "✅ Dual monitor setup configured"
    print_status "  Laptop (eDP-1): 1920x1080 at 0,0 (Primary)"
    print_status "  External (DP-3): 1920x1080 at 1920,0"
}

# Setup for triple monitors
setup_triple_monitors() {
    print_status "Setting up triple monitor configuration..."
    
    # Configure laptop as primary
    xrandr --output eDP-1 --mode 1920x1080 --pos 0x0 --rotate normal --primary
    
    # Configure external monitors (using DP-3 and DP-4)
    xrandr --output DP-3 --mode 1920x1080 --pos 1920x0 --rotate normal
    xrandr --output DP-4 --mode 1920x1080 --pos 3840x0 --rotate normal
    
    print_status "✅ Triple monitor setup configured"
    print_status "  Laptop (eDP-1): 1920x1080 at 0,0 (Primary)"
    print_status "  External 1 (DP-3): 1920x1080 at 1920,0"
    print_status "  External 2 (DP-4): 1920x1080 at 3840,0"
}

# Auto-detect and setup best configuration
auto_setup() {
    print_status "Auto-detecting display configuration..."
    
    # Check if Kensington dock is connected
    if lsusb | grep -q "Kensington.*Dock"; then
        print_status "✅ Kensington dock detected"
        
        # Count connected displays
        display_count=$(xrandr --listmonitors | grep -c "Monitor")
        
        if [ "$display_count" -eq 1 ]; then
            setup_laptop
        elif [ "$display_count" -eq 2 ]; then
            setup_dual_monitors
        elif [ "$display_count" -ge 3 ]; then
            setup_triple_monitors
        fi
    else
        print_status "❌ Kensington dock not detected, setting up laptop only"
        setup_laptop
    fi
}

# Reset all displays
reset_displays() {
    print_status "Resetting all display configurations..."
    
    # Turn off all external displays
    for output in $(xrandr | grep " connected" | cut -d" " -f1); do
        if [ "$output" != "eDP-1" ]; then
            xrandr --output "$output" --off 2>/dev/null
        fi
    done
    
    # Reset laptop to default
    xrandr --output eDP-1 --auto --rotate normal --primary
    
    print_status "✅ Displays reset to laptop only"
}

# Show help menu
show_help() {
    print_header
    echo -e "${PURPLE}Display Management Commands:${NC}"
    echo -e "${GREEN}  current${NC}    - Show current display configuration"
    echo -e "${GREEN}  laptop${NC}     - Setup laptop display only (undocked)"
    echo -e "${GREEN}  dual${NC}       - Setup dual monitors with dock"
    echo -e "${GREEN}  triple${NC}      - Setup triple monitors"
    echo -e "${GREEN}  auto${NC}        - Auto-detect and configure best setup"
    echo -e "${GREEN}  reset${NC}       - Reset all displays to laptop only"
    echo -e "${GREEN}  help${NC}        - Show this help menu"
    echo ""
    echo -e "${YELLOW}Kensington Dock:${NC}"
    echo -e "${CYAN}  Device ID: 047d:80c7${NC}"
    echo -e "${CYAN}  Name: SD4849Pv Dock${NC}"
    echo ""
}

# Main script logic
main() {
    case "${1:-help}" in
        "current")
            show_current
            ;;
        "laptop")
            setup_laptop
            ;;
        "dual")
            setup_dual_monitors
            ;;
        "triple")
            setup_triple_monitors
            ;;
        "auto")
            auto_setup
            ;;
        "reset")
            reset_displays
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

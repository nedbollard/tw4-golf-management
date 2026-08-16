#!/bin/bash

# TW4 HDMI Display Management Script
# Author: Ned Bollard
# Description: Handle HDMI displays through Kensington dock

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
    echo -e "${CYAN}TW4 HDMI Display Management${NC}"
    echo -e "${BLUE}================================${NC}"
}

# Force HDMI display activation
force_hdmi() {
    print_status "Force-activating HDMI displays..."
    
    # Force each display to activate with specific CRTC
    xrandr --output DP-3 --off
    xrandr --output DP-4 --off
    sleep 2
    
    # Activate laptop first
    xrandr --output eDP-1 --mode 1920x1080 --pos 0x0 --rotate normal --primary
    sleep 1
    
    # Force activate external displays with CRTCs
    xrandr --output DP-3 --mode 1920x1080 --pos 1920x0 --rotate normal --crtc 1
    sleep 1
    xrandr --output DP-4 --mode 1920x1080 --pos 3840x0 --rotate normal --crtc 2
    sleep 1
    
    # Refresh displays
    xrandr --output DP-3 --mode 1920x1080 --refresh 60
    xrandr --output DP-4 --mode 1920x1080 --refresh 60
    
    print_status "HDMI displays force-activated"
}

# Test HDMI connectivity
test_hdmi() {
    print_status "Testing HDMI connectivity..."
    
    echo -e "${CYAN}Available outputs:${NC}"
    xrandr --query | grep "connected"
    echo ""
    
    echo -e "${CYAN}Testing each HDMI display:${NC}"
    
    # Test DP-3
    echo "Testing DP-3..."
    if xrandr --output DP-3 --mode 1920x1080 --rate 60; then
        echo -e "${GREEN}DP-3: OK${NC}"
    else
        echo -e "${RED}DP-3: Failed${NC}"
    fi
    
    # Test DP-4
    echo "Testing DP-4..."
    if xrandr --output DP-4 --mode 1920x1080 --rate 60; then
        echo -e "${GREEN}DP-4: OK${NC}"
    else
        echo -e "${RED}DP-4: Failed${NC}"
    fi
}

# HDMI triple setup
hdmi_triple() {
    print_status "Setting up HDMI triple monitors..."
    force_hdmi
}

# HDMI dual setup
hdmi_dual() {
    print_status "Setting up HDMI dual monitors..."
    
    xrandr --output eDP-1 --mode 1920x1080 --pos 0x0 --rotate normal --primary
    sleep 1
    xrandr --output DP-3 --mode 1920x1080 --pos 1920x0 --rotate normal --crtc 1
    xrandr --output DP-4 --off
    
    print_status "HDMI dual setup applied"
}

# Show status
show_status() {
    print_header
    echo -e "${PURPLE}HDMI Display Status:${NC}"
    echo ""
    
    echo -e "${CYAN}Connected displays:${NC}"
    xrandr --query | grep "connected"
    echo ""
    
    echo -e "${CYAN}Active monitors:${NC}"
    xrandr --listactivemonitors
    echo ""
    
    echo -e "${CYAN}Kensington dock:${NC}"
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
    echo -e "${PURPLE}HDMI Display Commands:${NC}"
    echo -e "${GREEN}  force${NC}      - Force activate HDMI displays"
    echo -e "${GREEN}  test${NC}        - Test HDMI connectivity"
    echo -e "${GREEN}  triple${NC}      - HDMI triple monitors"
    echo -e "${GREEN}  dual${NC}        - HDMI dual monitors"
    echo -e "${GREEN}  status${NC}      - Show HDMI status"
    echo -e "${GREEN}  help${NC}        - Show this help menu"
    echo ""
    echo -e "${YELLOW}Note: Kensington dock converts HDMI to DisplayPort${NC}"
    echo ""
}

# Main script logic
main() {
    case "${1:-help}" in
        "force")
            force_hdmi
            ;;
        "test")
            test_hdmi
            ;;
        "triple")
            hdmi_triple
            ;;
        "dual")
            hdmi_dual
            ;;
        "status")
            show_status
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

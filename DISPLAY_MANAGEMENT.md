# TW4 Display Management Guide

## Overview

This guide covers the complete display management system for TW4 Golf Management with Kensington dock and Philips monitors.

## Hardware Configuration

- **Laptop:** Lenovo ThinkPad T490s
- **Dock:** Kensington SD4849Pv Dock (USB Device ID: 047d:80c7)
- **Monitors:** 3x Philips monitors (connected via HDMI → DisplayPort conversion)
- **Total Workspace:** 5760x1080 (3 × 1920x1080)

## Scripts Directory Structure

```
scripts/
├── display-manager.sh      # Master control script (recommended)
├── display-safe.sh         # Safe display management (works with GNOME)
├── display-hdmi.sh         # HDMI-specific management
├── display-diagnostic.sh    # Diagnostic and troubleshooting tools
├── display-persistent.sh     # Persistent configuration (bypasses GNOME)
├── display-setup.sh         # Original setup script
└── test-reboot.sh          # Reboot testing tools
```

## Quick Start Commands

### Most Common Usage
```bash
# Set up 3 monitors (your current working setup)
./scripts/display-manager.sh triple

# Check current display status
./scripts/display-manager.sh status

# Run full diagnostics if issues occur
./scripts/display-manager.sh diagnose

# See all available options
./scripts/display-manager.sh help
```

### Alternative Setups
```bash
# Dual monitors (laptop + 1 external)
./scripts/display-manager.sh dual

# Laptop display only
./scripts/display-manager.sh laptop

# Force HDMI activation (if monitors lose signal)
./scripts/display-manager.sh hdmi-force
```

## Display Manager Commands Reference

| Command | Description | Use Case |
|---------|-------------|----------|
| `status` | Show current display status | Check setup |
| `triple` | Safe 3-monitor setup | Daily use |
| `dual` | Safe 2-monitor setup | Portable use |
| `laptop` | Laptop display only | Travel |
| `hdmi-force` | Force HDMI activation | Troubleshooting |
| `hdmi-triple` | HDMI triple setup | HDMI issues |
| `diagnose` | Run full diagnostics | Problems |
| `list` | List all scripts | Discovery |
| `help` | Show help menu | Learning |

## Troubleshooting Guide

### Common Issues and Solutions

#### Issue: External Monitors Not Working
**Symptoms:**
- Monitors connected but no content displayed
- Only laptop screen visible
- Philips monitors show "No Signal"

**Solutions:**
1. **Check Monitor Input Source:**
   ```bash
   # Press Source/Input button on Philips monitors
   # Select HDMI 1 or HDMI 2 (match cable connection)
   ```

2. **Force HDMI Activation:**
   ```bash
   ./scripts/display-manager.sh hdmi-force
   ```

3. **Run Diagnostics:**
   ```bash
   ./scripts/display-manager.sh diagnose
   ```

#### Issue: GNOME Interference
**Symptoms:**
- Display settings revert after reboot
- Manual xrandr changes not persistent
- GNOME overrides manual configuration

**Solutions:**
1. **Use Safe Scripts:**
   ```bash
   ./scripts/display-manager.sh triple
   ```

2. **Avoid Dangerous Commands:**
   ```bash
   # DO NOT USE (breaks desktop):
   ./scripts/display-persistent.sh stop-gnome
   ```

#### Issue: Kensington Dock Not Detected
**Symptoms:**
- External monitors not recognized
- Only laptop display available
- USB device not found

**Solutions:**
1. **Check Dock Connection:**
   ```bash
   lsusb | grep Kensington
   # Should show: Kensington SD4849Pv Dock
   ```

2. **Check Display Outputs:**
   ```bash
   xrandr --query | grep connected
   # Should show DP-3 and DP-4 (HDMI→DP conversion)
   ```

3. **Restart Dock:**
   - Unplug dock for 30 seconds
   - Reconnect firmly
   - Run display setup

## Hardware-Specific Information

### Kensington SD4849Pv Dock
- **Device ID:** 047d:80c7
- **Functionality:** Converts HDMI to DisplayPort internally
- **Outputs:** Presents HDMI connections as DP-3, DP-4 to system
- **Power:** Bus-powered from laptop

### Philips Monitor Controls
#### Finding Input/Source Button
- **Location:** Usually bottom right edge or back panel
- **Labels:** "Source", "Input", or icon 📺↔️
- **Options:** HDMI 1, HDMI 2, DisplayPort, VGA, DVI

#### Changing Input Source
1. Press **Source/Input** button once
2. Use **Up/Down** buttons to navigate
3. Press **Source/Input** to confirm selection
4. Try both HDMI 1 and HDMI 2

## System Integration

### GNOME Compatibility
The display management system is designed to work **with** GNOME, not against it:

- **Safe Scripts:** Use `display-safe.sh` and `display-manager.sh`
- **Persistent Scripts:** Use `display-persistent.sh` (bypasses GNOME)
- **Avoid:** `display-persistent.sh stop-gnome` (breaks desktop)

### Reboot Persistence
- **Automatic:** Scripts in `~/.config/autostart/` run on login
- **Manual:** Use `display-manager.sh` commands after boot
- **Testing:** Use `test-reboot.sh` for reboot testing

## Development and Maintenance

### Adding New Display Setups
1. Create new function in appropriate script
2. Update `display-manager.sh` with new command
3. Test with `bash -x` for debugging
4. Update documentation

### Script Development Guidelines
- Use `#!/usr/bin/env bash` shebang
- Include colored output functions
- Add error handling and validation
- Test with different display configurations
- Update help documentation

## Emergency Procedures

### If All Displays Fail
1. **Force Laptop Only:**
   ```bash
   ./scripts/display-manager.sh laptop
   ```

2. **Restart Display Services:**
   ```bash
   sudo systemctl restart gdm
   ```

3. **Hardware Reset:**
   - Unplug Kensington dock
   - Restart laptop
   - Reconnect dock
   - Run display setup

### If Desktop Environment Breaks
1. **Reboot System:**
   ```bash
   sudo reboot
   ```

2. **Use Safe Scripts Only:**
   ```bash
   ./scripts/display-manager.sh triple
   ```

3. **Avoid Persistent Scripts:**
   - Don't use `display-persistent.sh stop-gnome`
   - Use `display-safe.sh` instead

## File Locations and Permissions

### Script Locations
- **Main Scripts:** `/home/ned-bollard/TW4/scripts/`
- **Configuration:** `~/.config/autostart/`
- **Logs:** `~/.local/share/xorg/` (if available)
- **Backups:** `/tmp/` (for testing)

### Permissions
All scripts are executable:
```bash
chmod +x scripts/*.sh
```

## Version Control

### Git Repository
- **Remote:** https://github.com/nedbollard/tw4-golf-management.git
- **Branch:** master
- **Scripts Directory:** Version-controlled
- **Backup:** All changes committed with descriptive messages

### Updating Scripts
```bash
# Add changes
git add scripts/

# Commit with descriptive message
git commit -m "Update display management - fixed HDMI detection"

# Push to GitHub
git push origin master
```

## Support and Reference

### Getting Help
```bash
# General help
./scripts/display-manager.sh help

# Specific script help
./scripts/display-safe.sh help
./scripts/display-hdmi.sh help
./scripts/display-diagnostic.sh help
```

### Checking System Status
```bash
# Current display configuration
./scripts/display-manager.sh status

# Dock connectivity
lsusb | grep Kensington

# Display outputs
xrandr --query | grep connected

# Active monitors
xrandr --listactivemonitors
```

## Best Practices

1. **Always use safe scripts** for daily operations
2. **Run diagnostics** when encountering issues
3. **Check physical connections** first (cables, power, input sources)
4. **Use display-manager.sh** as primary interface
5. **Commit changes** to Git for backup and version control
6. **Test after reboots** to ensure persistence

## Troubleshooting Checklist

- [ ] Kensington dock connected via USB?
- [ ] External monitors powered on?
- [ ] Correct input sources selected on monitors?
- [ ] HDMI cables firmly connected?
- [ ] Safe display script working?
- [ ] GNOME interfering with settings?
- [ ] Recent system updates causing issues?
- [ ] Configuration files created correctly?

## History

- **Initial Setup:** Display management scripts created for TW4 project
- **GNOME Issues:** Discovered GNOME interference with manual xrandr
- **HDMI Conversion:** Kensington dock converts HDMI to DisplayPort (DP-3, DP-4)
- **Solution:** Created safe scripts that work with GNOME
- **Organization:** All scripts organized in `scripts/` directory
- **Master Control:** `display-manager.sh` created as unified interface

---

*Last Updated:* April 12, 2026  
*Author:* Ned Bollard  
*Version:* 1.0

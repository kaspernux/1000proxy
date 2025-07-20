#!/bin/bash

# =============================================================================
# 1000proxy Main Setup Launcher
# =============================================================================
# This script provides easy access to all setup scripts in the scripts folder
# =============================================================================

set -e

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'

print_header() {
    echo -e "${BLUE}============================================================${NC}"
    echo -e "${BLUE} $1 ${NC}"
    echo -e "${BLUE}============================================================${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
    LOG_FILE="/var/log/1000proxy-setup.log"
    if [ -w "$LOG_FILE" ] || [ ! -e "$LOG_FILE" ] && [ -w "$(dirname "$LOG_FILE")" ]; then
        echo "$(date '+%Y-%m-%d %H:%M:%S') ERROR: $1" >> "$LOG_FILE"
    else
        USER_LOG="$HOME/1000proxy-setup.log"
        echo "$(date '+%Y-%m-%d %H:%M:%S') ERROR: $1" >> "$USER_LOG"
    fi
}

print_info() {
    echo -e "${CYAN}ℹ $1${NC}"
}


STATE_FILE=".setup_state"

clear
echo -e "${BLUE}╔══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║                     1000PROXY SETUP                          ║${NC}"
echo -e "${BLUE}║               Enterprise Security & Deployment               ║${NC}"
echo -e "${BLUE}╚══════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   print_error "This script must be run as root (use sudo)"
   print_info "Usage: sudo ./setup.sh"
   exit 1
fi

# Make scripts executable only if not already done
if [[ -d "scripts" ]]; then
    if ! find scripts -type f -name "*.sh" ! -executable | grep -q .; then
        print_info "Scripts already executable. Skipping chmod."
    else
        chmod +x scripts/*.sh
        print_success "Made all scripts executable"
    fi
else
    print_error "Scripts directory not found!"
    exit 1
fi

# Step tracking logic
function save_state() {
    echo "$1" > "$STATE_FILE"
}

function load_state() {
    if [[ -f "$STATE_FILE" ]]; then
        cat "$STATE_FILE"
    else
        echo ""
    fi
}

function clear_state() {
    rm -f "$STATE_FILE"
}

print_header "Setup Options"
echo "1. 🚀 Quick Setup (Complete deployment)"
echo "2. 🔐 Security Setup Only"
echo "3. 📦 Application Deployment Only"
echo "4. 📋 View Setup Summary"
echo "5. ❌ Exit"
echo

if [[ -f "$STATE_FILE" ]]; then
    print_warning "Previous setup was interrupted."
    last_step=$(load_state)
    print_info "Resuming from step: $last_step"
else
    last_step=""
fi

if [[ "$last_step" == "" ]]; then
    read -p "Choose an option (1-5): " choice
    sub_step=""
elif [[ "$last_step" =~ ^[0-9](\.[0-9])?$ ]]; then
    # If last_step is like "2.1", split into main and sub step
    choice="${last_step%%.*}"
    sub_step="${last_step#*.}"
    [[ "$choice" == "$sub_step" ]] && sub_step=""
else
    choice="$last_step"
    sub_step=""
fi

case $choice in
    1)
        print_header "Starting Quick Setup"
        save_state "1"
        ./scripts/quick-setup.sh || { print_error "Quick Setup failed."; exit 1; }
        clear_state
        ;;
    2)
        print_header "Security Setup Menu"
        echo "1. Core Security Setup"
        echo "2. Advanced Security Setup"
        echo "3. Both (Recommended)"
        echo
        if [[ -n "$sub_step" ]]; then
            sec_choice="$sub_step"
        else
            read -p "Choose security option (1-3): " sec_choice
        fi
        save_state "2.$sec_choice"
        case $sec_choice in
            1)
                ./scripts/secure-server-setup.sh || { print_error "Core Security Setup failed."; exit 1; }
                clear_state
                ;;
            2)
                ./scripts/advanced-security-setup.sh || { print_error "Advanced Security Setup failed."; exit 1; }
                clear_state
                ;;
            3)
                ./scripts/secure-server-setup.sh || { print_error "Core Security Setup failed."; exit 1; }
                ./scripts/advanced-security-setup.sh || { print_error "Advanced Security Setup failed."; exit 1; }
                clear_state
                ;;
            *)
                print_error "Invalid option"
                exit 1
                ;;
        esac
        ;;
    3)
        print_header "Starting Application Deployment"
        save_state "3"
        if [[ ! -x ./scripts/deploy-1000proxy.sh ]]; then
            print_error "deploy-1000proxy.sh not found or not executable."
            exit 1
        fi
        ./scripts/deploy-1000proxy.sh || { print_error "Application Deployment failed."; exit 1; }
        clear_state
        ;;
    4)
        print_header "Setup Summary"
        save_state "4"
        ./scripts/setup-summary.sh || { print_error "Setup Summary failed."; exit 1; }
        clear_state
        ;;
    5)
        print_info "Exiting..."
        clear_state
        exit 0
        ;;
    *)
        print_error "Invalid option"
        exit 1
        ;;
esac

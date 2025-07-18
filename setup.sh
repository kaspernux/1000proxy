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
}

print_info() {
    echo -e "${CYAN}ℹ $1${NC}"
}

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

# Make scripts executable
if [[ -d "scripts" ]]; then
    chmod +x scripts/*.sh
    print_success "Made all scripts executable"
else
    print_error "Scripts directory not found!"
    exit 1
fi

print_header "Setup Options"
echo "1. 🚀 Quick Setup (Complete deployment)"
echo "2. 🔐 Security Setup Only"
echo "3. 📦 Application Deployment Only"
echo "4. 📋 View Setup Summary"
echo "5. ❌ Exit"
echo

read -p "Choose an option (1-5): " choice

case $choice in
    1)
        print_header "Starting Quick Setup"
        exec ./scripts/quick-setup.sh
        ;;
    2)
        print_header "Security Setup Menu"
        echo "1. Core Security Setup"
        echo "2. Advanced Security Setup"
        echo "3. Both (Recommended)"
        echo
        read -p "Choose security option (1-3): " sec_choice

        case $sec_choice in
            1)
                exec ./scripts/secure-server-setup.sh
                ;;
            2)
                exec ./scripts/advanced-security-setup.sh
                ;;
            3)
                ./scripts/secure-server-setup.sh
                exec ./scripts/advanced-security-setup.sh
                ;;
            *)
                print_error "Invalid option"
                exit 1
                ;;
        esac
        ;;
    3)
        print_header "Starting Application Deployment"
        exec ./scripts/deploy-1000proxy.sh
        ;;
    4)
        print_header "Setup Summary"
        exec ./scripts/setup-summary.sh
        ;;
    5)
        print_info "Exiting..."
        exit 0
        ;;
    *)
        print_error "Invalid option"
        exit 1
        ;;
esac

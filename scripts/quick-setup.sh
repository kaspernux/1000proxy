#!/bin/bash

# =============================================================================
# 1000proxy Quick Setup Script
# =============================================================================
# This script makes all setup scripts executable and provides quick setup options
# =============================================================================

set -euo pipefail

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

# Banner
echo -e "${PURPLE}"
cat << 'EOF'
████████╗██╗  ██╗ ██████╗ ██╗   ██╗███████╗ █████╗ ███╗   ██╗██████╗
╚══██╔══╝██║  ██║██╔═══██╗██║   ██║██╔════╝██╔══██╗████╗  ██║██╔══██╗
   ██║   ███████║██║   ██║██║   ██║███████╗███████║██╔██╗ ██║██║  ██║
   ██║   ██╔══██║██║   ██║██║   ██║╚════██║██╔══██║██║╚██╗██║██║  ██║
   ██║   ██║  ██║╚██████╔╝╚██████╔╝███████║██║  ██║██║ ╚████║██████╔╝
   ╚═╝   ╚═╝  ╚═╝ ╚═════╝  ╚═════╝ ╚══════╝╚═╝  ╚═╝╚═╝  ╚═══╝╚═════╝

██████╗ ██████╗  ██████╗ ██╗  ██╗██╗   ██╗    ███████╗███████╗████████╗██╗   ██╗██████╗
██╔══██╗██╔══██╗██╔═══██╗╚██╗██╔╝╚██╗ ██╔╝    ██╔════╝██╔════╝╚══██╔══╝██║   ██║██╔══██╗
██████╔╝██████╔╝██║   ██║ ╚███╔╝  ╚████╔╝     ███████╗█████╗     ██║   ██║   ██║██████╔╝
██╔═══╝ ██╔══██╗██║   ██║ ██╔██╗   ╚██╔╝      ╚════██║██╔══╝     ██║   ██║   ██║██╔═══╝
██║     ██║  ██║╚██████╔╝██╔╝ ██╗   ██║       ███████║███████╗   ██║   ╚██████╔╝██║
╚═╝     ╚═╝  ╚═╝ ╚═════╝ ╚═╝  ╚═╝   ╚═╝       ╚══════╝╚══════╝   ╚═╝    ╚═════╝ ╚═╝
EOF
echo -e "${NC}"

print_header "1000proxy Secure Server Quick Setup"
print_info "Enterprise-level security for your proxy management platform"
echo

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   print_error "This script must be run as root (use sudo)"
   exit 1
fi

# Make scripts executable
print_header "Preparing Setup Scripts"

if [[ -f "scripts/secure-server-setup.sh" ]]; then
    chmod +x scripts/secure-server-setup.sh
    print_success "scripts/secure-server-setup.sh made executable"
else
    print_error "scripts/secure-server-setup.sh not found!"
    exit 1
fi

if [[ -f "scripts/advanced-security-setup.sh" ]]; then
    chmod +x scripts/advanced-security-setup.sh
    print_success "scripts/advanced-security-setup.sh made executable"
else
    print_warning "scripts/advanced-security-setup.sh not found"
fi

if [[ -f "scripts/deploy-1000proxy.sh" ]]; then
    chmod +x scripts/deploy-1000proxy.sh
    print_success "scripts/deploy-1000proxy.sh made executable"
else
    print_warning "scripts/deploy-1000proxy.sh not found"
fi

# Configuration options
print_header "Configuration Options"

read -p "Enter your domain name (or press Enter for default): " DOMAIN
DOMAIN="${DOMAIN:-1000proxy.io}"

read -p "Enter your email address (or press Enter for default): " EMAIL
EMAIL="${EMAIL:-admin@1000proxy.io}"

read -p "Enter repository URL (or press Enter for default): " REPO_URL
REPO_URL="${REPO_URL:-https://github.com/kaspernux/1000proxy.git}"

# Export variables
export DOMAIN="$DOMAIN"
export EMAIL="$EMAIL"
export REPO_URL="$REPO_URL"

print_info "Configuration:"
print_info "Domain: $DOMAIN"
print_info "Email: $EMAIL"
print_info "Repository: $REPO_URL"
echo

# Setup options
print_header "Setup Options"
echo -e "${CYAN}1. Complete Setup (Recommended)${NC} - Full security + application deployment"
echo -e "${CYAN}2. Security Only${NC} - Install and configure security measures only"
echo -e "${CYAN}3. Deploy Application${NC} - Deploy application to existing secure server"
echo -e "${CYAN}4. Custom Setup${NC} - Run individual scripts manually"
echo -e "${CYAN}5. Exit${NC}"
echo

read -p "Choose an option (1-5): " choice

case $choice in
    1)
        print_header "Starting Complete Setup"
        print_warning "This will take 15-30 minutes depending on your server speed"
        read -p "Continue? (y/N): " confirm
        if [[ $confirm =~ ^[Yy]$ ]]; then
            print_info "Step 1/3: Basic Security Setup"
            ./scripts/secure-server-setup.sh

            print_info "Step 2/3: Advanced Security Setup"
            if [[ -x "./scripts/advanced-security-setup.sh" ]]; then
                ./scripts/advanced-security-setup.sh
            else
                print_warning "scripts/advanced-security-setup.sh not found or not executable, skipping advanced security setup."
            fi

            print_info "Step 3/3: Application Deployment"
            ./scripts/deploy-1000proxy.sh

            print_success "Complete setup finished!"
        else
            print_info "Setup cancelled"
        fi
        ;;
    2)
        print_header "Starting Security-Only Setup"
        read -p "Continue? (y/N): " confirm
        if [[ $confirm =~ ^[Yy]$ ]]; then
            print_info "Step 1/2: Basic Security Setup"
            ./scripts/secure-server-setup.sh

            print_info "Step 2/2: Advanced Security Setup"
            ./scripts/advanced-security-setup.sh

            print_success "Security setup finished!"
            print_info "Run './scripts/deploy-1000proxy.sh' later to deploy the application"
        else
            print_info "Setup cancelled"
        fi
        ;;
            if [[ -x "./scripts/deploy-1000proxy.sh" ]]; then
                ./scripts/deploy-1000proxy.sh
                print_success "Application deployment finished!"
            else
                print_error "./scripts/deploy-1000proxy.sh not found or not executable!"
                exit 1
            fi
        read -p "Continue? (y/N): " confirm
        if [[ $confirm =~ ^[Yy]$ ]]; then
            ./scripts/deploy-1000proxy.sh
            print_success "Application deployment finished!"
        else
            print_info "Deployment cancelled"
        fi
        ;;
    4)
        print_header "Custom Setup Instructions"
        echo -e "${CYAN}Available scripts:${NC}"
        echo "1. ./scripts/secure-server-setup.sh      - Basic security and server setup"
        echo "2. ./scripts/advanced-security-setup.sh  - Advanced security measures"
        echo "3. ./scripts/deploy-1000proxy.sh         - Application deployment"
        echo
        echo -e "${CYAN}Run scripts in order for best results${NC}"
        echo "Example: ./scripts/secure-server-setup.sh && ./scripts/advanced-security-setup.sh && ./scripts/deploy-1000proxy.sh"
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

# Final information
print_header "Setup Complete Information"

if [[ -f "/root/1000proxy-security-report.txt" ]]; then
    print_success "Security report available at: /root/1000proxy-security-report.txt"
fi

if [[ -f "/root/advanced-security-report.txt" ]]; then
    print_success "Advanced security report available at: /root/advanced-security-report.txt"
fi

if [[ -f "/root/1000proxy-deployment-report.txt" ]]; then
    print_success "Deployment report available at: /root/1000proxy-deployment-report.txt"
fi

echo
print_warning "IMPORTANT REMINDERS:"
print_warning "1. SSH is now on port 2222 (not 22)"
print_warning "2. Root login is disabled - use 'proxy1000' user with sudo"
print_warning "   - The initial password for 'proxy1000' is saved in /root/1000proxy-security-report.txt"
print_warning "   - If you lose the password, reset it with: sudo passwd proxy1000"
print_warning "3. Save the security reports - they contain important passwords"
print_warning "4. Configure your .env file with actual API keys"
print_warning "5. Create an admin user for the application"
echo
    print_info "Your application should be available at: http://$DOMAIN"
    print_info "Admin panel: http://$DOMAIN/admin"
fi
    print_info "Admin panel: https://$DOMAIN/admin"
else
    print_info "Your application should be available at: http://localhost"
    print_info "Admin panel: http://localhost/admin"
fi

print_success "Thank you for using 1000proxy secure setup!"
print_header "Setup Complete"

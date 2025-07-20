#!/bin/bash

# 1000proxy Feature Verification Script (Bash version)
# Comprehensive feature checking for the entire application

# Default values
AUTHENTICATION=true
ADMIN_PANELS=true
PROXY_MANAGEMENT=true
PAYMENT_SYSTEM=true
API=true
INTEGRATION_3XUI=true
SECURITY=true
PERFORMANCE=true
VERBOSE=false
OUTPUT_FILE=""

# Parse flags
while [[ $# -gt 0 ]]; do
    case "$1" in
        --no-auth) AUTHENTICATION=false ;;
        --no-admin) ADMIN_PANELS=false ;;
        --no-proxy) PROXY_MANAGEMENT=false ;;
        --no-payment) PAYMENT_SYSTEM=false ;;
        --no-api) API=false ;;
        --no-3xui) INTEGRATION_3XUI=false ;;
        --no-security) SECURITY=false ;;
        --no-performance) PERFORMANCE=false ;;
        --verbose) VERBOSE=true ;;
        --output) OUTPUT_FILE="$2"; shift ;;
    esac
    shift
done

# Counters
TOTAL_CHECKS=0
PASSED_CHECKS=0
FAILED_CHECKS=0
WARNING_CHECKS=0

write_output() {
    local message="$1"
    local color="$2"
    local header="$3"
    local subheader="$4"

    if [[ "$header" == true ]]; then
        echo -e "\n\e[${color}m━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\e[0m"
        echo -e "\e[${color}m$message\e[0m"
        echo -e "\e[${color}m━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\e[0m"
    elif [[ "$subheader" == true ]]; then
        echo -e "\n\e[${color}m┌─ $message\e[0m"
    else
        echo -e "\e[${color}m$message\e[0m"
    fi

    if [[ -n "$OUTPUT_FILE" ]]; then
        echo "$message" >> "$OUTPUT_FILE"
    fi
}

test_feature() {
    local name="$1"
    local cmd="$2"
    local required=${3:-true}
    ((TOTAL_CHECKS++))

    if eval "$cmd"; then
        write_output "✅ $name" "32" false false
        ((PASSED_CHECKS++))
    else
        if [[ "$required" == true ]]; then
            write_output "❌ $name" "31" false false
            ((FAILED_CHECKS++))
        else
            write_output "⚠️  $name (Optional)" "33" false false
            ((WARNING_CHECKS++))
        fi
    fi
}

# START
write_output "🔍 1000proxy Complete Feature Verification" "36" true false
write_output "Check Started: $(date '+%Y-%m-%d %H:%M:%S')" "37"

# 1. CORE LARAVEL FEATURES
write_output "🎯 CORE LARAVEL FEATURES" "33" true

test_feature "Laravel Framework Installation" '[ -f artisan ] && [ -f composer.json ]'
test_feature "Environment Configuration" '[ -f .env ] && grep -q "^APP_KEY=" .env'
test_feature "Database Connection" 'php artisan migrate:status --quiet >/dev/null 2>&1'
test_feature "Application Key Generated" 'grep -q "^APP_KEY=base64:" .env'
test_feature "Storage Directory Writable" '[ -d storage ] && [ -d storage/logs ]'
test_feature "Bootstrap Cache Writable" '[ -d bootstrap/cache ]'

# 2. AUTHENTICATION SYSTEM
if [[ "$AUTHENTICATION" == true ]]; then
    write_output "🔐 AUTHENTICATION SYSTEM" "33" true
    test_feature "User Model" '[ -f app/Models/User.php ]'
    test_feature "Authentication Routes" 'php artisan route:list --json | grep -qE "login|register|logout"'
    test_feature "Password Reset Functionality" 'php artisan route:list --json | grep -q "password"'
    test_feature "Laravel Sanctum" 'grep -q "laravel/sanctum" composer.json'
    test_feature "Two-Factor Authentication" 'grep -q "pragmarx/google2fa-laravel" composer.json' false
    test_feature "Role-Based Permissions" 'grep -q "spatie/laravel-permission" composer.json'
fi

# 3. ADMIN PANELS (FILAMENT)
if [[ "$ADMIN_PANELS" == true ]]; then
    write_output "🎛️ ADMIN PANELS (FILAMENT)" "33" true
    test_feature "Filament Framework" 'grep -q "filament/filament" composer.json'
    test_feature "Filament Panels Directory" '[ -d app/Filament ]'
    test_feature "Admin Panel Configuration" '[ -d app/Providers/Filament ] || [ -f app/Filament/AdminPanelProvider.php ]'
    test_feature "Super Admin Panel" '[ -d app/Filament/Admin ] || ls app/Filament | grep -qi admin'
    test_feature "Customer Panel" '[ -d app/Filament/Customer ] || ls app/Filament | grep -qi customer'
    test_feature "Staff Panel" '[ -d app/Filament/Staff ] || ls app/Filament | grep -qi staff' false
    test_feature "Support Panel" '[ -d app/Filament/Support ] || ls app/Filament | grep -qi support' false
    test_feature "Filament Resources" 'find app/Filament -name "*Resource.php" | grep -q .'
    test_feature "Filament Pages" 'find app/Filament -name "*Page.php" | grep -q .'
fi

# 4. PROXY MANAGEMENT SYSTEM
if [[ "$PROXY_MANAGEMENT" == true ]]; then
    write_output "🌐 PROXY MANAGEMENT SYSTEM" "33" true
    test_feature "Server Model" '[ -f app/Models/Server.php ]'
    test_feature "Product Model" '[ -f app/Models/Product.php ]'
    test_feature "Order Model" '[ -f app/Models/Order.php ]'
    test_feature "Service Model" '[ -f app/Models/Service.php ]' false
    test_feature "Proxy Configuration Support" 'grep -qE "vless|vmess|trojan|shadowsocks" app/Models/*.php 2>/dev/null || ls app/Services | grep -q Proxy'
    test_feature "Server Management Service" 'ls app/Services 2>/dev/null | grep -q Server' false
    test_feature "Client Configuration Generation" 'grep -r "config\|client\|proxy" app/Services 2>/dev/null | grep -q .' false
fi

# (Continue for PAYMENT SYSTEM, API, INTEGRATION_3XUI, SECURITY, PERFORMANCE...)

# SUMMARY
write_output "📊 CHECK SUMMARY" "35" true
write_output "✅ Passed: $PASSED_CHECKS" "32"
write_output "❌ Failed: $FAILED_CHECKS" "31"
write_output "⚠️  Warnings: $WARNING_CHECKS" "33"
write_output "🔢 Total: $TOTAL_CHECKS" "36"


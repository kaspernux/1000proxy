# Scripts Directory

This directory contains utility scripts for development, testing, and deployment of the 1000proxy application.

## 📁 Script Overview

### 🐳 Docker Scripts

#### `docker-setup.sh` (Linux/macOS)
Complete Docker development environment setup script.

```bash
# Make executable and run
chmod +x scripts/docker-setup.sh
./scripts/docker-setup.sh
```

**Features:**
- Builds Docker images
- Starts all services
- Installs dependencies
- Runs migrations and seeders
- Sets up modern UI assets
- Configures permissions

#### `docker-setup.ps1` (Windows)
PowerShell version of the Docker setup script for Windows users.

```powershell
# Run with PowerShell
.\scripts\docker-setup.ps1
```

**Features:**
- Same functionality as the bash version
- Windows-optimized output and error handling
- PowerShell-native commands

### 🧪 Development Scripts

#### `test-project.ps1`
Comprehensive testing script that runs all project tests.

```powershell
# Run all tests
.\scripts\test-project.ps1

# Run with specific options
.\scripts\test-project.ps1 -Coverage -Verbose
```

**Features:**
- PHPUnit tests
- Feature tests
- Unit tests
- Code coverage reports
- Performance benchmarks

#### `debug-project.ps1`
Development debugging and diagnostic script.

```powershell
# Run diagnostics
.\scripts\debug-project.ps1

# Specific debug mode
.\scripts\debug-project.ps1 -Mode Database
```

**Features:**
- Environment validation
- Database connection testing
- Redis connectivity check
- File permission verification
- Laravel configuration validation

#### `check-features.ps1`
Feature availability and compatibility checker.

```powershell
# Check all features
.\scripts\check-features.ps1

# Check specific feature set
.\scripts\check-features.ps1 -Category UI
```

**Features:**
- Modern UI component validation
- Livewire functionality check
- Heroicons integration test
- Payment gateway status
- 3X-UI panel connectivity

#### `cleanup-project.ps1`
Project cleanup and maintenance script.

```powershell
# Basic cleanup
.\scripts\cleanup-project.ps1

# Deep cleanup
.\scripts\cleanup-project.ps1 -Deep
```

**Features:**
- Cache clearing
- Log file cleanup
- Temporary file removal
- Database optimization
- Asset rebuild

## 🚀 Quick Start Commands

### Docker Development Setup

```bash
# Linux/macOS
git clone https://github.com/kaspernux/1000proxy.git
cd 1000proxy
chmod +x scripts/docker-setup.sh
./scripts/docker-setup.sh
```

```powershell
# Windows PowerShell
git clone https://github.com/kaspernux/1000proxy.git
cd 1000proxy
.\scripts\docker-setup.ps1
```

### Testing and Validation

```powershell
# Run comprehensive tests
.\scripts\test-project.ps1

# Check system health
.\scripts\debug-project.ps1

# Validate features
.\scripts\check-features.ps1

# Clean up project
.\scripts\cleanup-project.ps1
```

## 📋 Script Dependencies

### System Requirements
- **Docker & Docker Compose** (for docker-setup scripts)
- **PowerShell 5.1+** (for .ps1 scripts)
- **Bash** (for .sh scripts on Linux/macOS)
- **PHP 8.3+** (for PHP-related operations)
- **Node.js 18+** (for frontend builds)

### Laravel Requirements
- **Composer** for PHP dependencies
- **NPM** for Node.js dependencies
- **MySQL/Redis** for database and caching

## 🔧 Customization

### Environment Variables
Scripts respect these environment variables:

```bash
# Docker configuration
DOCKER_COMPOSE_FILE=docker-compose.yml
APP_ENV=local
DB_CONNECTION=mysql

# Build configuration
NODE_ENV=development
NPM_CONFIG_CACHE=/tmp/npm-cache

# Testing configuration
PHPUNIT_COVERAGE=true
TEST_DATABASE=testing
```

### Script Configuration
Most scripts accept command-line parameters:

```powershell
# Example: Custom test configuration
.\scripts\test-project.ps1 -Environment testing -Coverage true -Parallel 4

# Example: Debug specific component
.\scripts\debug-project.ps1 -Component database -Verbose true

# Example: Cleanup with custom options
.\scripts\cleanup-project.ps1 -ClearCache true -OptimizeDB true
```

## 🛠️ Development Guidelines

### Adding New Scripts

1. **Follow naming convention**: `action-target.ps1` or `action-target.sh`
2. **Include help documentation**: Use comment blocks for usage
3. **Add error handling**: Proper exit codes and error messages
4. **Update this README**: Document new scripts and their usage

### Script Structure Template

```powershell
#!/usr/bin/env pwsh
# Script Name: example-script.ps1
# Description: Brief description of what the script does
# Usage: .\scripts\example-script.ps1 [options]

param(
    [string]$Environment = "local",
    [switch]$Verbose = $false
)

# Color functions for output
function Write-Info { param($msg) Write-Host "[INFO] $msg" -ForegroundColor Cyan }
function Write-Success { param($msg) Write-Host "[SUCCESS] $msg" -ForegroundColor Green }
function Write-Error { param($msg) Write-Host "[ERROR] $msg" -ForegroundColor Red }

# Main script logic here
try {
    Write-Info "Starting script execution..."
    # Script operations
    Write-Success "Script completed successfully!"
} catch {
    Write-Error "Script failed: $_"
    exit 1
}
```

## 📊 Script Execution Logs

Scripts generate logs in the following locations:

- **Docker Setup**: `storage/logs/docker-setup.log`
- **Test Results**: `storage/logs/test-results.log`
- **Debug Output**: `storage/logs/debug.log`
- **Cleanup Actions**: `storage/logs/cleanup.log`

## 🔒 Security Considerations

- Scripts validate environment before execution
- Sensitive operations require confirmation prompts
- Database operations use read-only users when possible
- File permissions are properly set after operations
- Cleanup scripts preserve important data

---

For more detailed information about specific scripts, use the `--help` or `-Help` parameter with any script.

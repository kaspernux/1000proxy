#!/bin/bash

# 1000proxy Complete Setup Summary
# This script provides an overview of all available deployment options

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

clear

echo -e "${BLUE}╔══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║                     1000PROXY SETUP SUMMARY                  ║${NC}"
echo -e "${BLUE}║               Complete Enterprise Deployment                  ║${NC}"
echo -e "${BLUE}╚══════════════════════════════════════════════════════════════╝${NC}"
echo ""

echo -e "${GREEN}📋 AVAILABLE SETUP SCRIPTS${NC}"
echo -e "${GREEN}════════════════════════════════════════════════════════${NC}"
echo ""

echo -e "${CYAN}1. 🚀 Quick Setup (Recommended)${NC}"
echo -e "   ${YELLOW}./scripts/quick-setup.sh${NC}"
echo -e "   One-command deployment with interactive menu"
echo -e "   Includes: Security + Application + Payment Gateway Setup"
echo ""

echo -e "${CYAN}2. 🔐 Manual Security Setup${NC}"
echo -e "   ${YELLOW}./scripts/secure-server-setup.sh${NC}"
echo -e "   Core Ubuntu 24.04 security hardening"
echo -e "   Features: SSH, Firewall, Fail2Ban, System Auditing"
echo ""

echo -e "${CYAN}3. 🛡️  Advanced Security Setup${NC}"
echo -e "   ${YELLOW}./scripts/advanced-security-setup.sh${NC}"
echo -e "   Enterprise-level security additions"
echo -e "   Features: WAF, IDS, DDoS Protection, Real-time Monitoring"
echo ""

echo -e "${CYAN}4. 📦 Application Deployment${NC}"
echo -e "   ${YELLOW}./scripts/deploy-1000proxy.sh${NC}"
echo -e "   Deploy 1000proxy Laravel application"
echo -e "   Features: Interactive Payment Gateway Configuration"
echo ""

echo -e "${GREEN}💳 SUPPORTED PAYMENT GATEWAYS${NC}"
echo -e "${GREEN}════════════════════════════════════════════════════════${NC}"
echo -e "• Stripe (Credit/Debit Cards)"
echo -e "• PayPal (PayPal Account & Cards)"
echo -e "• NowPayments (Cryptocurrency)"
echo -e "• Telegram Bot Integration"
echo ""

echo -e "${GREEN}🔒 SECURITY FEATURES${NC}"
echo -e "${GREEN}════════════════════════════════════════════════════════${NC}"
echo -e "• SSH Hardening (Custom Port 2222)"
echo -e "• UFW Firewall Configuration"
echo -e "• Fail2Ban Intrusion Prevention"
echo -e "• ModSecurity Web Application Firewall"
echo -e "• OSSEC Host-based Intrusion Detection"
echo -e "• DDoS Protection & Rate Limiting"
echo -e "• Real-time Security Monitoring"
echo -e "• Automated Security Backups"
echo -e "• SSL/TLS Certificate Management"
echo -e "• Database Security Hardening"
echo ""

echo -e "${GREEN}🚀 RECOMMENDED DEPLOYMENT PROCESS${NC}"
echo -e "${GREEN}════════════════════════════════════════════════════════${NC}"
echo -e "${YELLOW}For New Servers:${NC}"
echo -e "1. Run: ${CYAN}./scripts/quick-setup.sh${NC}"
echo -e "2. Follow interactive prompts"
echo -e "3. Configure payment gateways when prompted"
echo -e "4. Complete domain and SSL setup"
echo ""

echo -e "${YELLOW}For Manual Control:${NC}"
echo -e "1. Run: ${CYAN}./scripts/secure-server-setup.sh${NC}"
echo -e "2. Run: ${CYAN}./scripts/advanced-security-setup.sh${NC}"
echo -e "3. Run: ${CYAN}./scripts/deploy-1000proxy.sh${NC}"
echo -e "4. Configure payment gateways during deployment"
echo ""

echo -e "${GREEN}📚 DOCUMENTATION${NC}"
echo -e "${GREEN}════════════════════════════════════════════════════════${NC}"
echo -e "• Complete Guide: ${CYAN}docs/SECURE_SETUP_GUIDE.md${NC}"
echo -e "• Security Reports: ${CYAN}/root/*-report.txt${NC}"
echo -e "• Application Logs: ${CYAN}/var/www/1000proxy/storage/logs/${NC}"
echo -e "• Security Logs: ${CYAN}/var/log/security-monitor.log${NC}"
echo ""

echo -e "${GREEN}🔧 POST-INSTALLATION${NC}"
echo -e "${GREEN}════════════════════════════════════════════════════════${NC}"
echo -e "• Application: ${CYAN}https://your-domain.com${NC}"
echo -e "• Admin Panel: ${CYAN}https://your-domain.com/admin${NC}"
echo -e "• SSH Access: ${CYAN}ssh proxy1000@your-server -p 2222${NC}"
echo -e "• Monitor: ${CYAN}sudo tail -f /var/log/security-monitor.log${NC}"
echo ""

echo -e "${PURPLE}⚠️  IMPORTANT SECURITY NOTES${NC}"
echo -e "${PURPLE}════════════════════════════════════════════════════════${NC}"
echo -e "${RED}• SSH is on port 2222, not 22${NC}"
echo -e "${RED}• Root login is disabled${NC}"
echo -e "${RED}• Change all default passwords${NC}"
echo -e "${RED}• Backup your security keys${NC}"
echo -e "${RED}• Monitor security logs regularly${NC}"
echo ""

echo -e "${GREEN}Ready to deploy your secure 1000proxy server!${NC}"
echo -e "${CYAN}Choose your preferred setup method above.${NC}"
echo ""

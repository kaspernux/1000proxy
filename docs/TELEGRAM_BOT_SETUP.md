# 🤖 Telegram Bot Setup & Usage Guide

## Table of Contents
- [Overview](#overview)
- [Setup Instructions](#setup-instructions)
- [Environment Configuration](#environment-configuration)
- [Webhook Setup](#webhook-setup)
- [Customer Commands](#customer-commands)
- [Admin Commands](#admin-commands)
- [Security Features](#security-features)
- [Troubleshooting](#troubleshooting)

## Overview

The 1000proxy Telegram Bot provides a complete interface for customers and administrators to manage proxy services directly through Telegram. It offers:

- **Customer self-service**: Account management, proxy purchases, configuration downloads
- **Admin management**: User administration, server monitoring, system analytics
- **Real-time notifications**: Order updates, payment confirmations, system alerts
- **Interactive UI**: Inline keyboards, pagination, confirmation dialogs

## Setup Instructions

### 1. Create Telegram Bot

1. Contact [@BotFather](https://t.me/botfather) on Telegram
2. Send `/newbot` command
3. Choose a name for your bot (e.g., "1000proxy Support Bot")
4. Choose a username (e.g., "@your1000proxy_bot")
5. Save the **Bot Token** provided by BotFather

### 2. Environment Configuration

Add these variables to your `.env` file:

```env
# Telegram Bot Configuration
TELEGRAM_BOT_TOKEN=your_bot_token_from_botfather
TELEGRAM_WEBHOOK_URL=https://yourdomain.com/telegram/webhook
TELEGRAM_SECRET_TOKEN=your_random_secret_token_for_security

# Admin Configuration (optional)
APP_ADMIN_EMAILS=admin@yourdomain.com,admin2@yourdomain.com
```

### 3. Set Bot Commands (Optional)

Send these commands to [@BotFather](https://t.me/botfather):

1. Send `/setcommands`
2. Select your bot
3. Send this commands list:

```
start - Start using the bot
link - Link your Telegram to web account
balance - Check wallet balance
myproxies - View your proxy configurations
orders - View order history
servers - Browse available servers
buy - Purchase proxy servers
topup - Add funds to wallet
config - Get proxy configuration
reset - Reset proxy credentials
status - Check proxy status
support - Contact support
help - Show available commands
```

## Webhook Setup

### Option 1: Automatic Setup (Recommended)

Visit your admin panel and use the built-in webhook management:

```
https://yourdomain.com/telegram/set-webhook
```

### Option 2: Manual Setup

1. **Set Webhook:**
   ```bash
   curl -X POST "https://yourdomain.com/telegram/set-webhook"
   ```

2. **Check Webhook Status:**
   ```bash
   curl -X GET "https://yourdomain.com/telegram/webhook-info"
   ```

3. **Test Bot:**
   ```bash
   curl -X GET "https://yourdomain.com/telegram/test"
   ```

## Customer Commands

### Account Management

#### `/start`
- **Purpose**: Initialize bot interaction
- **Response**: Welcome message with account linking instructions
- **Usage**: `/start`

#### `/link [code]`
- **Purpose**: Link Telegram account to web account
- **Response**: Confirmation of successful linking
- **Usage**: `/link ABC12345`
- **Note**: Linking code is provided in the web dashboard

#### `/balance`
- **Purpose**: Check current wallet balance
- **Response**: Current balance in USD
- **Usage**: `/balance`

### Proxy Management

#### `/myproxies`
- **Purpose**: View all active proxy configurations
- **Response**: List of proxies with download links
- **Usage**: `/myproxies`
- **Features**: 
  - QR codes for easy setup
  - Direct configuration links
  - Traffic usage statistics

#### `/orders`
- **Purpose**: View order history
- **Response**: List of all orders with status
- **Usage**: `/orders`
- **Info**: Shows pending, completed, and disputed orders

#### `/config [order_id]`
- **Purpose**: Get specific proxy configuration
- **Response**: Configuration file and QR code
- **Usage**: `/config 123` or `/config_123`

#### `/reset [order_id]`
- **Purpose**: Reset proxy credentials (generates new UUID)
- **Response**: New configuration with reset credentials
- **Usage**: `/reset 123` or `/reset_123`
- **Note**: Requires confirmation via inline keyboard

#### `/status [order_id]`
- **Purpose**: Check proxy connection status
- **Response**: Connection status and traffic statistics
- **Usage**: `/status 123`

### Shopping & Payments

#### `/servers`
- **Purpose**: Browse available proxy servers
- **Response**: Paginated list of servers with purchase options
- **Usage**: `/servers`
- **Features**:
  - Location, protocol, and pricing information
  - Server load indicators
  - Direct purchase buttons

#### `/buy`
- **Purpose**: Browse servers for purchase
- **Response**: Interactive server selection with pagination
- **Usage**: `/buy`
- **Process**: Select server → Confirm purchase → Automatic provisioning

#### `/topup`
- **Purpose**: Add funds to wallet
- **Response**: Payment instructions and links
- **Usage**: `/topup`

### Support

#### `/support [message]`
- **Purpose**: Contact support team
- **Response**: Support ticket creation confirmation
- **Usage**: `/support My proxy is not working`

#### `/help`
- **Purpose**: Show available commands
- **Response**: Complete list of commands with descriptions
- **Usage**: `/help`

## Admin Commands

### Access Control

Admin commands are only available to users with admin privileges. Admin access is determined by:
- `is_admin` flag in user table
- `admin` role assignment
- Email address in `APP_ADMIN_EMAILS` environment variable

### Administrative Interface

#### `/admin`
- **Purpose**: Access main admin panel
- **Response**: Interactive admin dashboard
- **Features**:
  - User management
  - Server monitoring
  - System statistics
  - Broadcast messaging to customers

#### `/users [email]`
- **Purpose**: User management and search
- **Usage**: 
  - `/users` - Show user statistics
  - `/users john@example.com` - Search specific user
- **Response**: User details, orders, balance, activity

#### `/serverhealth`
- **Purpose**: Monitor server status and performance
- **Response**: 
  - Server status overview
  - Load indicators
  - Active/inactive servers count
  - Individual server details

#### `/stats`
- **Purpose**: System analytics and statistics
- **Response**:
  - User statistics
  - Revenue metrics
  - Order statistics
  - Server performance metrics

#### `/broadcast [message]`
- **Purpose**: Send announcements to all users
- **Usage**: `/broadcast Maintenance scheduled tonight`
- **Response**: Delivery confirmation with success/failure counts
- **Note**: Sends to all users with linked Telegram accounts

## Security Features

### Account Linking Security
- **Secure Token Generation**: 8-character alphanumeric codes
- **Time-limited Tokens**: Automatic expiration
- **One-time Use**: Tokens invalidated after use
- **User Verification**: Links only to authenticated accounts

### Admin Access Control
- **Role-based Permissions**: Multiple admin detection methods
- **Command Restriction**: Admin commands blocked for regular users
- **Audit Logging**: All admin actions logged

### Webhook Security
- **Secret Token Verification**: Optional webhook authenticity check
- **Request Validation**: Input sanitization and validation
- **Rate Limiting**: Protection against spam

## Advanced Features

### Interactive Elements

#### Inline Keyboards
- **Server Pagination**: Browse servers with Previous/Next buttons
- **Purchase Confirmations**: Confirm/Cancel buttons for orders
- **Admin Panel**: Interactive admin dashboard navigation

#### Callback Queries
- **Purchase Flow**: `buy_server_{id}` → `confirm_buy_{id}`
- **Reset Confirmations**: `reset_confirm_{order_id}`
- **Pagination**: `server_page_{page_number}`
- **Admin Actions**: `user_stats`, `server_health`, `system_stats`

### Notification System

The bot automatically sends notifications for:
- **Order Confirmations**: When orders are completed
- **Payment Confirmations**: When payments are processed
- **System Alerts**: Important system announcements
- **Account Updates**: Balance changes, proxy updates

### Queue Integration

All bot operations are integrated with Laravel's queue system:
- **Async Processing**: Order processing via `ProcessXuiOrder` job
- **Background Tasks**: Heavy operations don't block bot responses
- **Retry Logic**: Failed operations automatically retried
- **Error Handling**: Graceful failure handling with user notification

## Troubleshooting

### Common Issues

#### Bot Not Responding
1. **Check Webhook Status**:
   ```bash
   curl https://yourdomain.com/telegram/webhook-info
   ```

2. **Verify Bot Token**:
   - Ensure `TELEGRAM_BOT_TOKEN` is correct
   - Check with BotFather if token is valid

3. **Check Logs**:
   ```bash
   tail -f storage/logs/laravel.log
   ```

#### Account Linking Issues
1. **Generate New Linking Code**: Go to web dashboard
2. **Check Code Format**: Must be 8 alphanumeric characters
3. **Verify User Authentication**: User must be logged in web dashboard

#### Admin Commands Not Working
1. **Check Admin Status**: Verify user has admin privileges
2. **Review Environment**: Check `APP_ADMIN_EMAILS` configuration
3. **Database Check**: Verify `is_admin` flag or role assignment

#### Webhook SSL Issues
1. **Valid SSL Certificate**: Telegram requires HTTPS with valid certificate
2. **Certificate Chain**: Ensure full certificate chain is configured
3. **Test Webhook URL**: Verify URL is accessible externally

### Debug Commands

#### Test Bot Connectivity
```bash
# Test basic bot functionality
curl https://yourdomain.com/telegram/test

# Check webhook information
curl https://yourdomain.com/telegram/webhook-info
```

#### Monitor Bot Activity
```bash
# Watch real-time logs
tail -f storage/logs/laravel.log | grep -i telegram

# Monitor queue processing
php artisan queue:work --verbose
```

### Support

For technical support with the Telegram bot:

1. **Check Documentation**: Review this guide and Laravel documentation
2. **Review Logs**: Check application logs for error details
3. **Test Environment**: Use test commands to verify configuration
4. **Contact Support**: Reach out with specific error messages and logs

---

## 🎉 Ready to Launch!

Your Telegram bot is now fully configured and ready for production use. Users can:
- Link their accounts seamlessly
- Purchase and manage proxies
- Access support directly through Telegram
- Receive real-time notifications

Administrators can:
- Monitor system health
- Manage users efficiently
- Send broadcast announcements to customers
- Access comprehensive analytics

The bot provides a complete alternative interface to your web platform, enhancing user experience and reducing support overhead.

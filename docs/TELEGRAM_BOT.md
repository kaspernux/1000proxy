# Telegram Bot Integration Guide

## Overview

The 1000proxy Telegram bot allows users to access all proxy features directly through Telegram, providing a seamless mobile-first experience. Users can check balances, browse servers, place orders, and receive notifications without leaving the Telegram app.

## Features

### User Features

-   **Account Linking**: Secure account linking with the web platform
-   **Balance Checking**: View wallet balance and transaction history
-   **Server Browsing**: Browse available proxy servers and plans
-   **Order Management**: Place orders and view order history
-   **Support**: Access support directly through Telegram
-   **Notifications**: Real-time notifications for order updates

### Admin Features

-   **Webhook Management**: Set up and manage webhooks
-   **Bot Status**: Monitor bot health and connectivity
-   **User Management**: View linked accounts and activity

## Installation

### 1. Install Dependencies

```bash
composer require irazasyed/telegram-bot-sdk
```

### 2. Environment Configuration

Add the following to your `.env` file:

```env
TELEGRAM_BOT_TOKEN=your_bot_token_here
TELEGRAM_WEBHOOK_URL=https://your-domain.com/telegram/webhook
TELEGRAM_SECRET_TOKEN=your_secret_token_here
```

### 3. Create Telegram Bot

1. Message [@BotFather](https://t.me/BotFather) on Telegram
2. Send `/newbot` command
3. Follow the setup instructions
4. Copy the bot token to your `.env` file

### 4. Set Up Webhook

#### Option 1: Via Artisan Command

```bash
php artisan telegram:set-webhook
```

#### Option 2: Via API Endpoint

```bash
curl -X POST https://your-domain.com/telegram/set-webhook \
  -H "Authorization: Bearer YOUR_SANCTUM_TOKEN"
```

### 5. Run Migrations

```bash
php artisan migrate
```

## Usage

### Account Linking

1. User visits account settings page
2. Clicks "Generate Linking Code"
3. Starts conversation with bot on Telegram
4. Sends linking code to bot
5. Account is automatically linked

### Available Commands

-   `/start` - Start conversation and get linking instructions
-   `/help` - Show available commands
-   `/balance` - Check wallet balance
-   `/servers` - Browse available servers
-   `/orders` - View order history
-   `/buy <server_id>` - Purchase a server
-   `/support <message>` - Contact support

### Notifications

Users receive automatic notifications for:

-   Order confirmations
-   Payment completions
-   Server activations
-   Account alerts

## API Endpoints

### Webhook Endpoint

```
POST /telegram/webhook
```

Handles incoming updates from Telegram.

### Management Endpoints (Authenticated)

```
POST /telegram/set-webhook      # Set webhook URL
GET  /telegram/webhook-info     # Get webhook info
DELETE /telegram/webhook        # Remove webhook
GET  /telegram/test            # Test bot connectivity
```

## Security

### Authentication

-   Users must link their accounts before using bot features
-   Linking codes expire after 10 minutes
-   Each code can only be used once

### Data Protection

-   No sensitive data is stored in Telegram
-   All transactions are processed on the main platform
-   Audit logging for all bot interactions

### Rate Limiting

-   API calls are rate-limited to prevent abuse
-   Failed authentication attempts are logged
-   Automatic blocking of suspicious activity

## Error Handling

### Common Errors

-   **Invalid linking code**: Code expired or already used
-   **Account not linked**: User needs to link account first
-   **Insufficient balance**: User needs to top up wallet
-   **Server unavailable**: Server is out of stock

### Error Messages

All error messages are user-friendly and provide clear next steps.

## Monitoring

### Logs

-   All bot interactions are logged
-   Error tracking with stack traces
-   Performance metrics collection

### Health Checks

-   Webhook connectivity monitoring
-   Bot response time tracking
-   Error rate monitoring

## Development

### Testing

```bash
# Unit tests
php artisan test --filter TelegramBotTest

# Integration tests
php artisan test --filter TelegramIntegrationTest
```

### Debugging

```bash
# Check webhook info
curl https://your-domain.com/telegram/webhook-info

# Test bot connectivity
curl https://your-domain.com/telegram/test
```

## Troubleshooting

### Common Issues

1. **Webhook not working**

    - Check SSL certificate
    - Verify webhook URL is accessible
    - Check firewall settings

2. **Commands not responding**

    - Verify bot token is correct
    - Check webhook is set properly
    - Review error logs

3. **Account linking fails**
    - Ensure migration was run
    - Check cache configuration
    - Verify user permissions

### Support

For technical support, please:

1. Check the logs for error messages
2. Review this documentation
3. Contact the development team

## Contributing

When contributing to the Telegram bot:

1. Follow Laravel coding standards
2. Write comprehensive tests
3. Update documentation
4. Test all user flows
5. Ensure security best practices

## License

This Telegram bot integration is part of the 1000proxy platform and follows the same license terms.

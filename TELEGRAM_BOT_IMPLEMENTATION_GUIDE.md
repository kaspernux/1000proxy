# Telegram Bot Implementation Guide

## Overview
This document provides a comprehensive guide for the Telegram bot implementation in the 1000proxy Laravel application. The bot provides both customer and admin functionality for managing proxy services.

## Implementation Components

### 1. TelegramBotService (`app/Services/TelegramBotService.php`)
**Purpose**: Core service handling all Telegram bot interactions
**Lines of Code**: ~1,370 lines
**Key Features**:
- **Customer Commands (15 total)**:
  - `/start` - Welcome message and account linking
  - `/link` - Link Telegram account to user account  
  - `/balance` - Check current wallet balance
  - `/myproxies` - List user's active proxies
  - `/orders` - Show order history
  - `/servers` - Browse available servers
  - `/buy` - Purchase new proxy plans
  - `/topup` - Add funds to wallet
  - `/config` - Get proxy configuration
  - `/reset` - Reset proxy credentials
  - `/status` - Check proxy/order status
  - `/support` - Contact support
  - `/help` - Display help information

- **Admin Commands (5 total)**:
  - `/admin` - Admin dashboard access
  - `/users` - User management
  - `/serverhealth` - Check server status
  - `/stats` - System statistics
  - `/broadcast` - Send announcements

- **Interactive Features**:
  - Inline keyboards for navigation
  - Callback query handling
  - Order processing integration
  - Server management interface

### 2. TelegramBotController (`app/Http/Controllers/TelegramBotController.php`)
**Purpose**: HTTP controller for webhook management and bot administration
**Lines of Code**: ~314 lines
**Available Endpoints**:

#### Webhook Management
- `POST /telegram/webhook` - Process incoming updates
- `POST /telegram/set-webhook` - Configure webhook URL
- `GET /telegram/webhook-info` - Get webhook status
- `DELETE /telegram/webhook` - Remove webhook

#### Bot Administration
- `GET /telegram/test` - Test bot functionality
- `POST /telegram/send-test-message` - Send test messages
- `GET /telegram/bot-stats` - Get bot statistics
- `POST /telegram/broadcast` - Send broadcast messages

### 3. XUIService Integration (`app/Services/XUIService.php`)
**Enhanced Features**:
- `resetClient()` method for credential regeneration
- Traffic reset capabilities
- UUID regeneration for security

### 4. Queue Job Processing (`app/Jobs/ProcessXuiOrder.php`)
**Purpose**: Async order processing and proxy provisioning
**Features**:
- Automatic proxy setup
- Error handling and dispute management
- Integration with ClientProvisioningService

## Configuration

### Environment Variables
Add to your `.env` file:
```env
# Telegram Bot Configuration
TELEGRAM_BOT_TOKEN=your_bot_token_from_botfather
TELEGRAM_SECRET_TOKEN=your_random_secret_token_here

# Webhook Configuration
TELEGRAM_WEBHOOK_URL=${APP_URL}/telegram/webhook
```

### Services Configuration (`config/services.php`)
```php
'telegram' => [
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'secret_token' => env('TELEGRAM_SECRET_TOKEN'),
    'webhook_url' => env('TELEGRAM_WEBHOOK_URL'),
],
```

## Deployment Steps

### 1. Get Bot Token
1. Message @BotFather on Telegram
2. Create a new bot with `/newbot`
3. Save the provided token
4. Configure bot settings (name, description, etc.)

### 2. Configure Environment
```bash
# Add to .env file
echo "TELEGRAM_BOT_TOKEN=your_actual_token" >> .env
echo "TELEGRAM_SECRET_TOKEN=$(openssl rand -hex 32)" >> .env
```

### 3. Set Webhook
```bash
# Using curl
curl -X POST https://yourdomain.com/telegram/set-webhook \
  -H "Authorization: Bearer your_sanctum_token" \
  -H "Content-Type: application/json"

# Or access admin panel endpoint
```

### 4. Test Implementation
```bash
# Test bot functionality
curl -X GET https://yourdomain.com/telegram/test \
  -H "Authorization: Bearer your_sanctum_token"

# Send test message
curl -X POST https://yourdomain.com/telegram/send-test-message \
  -H "Authorization: Bearer your_sanctum_token" \
  -H "Content-Type: application/json" \
  -d '{"chat_id": "your_chat_id", "message": "Test message"}'
```

## Security Features

### 1. Webhook Verification
- Secret token validation
- Request signature verification
- Rate limiting protection

### 2. User Authentication
- Account linking mechanism
- Role-based access control
- Session management

### 3. Data Protection
- Secure credential handling
- Encrypted communications
- Input validation

## Usage Examples

### Customer Workflow
1. User starts bot with `/start`
2. Links account with `/link email@example.com password`
3. Checks balance with `/balance`
4. Browses servers with `/servers`
5. Purchases proxy with `/buy`
6. Gets configuration with `/config`

### Admin Workflow
1. Admin accesses with `/admin`
2. Checks system stats with `/stats`
3. Monitors servers with `/serverhealth`
4. Manages users with `/users`
5. Sends announcements with `/broadcast`

## API Integration

### Webhook Processing
```php
// Incoming webhook data structure
{
    "update_id": 123456789,
    "message": {
        "message_id": 123,
        "from": {
            "id": 987654321,
            "first_name": "John",
            "username": "johndoe"
        },
        "chat": {
            "id": 987654321,
            "type": "private"
        },
        "date": 1234567890,
        "text": "/start"
    }
}
```

### Response Format
```php
// Standard bot response
{
    "success": true,
    "data": {
        "bot_info": {...},
        "webhook_info": {...}
    }
}
```

## Monitoring and Logging

### 1. Bot Statistics
- Total linked users
- Recent interactions
- Message success rates
- Error tracking

### 2. Performance Metrics
- Response times
- Queue processing times
- Server health status
- User engagement rates

### 3. Error Handling
- Comprehensive logging
- Automatic retry mechanisms
- Graceful error responses
- Admin notifications

## Troubleshooting

### Common Issues

1. **Webhook Not Receiving Updates**
   - Check webhook URL accessibility
   - Verify SSL certificate
   - Confirm secret token configuration

2. **Bot Not Responding**
   - Verify bot token validity
   - Check service dependencies
   - Review error logs

3. **Command Not Working**
   - Validate user authentication
   - Check database connections
   - Verify queue processing

### Debug Commands
```bash
# Check webhook status
curl -X GET https://yourdomain.com/telegram/webhook-info

# Test bot connectivity
curl -X GET https://yourdomain.com/telegram/test

# View logs
tail -f storage/logs/laravel.log | grep -i telegram
```

## Maintenance

### Regular Tasks
1. Monitor bot statistics weekly
2. Update webhook URLs if domain changes
3. Rotate secret tokens periodically
4. Review and cleanup old conversations
5. Update bot commands and help text

### Performance Optimization
1. Implement response caching
2. Optimize database queries
3. Use queue workers for heavy operations
4. Monitor memory usage
5. Scale webhook processing

## Extension Points

### Adding New Commands
1. Define command in TelegramBotService
2. Add routing logic in processUpdate method
3. Implement business logic
4. Add tests and documentation

### Custom Keyboards
```php
// Example inline keyboard
$keyboard = [
    'inline_keyboard' => [
        [
            ['text' => 'Option 1', 'callback_data' => 'opt1'],
            ['text' => 'Option 2', 'callback_data' => 'opt2']
        ]
    ]
];
```

### Integration with External APIs
- Payment processors
- Server monitoring tools
- Analytics platforms
- Customer support systems

## Conclusion

The Telegram bot implementation provides a comprehensive interface for both customers and administrators to interact with the 1000proxy service. The architecture supports scalability, security, and extensibility while maintaining excellent user experience.

For support or questions, refer to the development team or create an issue in the project repository.

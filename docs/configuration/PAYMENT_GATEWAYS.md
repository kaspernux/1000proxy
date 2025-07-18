# 🔐 Payment Gateways Configuration

<div align="center">
  <img src="/images/1000proxy.png" width="200" alt="1000Proxy Logo">
  
  ## Payment Systems Setup & Integration
  
  *Complete guide to configuring payment gateways for 1000proxy*
</div>

---

## 📋 Overview

1000proxy supports multiple payment gateways including cryptocurrency and traditional payment methods. This guide covers configuration for all supported payment providers.

## 💳 Supported Payment Methods

### Cryptocurrency Payments
- **NowPayments** (100+ cryptocurrencies)
- **Bitcoin Core** (Direct Bitcoin integration)
- **Monero** (Privacy-focused payments)
- **Ethereum** (ETH and ERC-20 tokens)

### Traditional Payments
- **Stripe** (Credit/Debit cards)
- **PayPal** (PayPal and credit cards)
- **Bank Transfer** (Manual verification)

## 🚀 Quick Setup

### 1. NowPayments (Cryptocurrency)

**Registration:**
1. Sign up at [nowpayments.io](https://nowpayments.io)
2. Get API key and IPN secret
3. Configure accepted currencies

**Configuration:**
```env
# NowPayments Configuration
NOWPAYMENTS_API_KEY=your-api-key-here
NOWPAYMENTS_IPN_SECRET=your-ipn-secret-here
NOWPAYMENTS_SANDBOX=false
NOWPAYMENTS_CALLBACK_URL=https://yoursite.com/payment/callback/nowpayments

# Accepted Cryptocurrencies
NOWPAYMENTS_CURRENCIES=btc,eth,ltc,xmr,usdt,usdc
```

### 2. Bitcoin Core Integration

**Requirements:**
- Bitcoin Core node
- RPC access configured

**Configuration:**
```env
# Bitcoin Core RPC
BITCOIN_RPC_USER=bitcoinrpc
BITCOIN_RPC_PASSWORD=secure-rpc-password
BITCOIN_RPC_HOST=127.0.0.1
BITCOIN_RPC_PORT=8332
BITCOIN_NETWORK=mainnet

# Bitcoin Wallet
BITCOIN_WALLET_ENABLED=true
BITCOIN_WALLET_NAME=1000proxy
BITCOIN_CONFIRMATIONS_REQUIRED=3
```

### 3. Stripe Integration

**Setup:**
1. Create Stripe account
2. Get API keys from dashboard
3. Configure webhooks

**Configuration:**
```env
# Stripe Configuration
STRIPE_KEY=pk_live_your-publishable-key
STRIPE_SECRET=sk_live_your-secret-key
STRIPE_WEBHOOK_SECRET=whsec_your-webhook-secret
STRIPE_CURRENCY=usd

# Stripe Features
STRIPE_PAYMENT_METHODS=card,bank_transfer
STRIPE_CAPTURE_METHOD=automatic
```

## 🔧 Advanced Configuration

### Payment Flow Settings

```env
# General Payment Settings
PAYMENT_CURRENCY=USD
PAYMENT_MINIMUM_AMOUNT=5.00
PAYMENT_MAXIMUM_AMOUNT=1000.00
PAYMENT_PROCESSING_FEE=2.5

# Auto-activation
PAYMENT_AUTO_ACTIVATE=true
PAYMENT_MANUAL_REVIEW_THRESHOLD=500.00

# Refund Settings
PAYMENT_REFUND_ENABLED=true
PAYMENT_REFUND_PERIOD_DAYS=7
```

### Multi-Currency Support

```env
# Supported Currencies
PAYMENT_SUPPORTED_CURRENCIES=USD,EUR,GBP,BTC,ETH,XMR
PAYMENT_DEFAULT_CURRENCY=USD

# Exchange Rates
EXCHANGE_RATE_PROVIDER=coinapi
COINAPI_KEY=your-coinapi-key
EXCHANGE_RATE_UPDATE_INTERVAL=300
```

## 💰 Cryptocurrency Configuration

### NowPayments Advanced Settings

```env
# Advanced NowPayments
NOWPAYMENTS_NETWORK_FEE=standard
NOWPAYMENTS_MIN_CONFIRMATIONS=3
NOWPAYMENTS_PAYMENT_TIMEOUT=3600

# Supported Coins
NOWPAYMENTS_BTC_ENABLED=true
NOWPAYMENTS_ETH_ENABLED=true
NOWPAYMENTS_XMR_ENABLED=true
NOWPAYMENTS_USDT_ENABLED=true
NOWPAYMENTS_LTC_ENABLED=true

# Price API
NOWPAYMENTS_PRICE_API=https://api.nowpayments.io/v1/exchange
```

### Bitcoin Core Setup

**bitcoin.conf:**
```ini
# Bitcoin Core Configuration
server=1
daemon=1
rpcuser=bitcoinrpc
rpcpassword=secure-rpc-password
rpcallowip=127.0.0.1
rpcport=8332

# Network
testnet=0
prune=0

# Wallet
wallet=1000proxy
walletnotify=/usr/local/bin/payment-notify.sh %s

# Security
rpcssl=1
rpcsslcertificatechainfile=/path/to/cert.pem
rpcsslprivatekeyfile=/path/to/privkey.pem
```

### Monero Integration

```env
# Monero Configuration
MONERO_ENABLED=true
MONERO_WALLET_RPC_HOST=127.0.0.1
MONERO_WALLET_RPC_PORT=18082
MONERO_DAEMON_HOST=127.0.0.1
MONERO_DAEMON_PORT=18081

# Monero Security
MONERO_WALLET_PASSWORD=secure-wallet-password
MONERO_CONFIRMATIONS_REQUIRED=10
MONERO_NETWORK=mainnet
```

## 🏦 Traditional Payment Methods

### Stripe Configuration

**Webhook Setup:**
```bash
# Add webhook endpoint
https://yoursite.com/stripe/webhook

# Webhook events
payment_intent.succeeded
payment_intent.payment_failed
invoice.payment_succeeded
customer.subscription.updated
```

**Advanced Stripe Settings:**
```env
# Stripe Advanced
STRIPE_STATEMENT_DESCRIPTOR=1000PROXY
STRIPE_RECEIPT_EMAIL=true
STRIPE_SAVE_CARDS=true

# Subscription Features
STRIPE_SUBSCRIPTIONS_ENABLED=true
STRIPE_TRIAL_PERIOD_DAYS=7
STRIPE_INVOICE_COLLECTION=automatic
```

### PayPal Integration

```env
# PayPal Configuration
PAYPAL_MODE=live
PAYPAL_CLIENT_ID=your-client-id
PAYPAL_CLIENT_SECRET=your-client-secret
PAYPAL_WEBHOOK_ID=your-webhook-id

# PayPal Features
PAYPAL_CURRENCY=USD
PAYPAL_SANDBOX_MODE=false
PAYPAL_IPN_ENABLED=true
```

## 🔒 Security Configuration

### Payment Security

```env
# Security Settings
PAYMENT_ENCRYPTION_KEY=32-character-encryption-key
PAYMENT_HASH_ALGORITHM=sha256
PAYMENT_SIGNATURE_VALIDATION=true

# Rate Limiting
PAYMENT_RATE_LIMIT_ATTEMPTS=5
PAYMENT_RATE_LIMIT_MINUTES=15
PAYMENT_FRAUD_DETECTION=true

# IP Restrictions
PAYMENT_ALLOWED_IPS=127.0.0.1,your-server-ip
PAYMENT_WEBHOOK_IPS=verified-provider-ips
```

### Webhook Security

```env
# Webhook Validation
WEBHOOK_SIGNATURE_VALIDATION=true
WEBHOOK_TIMESTAMP_TOLERANCE=300
WEBHOOK_IP_WHITELIST=true

# Webhook URLs
STRIPE_WEBHOOK_URL=https://yoursite.com/webhooks/stripe
NOWPAYMENTS_WEBHOOK_URL=https://yoursite.com/webhooks/nowpayments
PAYPAL_WEBHOOK_URL=https://yoursite.com/webhooks/paypal
```

## 📊 Payment Monitoring

### Transaction Logging

```env
# Payment Logging
PAYMENT_LOG_LEVEL=info
PAYMENT_LOG_CHANNEL=payments
PAYMENT_AUDIT_ENABLED=true

# Database Logging
PAYMENT_DB_LOG_ENABLED=true
PAYMENT_RETENTION_DAYS=365
```

### Real-time Monitoring

```env
# Monitoring
PAYMENT_MONITORING_ENABLED=true
PAYMENT_SLACK_WEBHOOK=your-slack-webhook
PAYMENT_EMAIL_ALERTS=admin@yoursite.com

# Thresholds
PAYMENT_LARGE_AMOUNT_THRESHOLD=500.00
PAYMENT_FAILURE_RATE_THRESHOLD=10
```

## 🧪 Testing Configuration

### Sandbox/Test Mode

```env
# Test Mode
PAYMENT_TEST_MODE=true
APP_ENV=testing

# Test API Keys
STRIPE_TEST_KEY=pk_test_your-test-key
STRIPE_TEST_SECRET=sk_test_your-test-secret
NOWPAYMENTS_SANDBOX=true

# Test Webhooks
NGROK_URL=https://your-ngrok-url.ngrok.io
WEBHOOK_TEST_MODE=true
```

### Test Payment Commands

```bash
# Test payment processing
php artisan payment:test --provider=stripe --amount=10.00

# Test cryptocurrency payment
php artisan payment:test --provider=nowpayments --currency=btc

# Test webhook processing
php artisan webhook:test --provider=stripe --event=payment_succeeded
```

## 🔄 Payment Processing Flow

### Automatic Processing

```env
# Processing Settings
PAYMENT_AUTO_PROCESS=true
PAYMENT_PROCESSING_DELAY=30
PAYMENT_RETRY_ATTEMPTS=3
PAYMENT_RETRY_DELAY=300

# Queue Configuration
PAYMENT_QUEUE_CONNECTION=redis
PAYMENT_QUEUE_NAME=payments
PAYMENT_WORKER_TIMEOUT=300
```

### Manual Review Process

```env
# Manual Review
PAYMENT_MANUAL_REVIEW_ENABLED=true
PAYMENT_REVIEW_THRESHOLD=100.00
PAYMENT_ADMIN_APPROVAL_REQUIRED=false

# Review Notifications
PAYMENT_REVIEW_EMAIL=admin@yoursite.com
PAYMENT_REVIEW_SLACK_ENABLED=true
```

## 💎 Premium Features

### Subscription Management

```env
# Subscription Features
SUBSCRIPTION_ENABLED=true
SUBSCRIPTION_PLANS=basic,premium,enterprise
SUBSCRIPTION_TRIAL_ENABLED=true
SUBSCRIPTION_PRORATION=true

# Billing Cycles
SUBSCRIPTION_BILLING_CYCLES=monthly,quarterly,yearly
SUBSCRIPTION_GRACE_PERIOD_DAYS=3
```

### Discount System

```env
# Discount Configuration
DISCOUNT_CODES_ENABLED=true
DISCOUNT_PERCENTAGE_MAX=50
DISCOUNT_AMOUNT_MAX=100.00

# Bulk Discounts
BULK_DISCOUNT_ENABLED=true
BULK_DISCOUNT_THRESHOLD=10
BULK_DISCOUNT_PERCENTAGE=15
```

## 🔧 Troubleshooting

### Common Issues

**1. Payment Failures:**
```bash
# Check payment logs
tail -f storage/logs/payments.log

# Verify API credentials
php artisan payment:verify --provider=stripe
```

**2. Webhook Issues:**
```bash
# Test webhook connectivity
curl -X POST https://yoursite.com/webhooks/stripe \
  -H "Content-Type: application/json" \
  -d '{"test": true}'

# Check webhook logs
php artisan webhook:logs --provider=stripe
```

**3. Cryptocurrency Sync Issues:**
```bash
# Sync Bitcoin transactions
php artisan bitcoin:sync

# Check NowPayments status
php artisan nowpayments:status
```

### Debug Commands

```bash
# Enable payment debugging
PAYMENT_DEBUG=true

# Test payment flow
php artisan payment:debug --transaction=txn_123

# Verify webhook signatures
php artisan webhook:verify --provider=stripe --payload=webhook_data
```

## 📈 Performance Optimization

### Payment Performance

```env
# Performance Settings
PAYMENT_CACHE_ENABLED=true
PAYMENT_CACHE_TTL=300
PAYMENT_QUEUE_ENABLED=true

# Database Optimization
PAYMENT_DB_INDEXING=true
PAYMENT_CLEANUP_ENABLED=true
PAYMENT_CLEANUP_INTERVAL=daily
```

### Caching Strategy

```bash
# Cache payment methods
php artisan payment:cache

# Cache exchange rates
php artisan rates:cache

# Optimize payment queries
php artisan payment:optimize
```

## 📚 Related Documentation

- [🌍 Environment Configuration](ENVIRONMENT.md)
- [💾 Database Setup](DATABASE.md)
- [📧 Email Configuration](EMAIL.md)
- [🛡️ Security Guide](../security/SECURITY_BEST_PRACTICES.md)
- [🔧 API Documentation](../API.md)

---

<div align="center">
  <p>
    <a href="../README.md">📚 Back to Documentation</a> •
    <a href="../getting-started/CONFIGURATION.md">⚙️ Configuration</a> •
    <a href="EMAIL.md">📧 Email Setup</a>
  </p>
  
  **Need Help?** Check our [FAQ](../FAQ.md) or open an issue.
</div>

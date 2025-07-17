# 🔐 Admin Credentials & Default Users

This document contains the default administrator and test account credentials for the 1000proxy system.

## ⚠️ Security Notice

**IMPORTANT**: These are default credentials for development and initial setup only.

🚨 **CHANGE ALL PASSWORDS IMMEDIATELY IN PRODUCTION** 🚨

## 👑 Administrator Accounts

### System Administrator

- **Email**: `admin@1000proxy.io`
- **Password**: `P@ssw0rd!Adm1n2024$`
- **Role**: `admin`
- **Username**: `admin`
- **Permissions**: Full system access

### Support Manager

- **Email**: `support@1000proxy.io`
- **Password**: `Supp0rt#Mgr!2024&`
- **Role**: `support_manager`
- **Username**: `support_manager`
- **Permissions**: Customer support, ticket management

### Sales Support

- **Email**: `sales@1000proxy.io`
- **Password**: `S@les#Team!2024*`
- **Role**: `sales_support`
- **Username**: `sales_support`
- **Permissions**: Sales dashboard, customer onboarding

## 👤 Test Customer Account

### Demo Customer

- **Email**: `demo@1000proxy.io`
- **Password**: `D3m0#Cust0mer!2024$`
- **Name**: Demo Customer
- **Status**: Active
- **Free Trial**: Enabled
- **Discount**: 10%
- **Referral Code**: `DEMO2024`

## 🔑 Password Policy

All default passwords follow these security requirements:

- **Minimum Length**: 16 characters
- **Complexity**:
  - Uppercase letters (A-Z)
  - Lowercase letters (a-z)
  - Numbers (0-9)
  - Special characters (!@#$%^&*)
- **No Dictionary Words**: Avoid common dictionary words
- **No Personal Information**: No names, emails, or usernames in passwords

## 🛡️ Security Best Practices

### For Production Deployment

1. **Change All Default Passwords**

   ```bash
   # Use strong, unique passwords for each account
   # Consider using a password manager
   ```

2. **Enable Two-Factor Authentication**

   ```php
   // Configure 2FA for all admin accounts
   php artisan 2fa:setup admin@1000proxy.io
   ```

3. **Regular Password Rotation**

   ```bash
   # Rotate passwords every 90 days
   # Keep audit logs of password changes
   ```

4. **Account Monitoring**

   ```php
   // Enable login attempt monitoring
   // Set up alerts for suspicious activity
   ```

### Environment Variables

Set these environment variables for additional security:

```env
# Password requirements
PASSWORD_MIN_LENGTH=12
PASSWORD_REQUIRE_UPPERCASE=true
PASSWORD_REQUIRE_LOWERCASE=true
PASSWORD_REQUIRE_NUMBERS=true
PASSWORD_REQUIRE_SPECIAL=true

# Session security
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true

# Account lockout
MAX_LOGIN_ATTEMPTS=5
LOGIN_LOCKOUT_DURATION=300
```

## 📧 Email Configuration

All admin accounts use the `@1000proxy.io` domain:

- **Admin**: `admin@1000proxy.io`
- **Support**: `support@1000proxy.io`
- **Sales**: `sales@1000proxy.io`
- **Demo Customer**: `demo@1000proxy.io`

### Email Server Setup

Configure your email server to handle these domains:

```env
MAIL_MAILER=smtp
MAIL_HOST=mail.1000proxy.io
MAIL_PORT=587
MAIL_USERNAME=admin@1000proxy.io
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@1000proxy.io
MAIL_FROM_NAME="1000proxy"
```

## 🔄 Seeding Commands

To create these accounts in your database:

```bash
# Run specific seeders
php artisan db:seed --class=UserSeeder
php artisan db:seed --class=CustomerSeeder

# Or run all seeders
php artisan db:seed
```

### Docker Environment

```bash
# In Docker container
docker exec -it 1000proxy-app php artisan db:seed --class=UserSeeder
docker exec -it 1000proxy-app php artisan db:seed --class=CustomerSeeder
```

## 🎯 Access URLs

### Admin Panel Access

```text
Local Development:
- http://localhost:8000/admin
- Login with admin@1000proxy.io

Production:
- https://your-domain.com/admin
- Login with admin@1000proxy.io
```

### Customer Panel Access

```text
Local Development:
- http://localhost:8000/customer
- Login with demo@1000proxy.io

Production:
- https://your-domain.com/customer
- Login with demo@1000proxy.io
```

## 🔍 Account Verification

After seeding, verify accounts exist:

```bash
# Check admin users
php artisan tinker
>>> App\Models\User::where('role', 'admin')->get(['name', 'email', 'role']);

# Check test customer
>>> App\Models\Customer::where('email', 'demo@1000proxy.io')->first(['name', 'email']);
```

## 📋 Troubleshooting

### Password Reset

If you need to reset passwords:

```bash
# Reset admin password
php artisan tinker
>>> $user = App\Models\User::where('email', 'admin@1000proxy.io')->first();
>>> $user->password = Hash::make('new_secure_password');
>>> $user->save();
```

### Account Lockouts

If accounts get locked:

```bash
# Unlock user account
php artisan user:unlock admin@1000proxy.io

# Reset failed login attempts
php artisan auth:clear-attempts
```

### Email Verification

Force email verification if needed:

```bash
# Mark email as verified
php artisan tinker
>>> App\Models\User::where('email', 'admin@1000proxy.io')->update(['email_verified_at' => now()]);
```

## 📞 Support Information

For credential-related issues:

- **Technical Support**: `support@1000proxy.io`
- **Emergency Access**: Contact system administrator
- **Password Recovery**: Use "Forgot Password" feature

---

**Last Updated**: July 17, 2025  
**Version**: 1.0  
**Security Level**: High Priority

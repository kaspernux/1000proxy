<x-mail::message>
# Welcome to 1000 PROXIES! 🎉

Hello {{ $user->name }},

Welcome to **1000 PROXIES** - your premium proxy service provider! We're thrilled to have you on board.

## What's Next?

Here's how to get started with your new account:

### 🛍️ Browse Our Services
Explore our wide range of proxy services tailored to your needs:
- High-speed proxy servers
- Multiple geographic locations
- 24/7 reliable connection
- Advanced security features

### 📊 Access Your Dashboard
Your personal dashboard is ready and waiting for you:

<x-mail::button :url="$dashboardUrl">
Access Dashboard
</x-mail::button>

### 📚 Learn & Explore
Check out our comprehensive documentation to make the most of our services:

<x-mail::button :url="$docsUrl" color="success">
View Documentation
</x-mail::button>

## Need Help?

Our support team is here to help you 24/7. If you have any questions or need assistance:

<x-mail::button :url="$supportUrl" color="gray">
Contact Support
</x-mail::button>

## Account Information

- **Account Email:** {{ $user->email }}
- **Registration Date:** {{ $user->created_at->format('F j, Y') }}
- **Account ID:** #{{ $user->id }}

---

Thank you for choosing 1000 PROXIES. We look forward to providing you with exceptional proxy services!

Best regards,<br>
The 1000 PROXIES Team

---

*This is an automated message. Please do not reply to this email. For support, use the contact link above.*
</x-mail::message>

<?php

namespace App\Contracts;

/**
 * Marker interface for Notifications/Mails considered "system" messages.
 * If a Notification implements this interface (or is listed in config/mail_quota.php),
 * it will be throttled by the SystemMailQuotaGate listener.
 */
interface SystemMail {}

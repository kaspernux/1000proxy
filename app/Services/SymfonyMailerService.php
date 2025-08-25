<?php

namespace App\Services;

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Twig\Environment as TwigEnvironment;
use Illuminate\Support\Facades\Log;

class SymfonyMailerService
{
    protected Mailer $mailer;
    protected ?TwigEnvironment $twig = null;

    public function __construct(?TwigEnvironment $twig = null)
    {
        $dsn = (string) config('symfony-mailer.dsn', '');
        $transport = Transport::fromDsn($dsn);
        $this->mailer = new Mailer($transport);
        $this->twig = $twig; // optional, Laravel Blade remains primary
    }

    public function sendTemplated(string $to, string $subject, string $twigTemplate, array $context = []): bool
    {
        try {
            $from = new Address(config('mail.from.address'), config('mail.from.name'));
            $email = (new TemplatedEmail())
                ->from($from)
                ->to(new Address($to))
                ->subject($subject)
                ->htmlTemplate($twigTemplate)
                ->context($context);

            // Optional DKIM signing (requires openssl key and selector)
            $dkimDomain = config('symfony-mailer.dkim.domain');
            $dkimSelector = config('symfony-mailer.dkim.selector');
            $dkimKey = config('symfony-mailer.dkim.key_path');
            if ($dkimDomain && $dkimSelector && $dkimKey && is_readable($dkimKey)) {
                // Symfony Mailer auto applies DKIM if signer is configured in the Transport; here we simply log readiness.
                Log::info('Symfony Mailer DKIM ready', ['domain' => $dkimDomain, 'selector' => $dkimSelector]);
            }

            $this->mailer->send($email);
            return true;
        } catch (\Throwable $e) {
            Log::error('SymfonyMailer send failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logging\LoggerFactory;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;
use Throwable;

/**
 * Thin wrapper around PHPMailer for transactional email (email
 * verification, password reset, notifications). Failures are logged
 * but deliberately never thrown back to the request as a hard 500 —
 * a slow/unreachable SMTP host on shared hosting shouldn't break
 * registration; the user can always request the email again.
 */
final class MailService
{
    public function send(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): bool
    {
        $host = (string) config('mail.host');

        // An unconfigured/placeholder SMTP host must never stall the
        // request. Bail out immediately instead of attempting a
        // connection that will hang until the gateway times out (504).
        if ($host === '' || str_contains($host, 'yourdomain') || str_contains($host, 'example.')) {
            LoggerFactory::channel('system')->warning('Email skipped: MAIL_HOST is not configured.', [
                'to' => $toEmail,
                'subject' => $subject,
            ]);

            return false;
        }

        $mailer = new PHPMailer(true);

        try {
            $mailer->isSMTP();
            $mailer->Host = $host;
            // Cap SMTP connect/read time well below the web server's
            // gateway timeout so a slow or unreachable mail server can
            // only ever add a few seconds, never a 504.
            $mailer->Timeout = 10;
            $mailer->SMTPAuth = true;
            $mailer->Username = (string) config('mail.username');
            $mailer->Password = (string) config('mail.password');
            $mailer->SMTPSecure = (string) config('mail.encryption');
            $mailer->Port = (int) config('mail.port');

            $mailer->setFrom((string) config('mail.from.address'), (string) config('mail.from.name'));
            $mailer->addAddress($toEmail, $toName);

            $mailer->isHTML(true);
            $mailer->Subject = $subject;
            $mailer->Body = $htmlBody;
            $mailer->AltBody = $textBody !== '' ? $textBody : strip_tags($htmlBody);

            $mailer->send();

            return true;
        } catch (PHPMailerException|Throwable $e) {
            LoggerFactory::channel('system')->error('Failed to send email.', [
                'to' => $toEmail,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function sendEmailVerification(string $toEmail, string $toName, string $verificationUrl): bool
    {
        $subject = 'Verify your email address';
        $html = $this->renderTemplate('verify-email', [
            'name' => $toName,
            'url' => $verificationUrl,
            'appName' => (string) config('app.name'),
        ]);

        return $this->send($toEmail, $toName, $subject, $html);
    }

    public function sendPasswordReset(string $toEmail, string $toName, string $resetUrl): bool
    {
        $subject = 'Reset your password';
        $html = $this->renderTemplate('reset-password', [
            'name' => $toName,
            'url' => $resetUrl,
            'appName' => (string) config('app.name'),
        ]);

        return $this->send($toEmail, $toName, $subject, $html);
    }

    /**
     * @param array<string, string> $data
     */
    private function renderTemplate(string $template, array $data): string
    {
        $path = base_path("resources/mail-templates/{$template}.html");

        if (!is_file($path)) {
            // Minimal inline fallback so email sending never hard-fails
            // due to a missing template file.
            return sprintf(
                '<p>Hello %s,</p><p><a href="%s">Click here</a> to continue.</p>',
                htmlspecialchars($data['name'] ?? '', ENT_QUOTES),
                htmlspecialchars($data['url'] ?? '', ENT_QUOTES)
            );
        }

        $html = file_get_contents($path) ?: '';

        foreach ($data as $key => $value) {
            $html = str_replace('{{' . $key . '}}', htmlspecialchars($value, ENT_QUOTES), $html);
        }

        return $html;
    }
}

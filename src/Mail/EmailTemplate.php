<?php

namespace Tijd\Mail;

class EmailTemplate
{
    public static function verifyEmail(string $token, string $baseUrl): string
    {
        $verifyUrl = rtrim($baseUrl, '/') . "/verify.php?token={$token}";
        
        return self::wrapHtml(
            'Verifieer je e-mailadres',
            <<<HTML
<p>Bedankt voor je registratie bij Tijd Horoscopen!</p>

<p>Bevestig je e-mailadres door op de onderstaande link te klikken:</p>

<p style="margin: 20px 0;">
    <a href="{$verifyUrl}" style="background: #2196F3; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;">
        E-mailadres verifiëren
    </a>
</p>

<p>Of kopieer deze link naar je browser:</p>
<p style="word-break: break-all; color: #666;">{$verifyUrl}</p>

<p>Deze link is 24 uur geldig.</p>

<p>Als je je niet hebt geregistreerd, kun je deze e-mail negeren.</p>

<p>Met vriendelijke groet,<br>Tijd Horoscopen</p>
HTML
        );
    }

    public static function passwordReset(string $token, string $baseUrl): string
    {
        $resetUrl = rtrim($baseUrl, '/') . "/reset-password.php?token={$token}";
        
        return self::wrapHtml(
            'Wachtwoord resetten',
            <<<HTML
<p>Je hebt een verzoek gedaan om je wachtwoord te resetten.</p>

<p>Klik op de onderstaande link om een nieuw wachtwoord in te stellen:</p>

<p style="margin: 20px 0;">
    <a href="{$resetUrl}" style="background: #2196F3; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;">
        Wachtwoord resetten
    </a>
</p>

<p>Of kopieer deze link naar je browser:</p>
<p style="word-break: break-all; color: #666;">{$resetUrl}</p>

<p>Deze link is 1 uur geldig.</p>

<p>Als je geen wachtwoord reset hebt aangevraagd, kun je deze e-mail negeren.</p>

<p>Met vriendelijke groet,<br>Tijd Horoscopen</p>
HTML
        );
    }

    public static function emailChanged(string $newEmail, string $token, string $baseUrl): string
    {
        $verifyUrl = rtrim($baseUrl, '/') . "/verify-email.php?token={$token}";
        
        return self::wrapHtml(
            'Bevestig je nieuwe e-mailadres',
            <<<HTML
<p>Je hebt je e-mailadres gewijzigd naar: <strong>{$newEmail}</strong></p>

<p>Bevestig je nieuwe e-mailadres door op de onderstaande link te klikken:</p>

<p style="margin: 20px 0;">
    <a href="{$verifyUrl}" style="background: #2196F3; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;">
        E-mailadres bevestigen
    </a>
</p>

<p>Of kopieer deze link naar je browser:</p>
<p style="word-break: break-all; color: #666;">{$verifyUrl}</p>

<p>Deze link is 24 uur geldig.</p>

<p>Als je dit niet hebt aangevraagd, neem dan contact met ons op.</p>

<p>Met vriendelijke groet,<br>Tijd Horoscopen</p>
HTML
        );
    }

    private static function wrapHtml(string $title, string $content): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #f5f5f5;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f5f5f5; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="padding: 40px;">
                            <h1 style="margin: 0 0 24px 0; font-size: 24px; color: #1a1a2e;">{$title}</h1>
                            <div style="font-size: 16px; line-height: 1.6; color: #333;">
                                {$content}
                            </div>
                        </td>
                    </tr>
                </table>
                <p style="margin-top: 20px; font-size: 12px; color: #999;">
                    Dit is een automatisch bericht van Tijd Horoscopen.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
}
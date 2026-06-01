<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

function mail_phpmailer_available(): bool
{
    $root = dirname(__DIR__);
    if (is_readable($root . '/vendor/autoload.php')) {
        return true;
    }

    return is_readable($root . '/lib/PHPMailer/src/PHPMailer.php');
}

function mail_require_phpmailer(): bool
{
    static $loaded = false;
    if ($loaded) {
        return true;
    }
    $root = dirname(__DIR__);
    if (is_readable($root . '/vendor/autoload.php')) {
        require_once $root . '/vendor/autoload.php';
        $loaded = true;

        return true;
    }
    $src = $root . '/lib/PHPMailer/src';
    if (!is_readable($src . '/PHPMailer.php')) {
        return false;
    }
    require_once $src . '/Exception.php';
    require_once $src . '/PHPMailer.php';
    require_once $src . '/SMTP.php';
    $loaded = true;

    return true;
}

/**
 * @return array<string, mixed>|null
 */
function mail_load_config(): ?array
{
    static $cache = false;
    if ($cache !== false) {
        return $cache;
    }
    $path = dirname(__DIR__) . '/config/mail.php';
    if (!is_readable($path)) {
        $cache = null;
        return null;
    }
    $cfg = require $path;
    $cache = is_array($cfg) ? $cfg : null;
    return $cache;
}

function mail_is_configured(): bool
{
    $c = mail_load_config();
    if ($c === null || empty($c['enabled'])) {
        return false;
    }
    if (empty($c['smtp_host']) || empty($c['from_email'])) {
        return false;
    }
    if (!empty($c['smtp_auth'])) {
        $u = trim((string)($c['smtp_user'] ?? ''));
        $p = (string)($c['smtp_pass'] ?? '');
        if ($u === '' || $p === '') {
            return false;
        }
    }

    return mail_phpmailer_available();
}

/**
 * @param array<string, string> $headers Optional extra headers (name => value)
 */
function mail_send(string $to, string $subject, string $bodyText, string $bodyHtml = '', ?string $replyTo = null, array $headers = []): bool
{
    $cfg = mail_load_config();
    if ($cfg === null || empty($cfg['enabled'])) {
        return false;
    }
    if (!mail_require_phpmailer()) {
        error_log('mail_send: PHPMailer nav atrasts. Palaidiet composer install VAI pārbaudiet mapi lib/PHPMailer/src.');
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->isSMTP();
        $mail->Host = (string)$cfg['smtp_host'];
        $mail->Port = (int)($cfg['smtp_port'] ?? 587);
        $secure = (string)($cfg['smtp_secure'] ?? 'tls');
        $mail->SMTPAuth = !empty($cfg['smtp_auth']);
        if ($mail->SMTPAuth) {
            $mail->Username = (string)($cfg['smtp_user'] ?? '');
            $mail->Password = (string)($cfg['smtp_pass'] ?? '');
        }

        if (!empty($cfg['mailtrap_plain'])) {
            $mail->SMTPSecure = '';
            $mail->SMTPAutoTLS = true;
        } elseif ($secure === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->SMTPAutoTLS = true;
        } elseif ($secure === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->SMTPAutoTLS = true;
        } else {
            $mail->SMTPAutoTLS = false;
            $mail->SMTPSecure = '';
        }

        if (isset($cfg['smtp_debug']) && (int)$cfg['smtp_debug'] > 0) {
            $mail->SMTPDebug = (int)$cfg['smtp_debug'];
        }

        $mail->Timeout = !empty($cfg['smtp_timeout']) ? (int)$cfg['smtp_timeout'] : 10;


        $mail->setFrom((string)$cfg['from_email'], (string)($cfg['from_name'] ?? 'HomeEstate'));
        $mail->addAddress($to);
        if ($replyTo !== null && $replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $mail->addReplyTo($replyTo);
        }
        foreach ($headers as $hName => $hVal) {
            $mail->addCustomHeader((string)$hName, (string)$hVal);
        }

        $mail->Subject = $subject;
        if ($bodyHtml !== '') {
            $mail->isHTML(true);
            $mail->Body = $bodyHtml;
            $mail->AltBody = $bodyText;
        } else {
            $mail->isHTML(false);
            $mail->Body = $bodyText;
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('mail_send failed: ' . $e->getMessage());
        return false;
    }
}

function mail_notify_owner_new_pieteikums(
    string $ownerEmail,
    string $ownerName,
    string $listingTitle,
    string $applicantName,
    string $applicantEmail,
    string $pieteikumsType,
    string $viewUrl
): void {
    if (!filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) {
        return;
    }
    $subject = 'Jauns pieteikums sludinājumam: ' . $listingTitle;
    $text = "Sveiki, {$ownerName}!\n\n"
        . "Jūsu sludinājumam «{$listingTitle}» ir iesniegts jauns pieteikums.\n"
        . "Pieteikuma veids: {$pieteikumsType}\n"
        . "Pieteikējs: {$applicantName}\n"
        . "E-pasts: {$applicantEmail}\n"
        . "Apskatīt pieteikumu: {$viewUrl}\n\n"
        . '- HomeEstate';
    $safeTitle = htmlspecialchars($listingTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $html = '<p>Sveiki, ' . htmlspecialchars($ownerName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '!</p>'
        . '<p>Jūsu sludinājumam <strong>' . $safeTitle . '</strong> ir iesniegts jauns pieteikums.</p>'
        . '<ul><li>Pieteikuma veids: ' . htmlspecialchars($pieteikumsType, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>'
        . '<li>Pieteikējs: ' . htmlspecialchars($applicantName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>'
        . '<li>E-pasts: ' . htmlspecialchars($applicantEmail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>'
        . '<li>Apskatīt pieteikumu: <a href="' . htmlspecialchars($viewUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">' . htmlspecialchars($viewUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</a></li></ul>'
        . '<p>- HomeEstate</p>';
    mail_send($ownerEmail, $subject, $text, $html, $applicantEmail);
}

function mail_notify_applicant_pieteikums_decision(
    string $applicantEmail,
    string $applicantName,
    string $listingTitle,
    bool $accepted,
    string $ownerEmail = '',
    string $ownerPhone = ''
): void {
    if (!filter_var($applicantEmail, FILTER_VALIDATE_EMAIL)) {
        return;
    }
    $safeTitle = htmlspecialchars($listingTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    if ($accepted) {
        $subject = 'Jūsu pieteikums ir apstiprināts! HomeEstate';
        $text = "Sveiki, {$applicantName}!\n\n"
            . "Jūsu pieteikums sludinājumam «{$listingTitle}» ir apstiprināts.\n\n"
            . "Īpašnieka kontakts:\n"
            . "E-pasts: {$ownerEmail}\n"
            . "Telefons: {$ownerPhone}\n\n"
            . "- HomeEstate";
        $html = '<p>Sveiki, ' . htmlspecialchars($applicantName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '!</p>'
            . '<p>Jūsu pieteikums sludinājumam <strong>' . $safeTitle . '</strong> ir <strong>apstiprināts</strong>.</p>'
            . '<p><strong>Īpašnieka kontakts:</strong><br>'
            . 'E-pasts: ' . htmlspecialchars($ownerEmail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '<br>'
            . 'Telefons: ' . htmlspecialchars($ownerPhone, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>'
            . '<p>- HomeEstate</p>';
        mail_send($applicantEmail, $subject, $text, $html);
        return;
    }

    $subject = 'Jūsu pieteikums ir noraidīts! HomeEstate';
    $text = "Sveiki, {$applicantName}!\n\n"
        . "Jūsu pieteikums sludinājumam «{$listingTitle}» ir noraidīts.\n\n"
        . "- HomeEstate";
    $html = '<p>Sveiki, ' . htmlspecialchars($applicantName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '!</p>'
        . '<p>Jūsu pieteikums sludinājumam <strong>' . $safeTitle . '</strong> ir <strong>noraidīts</strong>.</p>'
        . '<p>- HomeEstate</p>';
    mail_send($applicantEmail, $subject, $text, $html);
}

function mail_send_login_2fa_code(string $to, string $recipientName, string $code, bool $isAdmin = false): bool
{
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $who = $isAdmin ? 'administratora / moderatora' : 'lietotāja';
    $subject = 'Piekļuves apstiprinājuma kods. HomeEstate';
    $text = "Sveiki, {$recipientName}!\n\n"
        . "Jūsu {$who} kontam ir pieprasīts ierakstīšanās.\n"
        . "Jūsu vienreizējais 6 ciparu kods: {$code}\n\n"
        . "Ja nepieprasījāt šo kodu, ignorējiet šo vēstuli.\n\n"
        . '- HomeEstate';
    $html = '<p>Sveiki, ' . htmlspecialchars($recipientName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '!</p>'
        . '<p>Jūsu ' . htmlspecialchars($who, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ' kontam ir pieprasīts ierakstīšanās.</p>'
        . '<p style="font-size:1.4em;font-weight:bold;letter-spacing:0.2em;">' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>'
        . '<p>Ja nepieprasījāt šo kodu, ignorējiet šo vēstuli.</p><p>- HomeEstate</p>';
    return mail_send($to, $subject, $text, $html);
}

function mail_notify_owner_listing_approved(string $ownerEmail, string $ownerName, string $listingTitle, string $viewUrl): void
{
    if (!filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) {
        return;
    }
    $subject = 'Jūsu sludinājums ir apstiprināts! HomeEstate';
    $text = "Sveiki, {$ownerName}!\n\n"
        . "Jūsu sludinājums «{$listingTitle}» ir apstiprināts un tagad ir aktīvs un publiski redzams.\n"
        . "Skatīt sludinājumu: {$viewUrl}\n\n"
        . '- HomeEstate';
    $safeTitle = htmlspecialchars($listingTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeUrl = htmlspecialchars($viewUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $html = '<p>Sveiki, ' . htmlspecialchars($ownerName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '!</p>'
        . '<p>Jūsu sludinājums <strong>' . $safeTitle . '</strong> ir <strong>apstiprināts</strong> un tagad ir aktīvs un publiski redzams.</p>'
        . '<p><a href="' . $safeUrl . '">' . $safeUrl . '</a></p>'
        . '<p>- HomeEstate</p>';
    mail_send($ownerEmail, $subject, $text, $html);
}

function mail_notify_owner_listing_rejected(string $ownerEmail, string $ownerName, string $listingTitle, string $reason = ''): void
{
    if (!filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) {
        return;
    }
    $reason = trim((string)$reason);
    $subject = 'Jūsu sludinājums ir noraidīts! HomeEstate';
    $text = "Sveiki, {$ownerName}!\n\n"
        . "Jūsu sludinājums «{$listingTitle}» ir noraidīts.\n";
    if ($reason !== '') {
        $text .= "\nIemesls: {$reason}\n";
    }
    $text .= "\n- HomeEstate";
    $safeTitle = htmlspecialchars($listingTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $html = '<p>Sveiki, ' . htmlspecialchars($ownerName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '!</p>'
        . '<p>Jūsu sludinājums <strong>' . $safeTitle . '</strong> ir <strong>noraidīts</strong>.</p>';
    if ($reason !== '') {
        $html .= '<p><strong>Iemesls:</strong> ' . nl2br(htmlspecialchars($reason, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) . '</p>';
    }
    $html .= '<p>- HomeEstate</p>';
    mail_send($ownerEmail, $subject, $text, $html);
}

function mail_notify_user_palidziba_reply(string $userEmail, string $userName, string $topic, string $replyText): void
{
    if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
        return;
    }
    $userName = trim($userName) !== '' ? $userName : 'Lietotājs';
    $topic = trim((string)$topic) !== '' ? (string)$topic : 'Palīdzības centrs';
    $replyText = trim((string)$replyText);
    $subject = 'Atbilde no palīdzības centra. HomeEstate';
    $text = "Sveiki, {$userName}!\n\n"
        . "Jūsu jautājumam ir sniegta atbilde.\n"
        . "Tēma: {$topic}\n\n"
        . "Atbilde:\n{$replyText}\n\n"
        . '- HomeEstate';
    $html = '<p>Sveiki, ' . htmlspecialchars($userName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '!</p>'
        . '<p>Jūsu jautājumam ir sniegta atbilde.</p>'
        . '<p><strong>Tēma:</strong> ' . htmlspecialchars($topic, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>'
        . '<p><strong>Atbilde:</strong><br>' . nl2br(htmlspecialchars($replyText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) . '</p>'
        . '<p>- HomeEstate</p>';
    mail_send($userEmail, $subject, $text, $html);
}

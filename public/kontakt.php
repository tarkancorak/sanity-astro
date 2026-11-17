<?php
/**
 * Kontaktformular-Handler (läuft auf dem Webspace des Kunden, Standard: IONOS).
 *
 * Versand über authentifiziertes SMTP mit dem EIGENEN Postfach des Kunden —
 * kein externer Formular-Dienst, kein Drittland-Transfer, kein fremder
 * Auftragsverarbeiter. Die Daten bleiben vollständig beim Kunden; der AVV ist
 * über dessen Hosting-/Mail-Vertrag abgedeckt.
 *
 * Das ContactForm-JS (fetch + Prüfung auf response.ok) funktioniert unverändert:
 * 200 = Erfolg, 4xx/5xx = Fehler. Antwort zusätzlich als JSON.
 *
 * ── PRO PROJEKT ANPASSEN ─────────────────────────────────────────────────────
 *  MAIL_TO / MAIL_FROM / SMTP_USER  auf das eigene Postfach des Kunden setzen.
 *  SMTP_HOST / SMTP_PORT            beim Mail-Provider des Kunden (Standard IONOS).
 *  $ALLOWED_ORIGINS                 Produktionsdomain(en) des Kunden.
 *  Passwort                         NICHT hier — der Kunde hinterlegt es als
 *                                   GitHub-Secret SMTP_PASSWORD in SEINEM Repo;
 *                                   der Deploy schreibt daraus smtp-secret.php.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// ── Konfiguration ────────────────────────────────────────────────────────────
const MAIL_TO        = 'TODO_REPLACE_ME@example.com';  // Empfänger (Postfach des Kunden)
const MAIL_FROM      = 'TODO_REPLACE_ME@example.com';  // Absender (eigene Domain des Kunden)
const MAIL_FROM_NAME = 'Kontaktformular';
const SMTP_HOST      = 'smtp.ionos.de';
const SMTP_PORT      = 465;                             // 465 = implizites SSL
const SMTP_USER      = 'TODO_REPLACE_ME@example.com';  // meist = MAIL_FROM
const TOPIC_LABEL    = 'Thema';                        // Label des optionalen topic-Dropdowns in der Mail
const SOURCE_LABEL   = 'Aufmerksam geworden über';     // Label des optionalen source-Dropdowns in der Mail

$ALLOWED_ORIGINS = [
    'https://example.com',      // TODO: Produktionsdomain
    'https://www.example.com',  // TODO
];

/** Antwort senden und beenden. */
function respond(int $status, string $message): void
{
    http_response_code($status);
    echo json_encode(['ok' => $status < 400, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

/** Kopfzeilen-Injection verhindern (CR/LF aus einzeiligen Feldern entfernen). */
function clean_line(string $value): string
{
    return trim(str_replace(["\r", "\n", "%0a", "%0d"], '', $value));
}

/**
 * Minimaler, dependency-freier SMTP-Versand über implizites TLS (Port 465)
 * mit AUTH LOGIN. Gibt true bei erfolgreicher Zustellung zurück.
 */
function smtp_send(string $pass, string $subjectEncoded, string $body, string $replyName, string $replyEmail): bool
{
    $ehlo = substr(strrchr(MAIL_FROM, '@') ?: '@localhost', 1);

    $fp = @stream_socket_client('ssl://' . SMTP_HOST . ':' . SMTP_PORT, $errno, $errstr, 15);
    if (!$fp) {
        error_log("kontakt.php: SMTP-Verbindung fehlgeschlagen ($errno $errstr)");
        return false;
    }
    stream_set_timeout($fp, 15);

    $read = static function () use ($fp): string {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) {
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break; // letzte Zeile einer (ggf. mehrzeiligen) Antwort
            }
        }
        return $data;
    };
    $cmd = static function (string $c) use ($fp, $read): string {
        fwrite($fp, $c . "\r\n");
        return $read();
    };
    $ok = static fn (string $resp, string $code): bool => strncmp($resp, $code, strlen($code)) === 0;
    $fail = static function (string $where) use ($fp): bool {
        error_log('kontakt.php: SMTP fehlgeschlagen bei ' . $where);
        @fwrite($fp, "QUIT\r\n");
        @fclose($fp);
        return false;
    };

    if (!$ok($read(), '220'))                               { return $fail('greeting'); }
    if (!$ok($cmd('EHLO ' . $ehlo), '250'))                 { return $fail('EHLO'); }
    if (!$ok($cmd('AUTH LOGIN'), '334'))                    { return $fail('AUTH'); }
    if (!$ok($cmd(base64_encode(SMTP_USER)), '334'))        { return $fail('user'); }
    if (!$ok($cmd(base64_encode($pass)), '235'))            { return $fail('pass'); }
    if (!$ok($cmd('MAIL FROM:<' . MAIL_FROM . '>'), '250')) { return $fail('MAIL FROM'); }
    if (!$ok($cmd('RCPT TO:<' . MAIL_TO . '>'), '25'))      { return $fail('RCPT TO'); }
    if (!$ok($cmd('DATA'), '354'))                          { return $fail('DATA'); }

    $headers = [
        'Date: ' . date('r'),
        'From: ' . mb_encode_mimeheader(MAIL_FROM_NAME) . ' <' . MAIL_FROM . '>',
        'To: <' . MAIL_TO . '>',
        'Reply-To: ' . mb_encode_mimeheader($replyName) . ' <' . $replyEmail . '>',
        'Subject: ' . $subjectEncoded,
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=utf-8',
        'Content-Transfer-Encoding: 8bit',
    ];

    // CRLF-Zeilenenden + Dot-Stuffing (Zeilen, die mit '.' beginnen, verdoppeln).
    $message = implode("\r\n", $headers) . "\r\n\r\n" . $body;
    $message = str_replace(["\r\n", "\r", "\n"], "\n", $message);
    $message = str_replace("\n", "\r\n", $message);
    $message = preg_replace('/^\./m', '..', $message);

    fwrite($fp, $message . "\r\n.\r\n");
    if (!$ok($read(), '250')) {
        return $fail('after DATA');
    }

    $cmd('QUIT');
    fclose($fp);
    return true;
}

// ── Herkunfts-Prüfung (Spam-/Missbrauchs-Schutz + CORS) ──────────────────────
// Best-effort: Origin/Referer lassen sich von Nicht-Browsern fälschen, stoppen
// aber zuverlässig browser-basierten Fremd-Missbrauch — keine echte Sicherheitsgrenze.
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin === '' && !empty($_SERVER['HTTP_REFERER'])) {
    $ref = parse_url($_SERVER['HTTP_REFERER']);
    if (isset($ref['scheme'], $ref['host'])) {
        $origin = $ref['scheme'] . '://' . $ref['host'] . (isset($ref['port']) ? ':' . $ref['port'] : '');
    }
}
$originAllowed = $origin !== '' && in_array($origin, $ALLOWED_ORIGINS, true);

if ($originAllowed) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    if (!$originAllowed) {
        respond(403, 'Ungültige Herkunft.');
    }
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Accept');
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, 'Methode nicht erlaubt.');
}

if (!$originAllowed) {
    respond(403, 'Ungültige Herkunft.');
}

// Honeypot: Bots füllen dieses versteckte Feld. Still "erfolgreich" verwerfen.
if (!empty($_POST['_gotcha'])) {
    respond(200, 'Vielen Dank!');
}

$name    = clean_line((string) ($_POST['name'] ?? ''));
$company = clean_line((string) ($_POST['company'] ?? ''));  // optional
$telefon = clean_line((string) ($_POST['telefon'] ?? ''));
$email   = clean_line((string) ($_POST['email'] ?? ''));
$topic   = clean_line((string) ($_POST['topic'] ?? ''));  // optional
$source  = clean_line((string) ($_POST['source'] ?? '')); // optional
$message = trim((string) ($_POST['message'] ?? ''));
$subject = clean_line((string) ($_POST['_subject'] ?? 'Neue Anfrage über das Kontaktformular'));

if ($name === '' || $telefon === '' || $email === '' || $message === '') {
    respond(400, 'Bitte alle Felder ausfüllen.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(400, 'Bitte eine gültige E-Mail-Adresse angeben.');
}
// Längenlimits (Missbrauchsschutz): Byte-Limits = 4× die maxlength-Attribute im
// Formular (UTF-8 max. 4 Byte/Zeichen); strlen statt mb_strlen, um nicht von der
// mbstring-Extension abzuhängen.
if (strlen($name) > 400 || strlen($company) > 400 || strlen($telefon) > 200
    || strlen($email) > 800 || strlen($topic) > 400 || strlen($source) > 400
    || strlen($subject) > 600
    || strlen($message) > 20000) {
    respond(400, 'Eingabe zu lang. Bitte kürzen Sie Ihre Angaben.');
}

// SMTP-Passwort laden (nicht im Repo — schreibt der Deploy aus dem GitHub-Secret).
$smtpPass = '';
$secretFile = __DIR__ . '/smtp-secret.php';
if (is_file($secretFile)) {
    $v = include $secretFile;
    if (is_string($v)) {
        $smtpPass = $v;
    }
}
if ($smtpPass === '') {
    error_log('kontakt.php: SMTP-Passwort fehlt (smtp-secret.php).');
    respond(500, 'Versand ist noch nicht konfiguriert.');
}

$topicLine  = $topic !== '' ? TOPIC_LABEL . ': ' . $topic . "\n" : '';
$sourceLine = $source !== '' ? SOURCE_LABEL . ': ' . $source . "\n" : '';
$body = "Neue Nachricht über das Kontaktformular\n\n"
      . "Name:     {$name}\n"
      . "Firma:    " . ($company !== '' ? $company : '-') . "\n"
      . "Telefon:  {$telefon}\n"
      . "E-Mail:   {$email}\n"
      . $topicLine
      . $sourceLine
      . "\nNachricht:\n{$message}\n";

$encodedSubject = mb_encode_mimeheader($subject, 'UTF-8');

if (smtp_send($smtpPass, $encodedSubject, $body, $name, $email)) {
    respond(200, 'Vielen Dank! Ihre Nachricht wurde gesendet.');
}

respond(500, 'Nachricht konnte nicht gesendet werden. Bitte später erneut versuchen.');

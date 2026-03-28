<?php
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: devis.html');
    exit;
}

/*
|--------------------------------------------------------------------------
| Chargement simple du .env
|--------------------------------------------------------------------------
*/

function load_env(string $path): array
{
    if (!is_readable($path)) {
        return [];
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if ($lines === false) {
        return [];
    }

    $env = [];

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $parts = explode('=', $line, 2);

        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);

        $value = trim($value, "\"'");

        $env[$key] = $value;
    }

    return $env;
}

$env = load_env(__DIR__ . '/.env');

/*
|--------------------------------------------------------------------------
| Configuration
|--------------------------------------------------------------------------
*/

$turnstileSecret = $env['SECRET_KEY'] ?? '';
$recipientEmail = 'contact@les-sites-de-david.fr';
$siteName = 'Les Sites de David';
$redirectSuccess = 'merci.html';
$redirectError = 'devis.html?error=1';

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function clean_input(?string $value): string
{
    return trim((string) $value);
}

function fail(string $message, int $statusCode = 400): void
{
    http_response_code($statusCode);
    echo $message;
    exit;
}

function verify_turnstile(string $secret, string $token, string $remoteIp = ''): bool
{
    if ($secret === '' || $token === '') {
        return false;
    }

    $postFields = [
        'secret' => $secret,
        'response' => $token,
    ];

    if ($remoteIp !== '') {
        $postFields['remoteip'] = $remoteIp;
    }

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($postFields),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        curl_close($ch);
        return false;
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return false;
    }

    $result = json_decode($response, true);

    return is_array($result) && !empty($result['success']);
}

/*
|--------------------------------------------------------------------------
| Récupération des données
|--------------------------------------------------------------------------
*/

$name = clean_input($_POST['name'] ?? '');
$email = clean_input($_POST['email'] ?? '');
$phone = clean_input($_POST['phone'] ?? '');
$service = clean_input($_POST['service'] ?? '');
$budget = clean_input($_POST['budget'] ?? '');
$message = clean_input($_POST['message'] ?? '');
$consent = $_POST['consent'] ?? '';
$turnstileToken = clean_input($_POST['cf-turnstile-response'] ?? '');

/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

if ($turnstileSecret === '') {
    fail('Configuration serveur manquante.', 500);
}

if ($name === '' || $email === '' || $service === '' || $message === '' || empty($consent)) {
    fail('Merci de remplir tous les champs obligatoires.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fail('Adresse e-mail invalide.');
}

if (!verify_turnstile($turnstileSecret, $turnstileToken, $_SERVER['REMOTE_ADDR'] ?? '')) {
    fail('Échec de vérification captcha.');
}

/*
|--------------------------------------------------------------------------
| Sécurisation supplémentaire pour les headers e-mail
|--------------------------------------------------------------------------
*/

$safeName = str_replace(["\r", "\n"], ' ', $name);
$safeEmail = filter_var($email, FILTER_SANITIZE_EMAIL);
$safePhone = str_replace(["\r", "\n"], ' ', $phone);
$safeService = str_replace(["\r", "\n"], ' ', $service);
$safeBudget = str_replace(["\r", "\n"], ' ', $budget);

/*
|--------------------------------------------------------------------------
| Construction du mail
|--------------------------------------------------------------------------
*/

$subject = 'Nouvelle demande de devis - ' . $safeName;

$body = "";
$body .= "Nouvelle demande de devis reçue depuis les-sites-de-david.fr\n\n";
$body .= "Nom : " . $safeName . "\n";
$body .= "Email : " . $safeEmail . "\n";
$body .= "Téléphone : " . ($safePhone !== '' ? $safePhone : 'Non renseigné') . "\n";
$body .= "Service : " . $safeService . "\n";
$body .= "Budget : " . ($safeBudget !== '' ? $safeBudget : 'Non renseigné') . "\n\n";
$body .= "Message :\n";
$body .= $message . "\n";

$headers = [];
$headers[] = 'From: ' . $siteName . ' <contact@les-sites-de-david.fr>';
$headers[] = 'Reply-To: ' . $safeEmail;
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-Type: text/plain; charset=UTF-8';

$headersString = implode("\r\n", $headers);

/*
|--------------------------------------------------------------------------
| Envoi
|--------------------------------------------------------------------------
*/

$mailSent = mail($recipientEmail, $subject, $body, $headersString);

if ($mailSent) {
    header('Location: ' . $redirectSuccess);
    exit;
}

fail("Erreur lors de l'envoi du message.", 500);
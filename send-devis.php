<?php
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: devis.html");
    exit;
}

// ==============================
// 🔒 VÉRIFICATION TURNSTILE
// ==============================

$secret = "0x4AAAAAACvAS3aNXHyeYZEZ"; 

$token = $_POST["cf-turnstile-response"] ?? "";

if (empty($token)) {
    http_response_code(400);
    echo "Captcha invalide.";
    exit;
}

// Vérification avec Cloudflare
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://challenges.cloudflare.com/turnstile/v0/siteverify");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    "secret" => $secret,
    "response" => $token,
    "remoteip" => $_SERVER["REMOTE_ADDR"]
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

if (!$result["success"]) {
    http_response_code(400);
    echo "Échec de vérification captcha.";
    exit;
}

// ==============================
// 🧼 SANITIZE
// ==============================

function clean($data) {
    return trim($data);
}

$name = clean($_POST["name"] ?? "");
$email = clean($_POST["email"] ?? "");
$phone = clean($_POST["phone"] ?? "");
$service = clean($_POST["service"] ?? "");
$budget = clean($_POST["budget"] ?? "");
$message = clean($_POST["message"] ?? "");
$consent = $_POST["consent"] ?? "";

// ==============================
// ✅ VALIDATION
// ==============================

if (empty($name) || empty($email) || empty($service) || empty($message) || empty($consent)) {
    http_response_code(400);
    echo "Merci de remplir tous les champs obligatoires.";
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo "Adresse e-mail invalide.";
    exit;
}

// ==============================
// 📧 EMAIL
// ==============================

$to = "contact@les-sites-de-david.fr";
$subject = "Nouvelle demande de devis - " . $name;

$body = "Nouvelle demande de devis :\n\n";
$body .= "Nom : $name\n";
$body .= "Email : $email\n";
$body .= "Téléphone : $phone\n";
$body .= "Service : $service\n";
$body .= "Budget : $budget\n\n";
$body .= "Message :\n$message\n";

$headers = "From: Les Sites de David <contact@les-sites-de-david.fr>\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// ==============================
// 🚀 ENVOI
// ==============================

if (mail($to, $subject, $body, $headers)) {
    header("Location: merci.html");
    exit;
} else {
    http_response_code(500);
    echo "Erreur lors de l'envoi.";
}
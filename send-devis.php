<?php
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: devis.html");
    exit;
}

function clean($data) {
    return trim($data);
}

$name = isset($_POST["name"]) ? clean($_POST["name"]) : "";
$email = isset($_POST["email"]) ? clean($_POST["email"]) : "";
$phone = isset($_POST["phone"]) ? clean($_POST["phone"]) : "";
$service = isset($_POST["service"]) ? clean($_POST["service"]) : "";
$budget = isset($_POST["budget"]) ? clean($_POST["budget"]) : "";
$message = isset($_POST["message"]) ? clean($_POST["message"]) : "";
$consent = isset($_POST["consent"]) ? $_POST["consent"] : "";

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

$to = "contact@les-sites-de-david.fr";
$subject = "Nouvelle demande de devis - " . $name;

$body = "Vous avez reçu une nouvelle demande de devis depuis le site Les Sites de David.\n\n";
$body .= "Nom / Prénom : " . $name . "\n";
$body .= "Email : " . $email . "\n";
$body .= "Téléphone : " . $phone . "\n";
$body .= "Type de demande : " . $service . "\n";
$body .= "Budget estimé : " . $budget . "\n\n";
$body .= "Message du client :\n" . $message . "\n";

$headers = "From: Les Sites de David <contact@les-sites-de-david.fr>\r\n";
$headers .= "Reply-To: " . $email . "\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

if (mail($to, $subject, $body, $headers)) {
    header("Location: merci.html");
    exit;
} else {
    http_response_code(500);
    echo "Une erreur est survenue lors de l'envoi du message.";
}
?>
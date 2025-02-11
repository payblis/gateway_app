<?php
// Définir le chemin absolu du fichier de log
define('LOG_FILE', __DIR__ . '/ipn_test_logs.txt');

// Créer le fichier s'il n'existe pas
if (!file_exists(LOG_FILE)) {
    touch(LOG_FILE);
    chmod(LOG_FILE, 0666); // Donner les permissions d'écriture
}

// Vérifier si le fichier est accessible en écriture
if (!is_writable(LOG_FILE)) {
    error_log('Le fichier de log IPN n\'est pas accessible en écriture: ' . LOG_FILE);
}

// Activer l'affichage des erreurs pour le debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log initial pour vérifier que le script est appelé
$initialLog = "=== Script IPN appelé le " . date('Y-m-d H:i:s') . " ===\n";
file_put_contents(LOG_FILE, $initialLog, FILE_APPEND);

// Récupérer les données brutes
$rawData = file_get_contents('php://input');
$headers = getallheaders();

// Logger toutes les données reçues
$logContent = "Headers:\n" . print_r($headers, true) . "\n\n";
$logContent .= "Raw Data:\n" . $rawData . "\n\n";
$logContent .= "GET Data:\n" . print_r($_GET, true) . "\n\n";
$logContent .= "POST Data:\n" . print_r($_POST, true) . "\n\n";

file_put_contents(LOG_FILE, $logContent, FILE_APPEND);

// Répondre avec un code 200
http_response_code(200);
header('Content-Type: application/json');

$response = [
    'status' => 'success',
    'message' => 'IPN notification received',
    'timestamp' => date('Y-m-d H:i:s')
];

echo json_encode($response);

// Logger la réponse
file_put_contents(LOG_FILE, "Response sent:\n" . json_encode($response) . "\n\n", FILE_APPEND); 
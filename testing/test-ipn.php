<?php
// Activer l'affichage des erreurs pour le debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Créer un fichier de log
$logFile = __DIR__ . '/ipn_test_logs.txt';

// Récupérer les données brutes
$rawData = file_get_contents('php://input');

// Récupérer les headers
$headers = getallheaders();

// Préparer le contenu du log
$logContent = "=== Nouvelle notification IPN reçue le " . date('Y-m-d H:i:s') . " ===\n";
$logContent .= "\nHeaders reçus:\n";
$logContent .= print_r($headers, true);
$logContent .= "\nDonnées brutes reçues:\n";
$logContent .= $rawData . "\n";

// Si les données sont du JSON, les décoder pour un affichage plus lisible
if ($rawData) {
    $decodedData = json_decode($rawData, true);
    if ($decodedData) {
        $logContent .= "\nDonnées décodées:\n";
        $logContent .= print_r($decodedData, true);
    }
}

$logContent .= "\n=== Fin de la notification ===\n\n";

// Enregistrer dans le fichier de log
file_put_contents($logFile, $logContent, FILE_APPEND);

// Répondre avec un code 200 et les données reçues
http_response_code(200);
header('Content-Type: application/json');

echo json_encode([
    'status' => 'success',
    'message' => 'IPN notification received and logged',
    'received_data' => [
        'headers' => $headers,
        'body' => $decodedData ?? $rawData
    ]
]); 
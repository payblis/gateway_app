<?php
function logOvriFlow($stage, $data) {
    $log_file = __DIR__ . '/../logs/ovri_flow.log';
    
    // Créer le dossier logs s'il n'existe pas
    if (!file_exists(__DIR__ . '/../logs')) {
        mkdir(__DIR__ . '/../logs', 0755, true);
    }
    
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'stage' => $stage,
        'data' => $data
    ];
    
    file_put_contents($log_file, json_encode($log_entry, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
} 
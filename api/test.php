<?php
/**
 * Test semplice per verificare che la cartella API sia accessibile
 */

header('Content-Type: application/json');

echo json_encode([
    'status' => 'ok',
    'message' => 'API accessibile!',
    'timestamp' => date('Y-m-d H:i:s')
]);

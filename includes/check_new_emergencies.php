<?php
session_start();
require_once 'auth.php';
require_once 'database.php';

header('Content-Type: application/json');

if (!isOperador()) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

$db = connectDatabase();
$total = checkNewEmergencies($db);

echo json_encode([
    'total' => $total,
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
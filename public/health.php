<?php
/************************************************************************
 * Simple health check endpoint for container orchestration (Cloud Run, K8s).
 * This endpoint does NOT load the EspoCRM application to allow health
 * checks to pass even when the database is not configured.
 ************************************************************************/

// Set JSON content type
header('Content-Type: application/json');

// Basic health check - verify core files exist
$checks = [
    'php' => true,
    'bootstrap' => file_exists(__DIR__ . '/../bootstrap.php'),
    'vendor' => file_exists(__DIR__ . '/../vendor/autoload.php'),
    'client' => file_exists(__DIR__ . '/../client/lib/espo.js'),
];

$healthy = !in_array(false, $checks, true);

if ($healthy) {
    http_response_code(200);
    echo json_encode([
        'status' => 'healthy',
        'checks' => $checks,
        'timestamp' => date('c'),
    ]);
} else {
    http_response_code(503);
    echo json_encode([
        'status' => 'unhealthy',
        'checks' => $checks,
        'timestamp' => date('c'),
    ]);
}

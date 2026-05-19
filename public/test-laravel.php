<?php
header('Content-Type: application/json');

try {
    $app = require __DIR__ . '/../bootstrap/app.php';
    echo json_encode([
        'status' => 'Laravel bootstrapped successfully',
        'app_class' => get_class($app),
    ]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'Laravel bootstrap failed',
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);
}
?>

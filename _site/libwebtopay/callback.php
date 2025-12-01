<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/WebToPay.php';

try {
    $response = WebToPay::validateAndParseData($_REQUEST, PROJECT_ID, PROJECT_PASSWORD);

    if (isset($response['status']) && ($response['status'] === '1' || $response['status'] === '3')) {
        // Mark the order as paid in your storage layer
        $orderId = $response['orderid'] ?? 'unknown';
        $logLine = sprintf("%s;PAYMENT_CONFIRMED;%s\n", date('c'), $orderId);
        file_put_contents(__DIR__ . '/payments.log', $logLine, FILE_APPEND);
        echo 'OK';
    } else {
        throw new Exception('Payment not successful');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo 'Error: ' . $e->getMessage();
}

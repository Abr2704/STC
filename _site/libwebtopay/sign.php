<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/WebToPay.php';

header('Content-Type: application/json');
// Allow same-origin and static-hosted sites to call this endpoint (e.g. GitHub Pages
// HTML submitting to a PHP backend elsewhere).
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, HEAD, OPTIONS');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Expose-Headers: Paysera-Ownership');
if (PAYSERA_OWNERSHIP !== '') {
    header('Paysera-Ownership: ' . PAYSERA_OWNERSHIP);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$allowedMethods = 'GET, POST, HEAD, OPTIONS';
header('Allow: ' . $allowedMethods);

if ($method === 'HEAD' || $method === 'OPTIONS') {
    // Hosting platforms sometimes probe endpoints with HEAD/OPTIONS. Respond gracefully.
    http_response_code(204);
    exit;
}

if ($method !== 'POST' && $method !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (PROJECT_PASSWORD === 'CHANGE_ME' || empty(PROJECT_PASSWORD)) {
    http_response_code(500);
    echo json_encode(['error' => 'Paysera password is not configured on the server']);
    exit;
}

$rawBody = file_get_contents('php://input');
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$input = [];

if ($method === 'POST') {
    if (stripos($contentType, 'application/json') !== false) {
        $input = json_decode($rawBody, true) ?: [];
    } elseif (!empty($_POST)) {
        $input = $_POST;
    }
}

if ($method === 'GET' && empty($input)) {
    $input = $_GET;
}

if (!is_array($input)) {
    $input = [];
}

$orderId = isset($input['orderId']) ? trim((string) $input['orderId']) : '';
$email = isset($input['email']) ? trim((string) $input['email']) : '';
$fullName = isset($input['fullName']) ? trim((string) $input['fullName']) : '';
$phone = isset($input['phone']) ? trim((string) $input['phone']) : '';

if ($orderId === '') {
    http_response_code(422);
    echo json_encode(['error' => 'orderId is required']);
    exit;
}

try {
    $baseUrl = (function (): string {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        return $scheme . '://' . $host . $path;
    })();

    $paymentUrl = WebToPay::buildRequestUrl([
        'projectid' => PROJECT_ID,
        'sign_password' => PROJECT_PASSWORD,
        'orderid' => $orderId,
        'amount' => APPLICATION_FEE_CENTS,
        'currency' => APPLICATION_CURRENCY,
        'country' => 'GB',
        'p_email' => $email,
        'p_firstname' => $fullName,
        'p_phone' => $phone,
        'accepturl' => $baseUrl . '/accept.php',
        'cancelurl' => $baseUrl . '/cancel.php',
        'callbackurl' => $baseUrl . '/callback.php',
        'test' => 0,
        'lang' => 'en',
        'payment' => 'card',
        'version' => WebToPay::VERSION,
    ]);

    $response = ['url' => $paymentUrl];

    if (isset($input['format']) && $input['format'] === 'text') {
        header('Content-Type: text/plain');
        echo $paymentUrl;
        exit;
    }

    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

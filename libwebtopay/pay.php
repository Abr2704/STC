<?php
// libwebtopay/pay.php

require_once __DIR__ . '/WebToPay.php';

function getBaseUrl(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'];
    $path   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    return $scheme . '://' . $host . $path;
}

$baseUrl = getBaseUrl();

// You can generate a simple order ID here.
// If you want to manually match payments to applications,
// you can keep this simple and use time + random.
$orderId = time() . '-' . mt_rand(1000, 9999);

// Application fee – example: 25.00 EUR = 2500 cents
$amountCents = 2500;
$currency    = 'EUR';

try {
    WebToPay::redirectToPayment([
        'projectid'     => YOUR_PROJECT_ID,        // e.g. 123456
        'sign_password' => 'YOUR_PROJECT_PASSWORD',
        'orderid'       => $orderId,
        'amount'        => $amountCents,
        'currency'      => $currency,
        'country'       => 'GB',                   // or another appropriate country code
        'accepturl'     => $baseUrl . '/accept.php',
        'cancelurl'     => $baseUrl . '/cancel.php',
        'callbackurl'   => $baseUrl . '/callback.php',
        'test'          => 0,                      // set to 1 while testing
    ]);
} catch (Exception $e) {
    echo 'Payment initialisation error: ' . htmlspecialchars($e->getMessage());
}

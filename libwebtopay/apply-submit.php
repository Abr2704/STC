<?php
// Handles form submissions and initiates a PaySera payment
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/WebToPay.php';

if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

function readField(string $key): string
{
    return trim($_POST[$key] ?? '');
}

function saveUploads(string $fieldName, string $uploadDir): array
{
    if (!isset($_FILES[$fieldName])) {
        return [];
    }

    $files = $_FILES[$fieldName];
    $saved = [];

    // Normalize single file structure to arrays
    $names = is_array($files['name']) ? $files['name'] : [$files['name']];
    $tmpNames = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
    $errors = is_array($files['error']) ? $files['error'] : [$files['error']];

    foreach ($names as $index => $originalName) {
        if ($errors[$index] !== UPLOAD_ERR_OK) {
            continue;
        }

        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
        $targetName = time() . '_' . $safeName;
        $targetPath = $uploadDir . $targetName;

        if (move_uploaded_file($tmpNames[$index], $targetPath)) {
            $saved[] = $targetName;
        }
    }

    return $saved;
}

function storeApplication(array $data): void
{
    $line = [
        date('c'),
        $data['order_id'],
        $data['first_name'],
        $data['last_name'],
        $data['email'],
        $data['mobile'],
        $data['course'],
        $data['highest_qualification'],
        implode(',', $data['uploads']['id_document'] ?? []),
        implode(',', $data['uploads']['qualification_files'] ?? []),
    ];

    $headerNeeded = !file_exists(APPLICATIONS_LOG);
    $fp = fopen(APPLICATIONS_LOG, 'ab');
    if ($fp !== false) {
        if ($headerNeeded) {
            fputcsv($fp, [
                'submitted_at', 'order_id', 'first_name', 'last_name',
                'email', 'mobile', 'course', 'highest_qualification',
                'id_documents', 'qualification_files',
            ], ';');
        }
        fputcsv($fp, $line, ';');
        fclose($fp);
    }
}

function getBaseUrl(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    return $scheme . '://' . $host . $path;
}

try {
    if (PROJECT_ID <= 0 || PROJECT_PASSWORD === 'CHANGE_ME') {
        throw new RuntimeException('Paysera credentials are not configured yet. Please set PROJECT_ID and PROJECT_PASSWORD in libwebtopay/config.php.');
    }

    $firstName = readField('first_names');
    $lastName = readField('surname');
    $email = readField('email');
    $mobile = readField('mobile');
    $course = readField('first_choice');
    $highestQualification = readField('highest_qualification');

    $uploads = [
        'id_document' => saveUploads('id_document', UPLOAD_DIR),
        'qualification_files' => saveUploads('qualification_files', UPLOAD_DIR),
    ];

    $orderId = date('YmdHis') . '-' . bin2hex(random_bytes(3));

    storeApplication([
        'order_id' => $orderId,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'mobile' => $mobile,
        'course' => $course,
        'highest_qualification' => $highestQualification,
        'uploads' => $uploads,
    ]);

    $baseUrl = getBaseUrl();

    $paymentUrl = WebToPay::buildRequestUrl([
        'projectid' => PROJECT_ID,
        'sign_password' => PROJECT_PASSWORD,
        'orderid' => $orderId,
        'amount' => APPLICATION_FEE_CENTS,
        'currency' => APPLICATION_CURRENCY,
        'country' => 'GB',
        'p_firstname' => $firstName,
        'p_lastname' => $lastName,
        'p_email' => $email,
        'p_phone' => $mobile,
        'accepturl' => $baseUrl . '/accept.php',
        'cancelurl' => $baseUrl . '/cancel.php',
        'callbackurl' => $baseUrl . '/callback.php',
        'test' => 1, // set to 0 for live payments
        'lang' => 'en',
        'version' => WebToPay::VERSION,
        'payment' => 'card',
    ]);
    header('Location: ' . $paymentUrl);
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="0;url=<?php echo htmlspecialchars($paymentUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <title>Redirecting to Paysera…</title>
</head>
<body>
    <p>We are redirecting you to Paysera to complete your payment. If you are not redirected automatically, <a href="<?php echo htmlspecialchars($paymentUrl, ENT_QUOTES, 'UTF-8'); ?>">click here</a>.</p>
</body>
</html>
<?php
} catch (Exception $e) {
    http_response_code(500);
    echo 'Error: ' . htmlspecialchars($e->getMessage());
}

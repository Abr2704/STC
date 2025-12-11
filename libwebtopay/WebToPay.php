<?php
/**
 * Lightweight Paysera gateway helper inspired by the official lib-webtopay package.
 * This simplified version supports generating redirect URLs and validating callback payloads
 * using the documented sign algorithm.
 */
class WebToPay
{
    public const VERSION = '1.0-lite';
    public const PAY_URL = 'https://www.paysera.com/pay/';

    /**
     * Builds the payment URL and redirects the browser.
     *
     * @throws Exception when required parameters are missing.
     */
    public static function redirectToPayment(array $params): void
    {
        $url = self::buildRequestUrl($params);
        header('Location: ' . $url);
        exit;
    }

    /**
     * Builds the Paysera payment URL with encoded data and signature.
     *
     * @return string
     * @throws Exception
     */
    public static function buildRequestUrl(array $params): string
    {
        if (!isset($params['sign_password'])) {
            throw new Exception('sign_password is required');
        }
        if (!isset($params['projectid'])) {
            throw new Exception('projectid is required');
        }

        $signPassword = (string) $params['sign_password'];
        $data = self::prepareData($params);
        $sign = self::signRequest($data, $signPassword);

        return self::PAY_URL . '?' . http_build_query([
            'data' => $data,
            'sign' => $sign,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Validates Paysera callback data and returns decoded payload.
     *
     * @throws Exception when validation fails.
     */
    public static function validateAndParseData(array $request, int $projectId, string $signPassword): array
    {
        $data = $request['data'] ?? null;
        $sign = $request['sign'] ?? null;

        if (!$data || !$sign) {
            throw new Exception('Missing data or sign parameters');
        }

        $expectedSign = self::signRequest($data, $signPassword);
        if (!hash_equals($expectedSign, (string) $sign)) {
            throw new Exception('Invalid sign');
        }

        $decoded = base64_decode((string) $data, true);
        if ($decoded === false) {
            throw new Exception('Failed to decode data payload');
        }

        parse_str($decoded, $response);
        if (!is_array($response) || empty($response['projectid'])) {
            throw new Exception('Malformed response data');
        }

        if ((int) $response['projectid'] !== $projectId) {
            throw new Exception('Project ID mismatch');
        }

        return $response;
    }

    /**
     * Builds a base64 encoded string of parameters ready for signing.
     */
    protected static function prepareData(array $params): string
    {
        $prepared = $params;
        unset($prepared['sign_password']);
        ksort($prepared, SORT_STRING);

        // Follow Paysera's canonical encoding: RFC1738 (spaces encoded as "+")
        // to ensure our signature matches their verification on the gateway.
        $encoded = http_build_query($prepared);

        return base64_encode($encoded);
    }

    protected static function signRequest(string $data, string $signPassword): string
    {
        return md5($signPassword . $data . $signPassword);
    }
}

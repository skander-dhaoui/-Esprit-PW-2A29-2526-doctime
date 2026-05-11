<?php
declare(strict_types=1);

/**
 * reCAPTCHA v2 case à cocher (« Je ne suis pas un robot »).
 *
 * Production : renseigner RECAPTCHA_SITE_KEY et RECAPTCHA_SECRET_KEY dans .env
 * (https://www.google.com/recaptcha/admin — type v2 « Je ne suis pas un robot »).
 *
 * Développement local : sans clés, les clés de test officielles Google sont utilisées
 * automatiquement (HTTP_HOST contenant localhost ou 127.0.0.1).
 */
class RecaptchaConfig
{
    /** @see https://developers.google.com/recaptcha/docs/faq — toujours acceptées par l’API */
    private const TEST_SITE_KEY = '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI';

    private const TEST_SECRET_KEY = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe';

    private static function envString(string $key): string
    {
        $v = getenv($key);
        if ($v === false || $v === null) {
            return '';
        }

        return trim((string) $v);
    }

    /**
     * Clés de test uniquement en local (sauf désactivation explicite).
     */
    private static function useGoogleTestKeysForLocalDev(): bool
    {
        if (self::envString('RECAPTCHA_DISABLE_TEST_KEYS') === '1') {
            return false;
        }
        if (self::envString('RECAPTCHA_FORCE_TEST_KEYS') === '1') {
            return true;
        }

        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));

        if ($host === '') {
            return false;
        }
        if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
            return true;
        }
        $suffix = '.local';
        $suffixLen = strlen($suffix);

        return strlen($host) > $suffixLen && substr($host, -$suffixLen) === $suffix;
    }

    public static function siteKey(): string
    {
        $v = self::envString('RECAPTCHA_SITE_KEY');
        if ($v !== '' && strpos($v, 'REPLACE_ME_') !== 0) {
            return $v;
        }
        if (self::useGoogleTestKeysForLocalDev()) {
            return self::TEST_SITE_KEY;
        }

        return '';
    }

    public static function secretKey(): string
    {
        $v = self::envString('RECAPTCHA_SECRET_KEY');
        if ($v !== '' && strpos($v, 'REPLACE_ME_') !== 0) {
            return $v;
        }
        if (self::useGoogleTestKeysForLocalDev()) {
            return self::TEST_SECRET_KEY;
        }

        return '';
    }

    public static function isConfigured(): bool
    {
        return self::siteKey() !== '' && self::secretKey() !== '';
    }

    /** @return array{success: bool, error?: string} */
    public static function verify(string $response, string $remoteIp = ''): array
    {
        $secret = self::secretKey();
        if ($secret === '') {
            return ['success' => false, 'error' => 'reCAPTCHA non configuré.'];
        }
        $response = trim($response);
        if ($response === '') {
            return ['success' => false, 'error' => 'Cochez « Je ne suis pas un robot ».'];
        }

        $payload = http_build_query([
            'secret'   => $secret,
            'response' => $response,
            'remoteip' => $remoteIp,
        ]);

        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $payload,
                'timeout' => 10,
            ],
        ]);
        $raw = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $ctx);
        if ($raw === false) {
            return ['success' => false, 'error' => 'Impossible de joindre Google reCAPTCHA.'];
        }
        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data['success'])) {
            return ['success' => false, 'error' => 'reCAPTCHA invalide. Réessayez.'];
        }

        return ['success' => true];
    }
}

<?php
declare(strict_types=1);

/**
 * Journal applicatif (auth, captcha, e-mail) — une ligne JSON par événement dans logs/app.log
 */
final class AppLogger
{
    private static function logPath(): string
    {
        return dirname(__DIR__) . '/logs/app.log';
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function log(string $channel, string $message, array $context = []): void
    {
        $dir = dirname(self::logPath());
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $line = json_encode(
            [
                'ts'    => date('c'),
                'ch'    => $channel,
                'msg'   => $message,
                'ctx'   => $context,
                'ip'    => $_SERVER['REMOTE_ADDR'] ?? '',
                'uri'   => $_SERVER['REQUEST_URI'] ?? '',
            ],
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
        if ($line === false) {
            return;
        }

        @file_put_contents(self::logPath(), $line . "\n", FILE_APPEND | LOCK_EX);
    }
}

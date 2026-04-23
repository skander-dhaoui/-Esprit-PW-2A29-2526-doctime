<?php
declare(strict_types=1);

if (class_exists('SocialAuthConfig')) {
    return;
}

class SocialAuthConfig
{
    public static function all(): array
    {
        return [
            'google' => [
                'label'         => 'Google',
                'client_id'     => self::env('GOOGLE_CLIENT_ID'),
                'client_secret' => self::env('GOOGLE_CLIENT_SECRET'),
                'scope'         => 'openid email profile',
                'auth_url'      => 'https://accounts.google.com/o/oauth2/v2/auth',
                'token_url'     => 'https://oauth2.googleapis.com/token',
                'user_url'      => 'https://openidconnect.googleapis.com/v1/userinfo',
            ],
            'facebook' => [
                'label'         => 'Facebook',
                'client_id'     => self::env('FACEBOOK_CLIENT_ID'),
                'client_secret' => self::env('FACEBOOK_CLIENT_SECRET'),
                'scope'         => 'email public_profile',
                'auth_url'      => 'https://www.facebook.com/dialog/oauth',
                'token_url'     => 'https://graph.facebook.com/oauth/access_token',
                'user_url'      => 'https://graph.facebook.com/me?fields=id,first_name,last_name,name,email,picture.type(large)',
            ],
            'instagram' => [
                'label'         => 'Instagram',
                'client_id'     => self::env('INSTAGRAM_CLIENT_ID'),
                'client_secret' => self::env('INSTAGRAM_CLIENT_SECRET'),
                'scope'         => 'user_profile',
                'auth_url'      => 'https://api.instagram.com/oauth/authorize',
                'token_url'     => 'https://api.instagram.com/oauth/access_token',
                'user_url'      => 'https://graph.instagram.com/me?fields=id,username,account_type',
            ],
        ];
    }

    public static function get(string $provider): ?array
    {
        $providers = self::all();
        return $providers[$provider] ?? null;
    }

    public static function isConfigured(string $provider): bool
    {
        $config = self::get($provider);
        if ($config === null) {
            return false;
        }

        return self::isRealSecret($config['client_id']) && self::isRealSecret($config['client_secret']);
    }

    private static function env(string $key, string $default = ''): string
    {
        $value = getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }

        return trim((string) $value);
    }

    private static function isRealSecret(string $value): bool
    {
        $value = trim($value);

        if ($value === '') {
            return false;
        }

        if (str_starts_with($value, 'REPLACE_ME_')) {
            return false;
        }

        return true;
    }
}

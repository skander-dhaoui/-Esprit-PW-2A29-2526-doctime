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
            'github' => [
                'label'         => 'GitHub',
                'client_id'     => self::env('GITHUB_CLIENT_ID'),
                'client_secret' => self::env('GITHUB_CLIENT_SECRET'),
                'scope'         => 'read:user user:email',
                'auth_url'      => 'https://github.com/login/oauth/authorize',
                'token_url'     => 'https://github.com/login/oauth/access_token',
                'user_url'      => 'https://api.github.com/user',
                'email_url'     => 'https://api.github.com/user/emails',
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
            'linkedin' => [
                'label'         => 'LinkedIn',
                'client_id'     => self::env('LINKEDIN_CLIENT_ID'),
                'client_secret' => self::env('LINKEDIN_CLIENT_SECRET'),
                'scope'         => self::env('LINKEDIN_SCOPE', 'profile email openid'),
                'auth_url'      => 'https://www.linkedin.com/oauth/v2/authorization',
                'token_url'     => 'https://www.linkedin.com/oauth/v2/accessToken',
                'user_url'      => 'https://api.linkedin.com/v2/me',
                'auth_params'   => [
                    'response_type' => 'code',
                ],
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

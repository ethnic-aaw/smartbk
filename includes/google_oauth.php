<?php
/**
 * Google OAuth 2.0 Wrapper untuk Smart BK
 * Memerlukan: composer require google/apiclient
 */

require_once __DIR__ . '/../vendor/autoload.php';

class GoogleOAuth
{
    private Google\Client $client;
    private string $redirectUri;
    private array $scopes = [
        'openid',
        'https://www.googleapis.com/auth/userinfo.email',
        'https://www.googleapis.com/auth/userinfo.profile'
    ];

    public function __construct()
    {
        if (!defined('APP_BASE') && file_exists(__DIR__ . '/../config/app.php')) {
            require_once __DIR__ . '/../config/app.php';
        }
        $envRedirect = getenv('GOOGLE_REDIRECT_URI');
        if ($envRedirect === false || $envRedirect === '') {
            $envRedirect = $_ENV['GOOGLE_REDIRECT_URI'] ?? '';
        }
        if ($envRedirect !== '' && $envRedirect !== false) {
            $this->redirectUri = $envRedirect;
        } elseif (defined('OAUTH_REDIRECT_URI')) {
            $this->redirectUri = OAUTH_REDIRECT_URI;
        } else {
            $this->redirectUri = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://')
                . ($_SERVER['HTTP_HOST'] ?? 'localhost')
                . rtrim(defined('APP_BASE') ? APP_BASE : '/', '/')
                . '/auth/google_callback.php';
        }

        $this->client = new Google\Client();
        $clientId = getenv('GOOGLE_CLIENT_ID');
        if ($clientId === false || $clientId === '') {
            $clientId = $_ENV['GOOGLE_CLIENT_ID'] ?? '';
        }
        $clientSecret = getenv('GOOGLE_CLIENT_SECRET');
        if ($clientSecret === false || $clientSecret === '') {
            $clientSecret = $_ENV['GOOGLE_CLIENT_SECRET'] ?? '';
        }
        if (empty($clientId) || empty($clientSecret) || str_contains($clientId, 'your-client-id')) {
            error_log('Google OAuth credentials are not configured.');
        }
        $this->client->setClientId($clientId);
        $this->client->setClientSecret($clientSecret);
        $this->client->setRedirectUri($this->redirectUri);
        $this->client->setScopes($this->scopes);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('select_account consent');
        $this->client->setIncludeGrantedScopes(true);
    }

    /**
     * Generate URL untuk redirect ke Google OAuth
     */
    public function getAuthUrl(): string
    {
        $state = bin2hex(random_bytes(32));
        $_SESSION['oauth_state'] = $state;
        $this->client->setState($state);
        return $this->client->createAuthUrl();
    }

    /**
     * Verifikasi state parameter untuk防止 CSRF
     */
    public function verifyState(string $state): bool
    {
        $expected = $_SESSION['oauth_state'] ?? '';
        unset($_SESSION['oauth_state']);
        return $expected !== '' && hash_equals($expected, $state);
    }

    /**
     * Tukar authorization code dengan access token
     */
    public function fetchToken(string $code): array
    {
        $token = $this->client->fetchAccessTokenWithAuthCode($code);
        
        if (isset($token['error'])) {
            throw new RuntimeException('Gagal mendapatkan token: ' . ($token['error_description'] ?? $token['error']));
        }
        
        return $token;
    }

    /**
     * Ambil info user dari Google (email, nama, google_id, dll)
     */
    public function getUserInfo(array $token): array
    {
        $this->client->setAccessToken($token);
        
        $oauth2 = new Google\Service\Oauth2($this->client);
        $userInfo = $oauth2->userinfo->get();
        
        return [
            'google_id' => $userInfo->id ?? '',
            'email' => $userInfo->email ?? '',
            'name' => $userInfo->name ?? '',
            'given_name' => $userInfo->givenName ?? '',
            'family_name' => $userInfo->familyName ?? '',
            'picture' => $userInfo->picture ?? '',
            'verified_email' => $userInfo->verifiedEmail ?? false,
            'locale' => $userInfo->locale ?? '',
        ];
    }

    public function validateDomain(string $email): bool
    {
        return (bool) preg_match('/@([a-z0-9.-]+\.)?belajar\.id$/i', trim($email));
    }

    public function isConfigured(): bool
    {
        $cid = getenv('GOOGLE_CLIENT_ID');
        if ($cid === false || $cid === '') $cid = $_ENV['GOOGLE_CLIENT_ID'] ?? '';
        $sec = getenv('GOOGLE_CLIENT_SECRET');
        if ($sec === false || $sec === '') $sec = $_ENV['GOOGLE_CLIENT_SECRET'] ?? '';
        return $cid !== '' && $sec !== '' && !str_contains($cid, 'your-client-id');
    }

    /**
     * Get redirect URI untuk debugging
     */
    public function getRedirectUri(): string
    {
        return $this->redirectUri;
    }
}

/**
 * Helper function untuk mendapatkan instance GoogleOAuth
 */
function getGoogleOAuth(): GoogleOAuth
{
    return new GoogleOAuth();
}
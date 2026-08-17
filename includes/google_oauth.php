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
        $this->redirectUri = getenv('GOOGLE_REDIRECT_URI') 
            ?: (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') 
                . ($_SERVER['HTTP_HOST'] ?? 'localhost') 
                . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') 
                . '/auth/google_callback.php';

        $this->client = new Google\Client();
        $this->client->setClientId(getenv('GOOGLE_CLIENT_ID') ?? '');
        $this->client->setClientSecret(getenv('GOOGLE_CLIENT_SECRET') ?? '');
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
        return $this->client->createAuthUrl() . '&state=' . $state;
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

    /**
     * Validasi domain email (@belajar.id)
     */
    public function validateDomain(string $email): bool
    {
        return str_ends_with(strtolower($email), '@belajar.id');
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
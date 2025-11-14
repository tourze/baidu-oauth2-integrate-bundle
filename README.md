# Baidu OAuth2 Integration Bundle

[English](README.md) | [中文](README.zh-CN.md)

A Symfony bundle that provides Baidu OAuth2 integration for Symfony applications with database-backed configuration and Entity-based management.

## Features

- 🔐 **Complete OAuth2 Flow**: Full implementation of Baidu OAuth2 authorization process
- 🗄️ **Database Configuration**: Entity-based configuration management supporting multiple and dynamic configs
- 🏗️ **Symfony Integration**: Fully compatible with Symfony 7.x ecosystem
- 🛡️ **State Management**: Built-in CSRF protection and state token management
- 📊 **EasyAdmin Backend**: Complete admin interface for management
- 🔧 **Flexible Configuration**: Support for custom scopes and redirect URIs
- 🧪 **Complete Testing**: Comprehensive unit and integration tests
- 📝 **Detailed Logging**: Full debugging and error logging

## Installation

Install using Composer:

```bash
composer require tourze/baidu-oauth2-integrate-bundle
```

## Quick Start

### 1. Enable Bundle

Add to your `config/bundles.php`:

```php
return [
    // ...
    Tourze\BaiduOauth2IntegrateBundle\BaiduOauth2IntegrateBundle::class => ['all' => true],
];
```

### 2. Database Configuration

The bundle provides three main entities:

- `BaiduOAuth2Config`: OAuth2 application configuration
- `BaiduOAuth2State`: State token management
- `BaiduOAuth2User`: User information storage

Create and run database migrations:

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

### 3. Basic Usage

#### Generate Authorization URL

```php
use Tourze\BaiduOauth2IntegrateBundle\Service\BaiduOAuth2Service;

class AuthController extends AbstractController
{
    public function __construct(
        private BaiduOAuth2Service $oauth2Service
    ) {}

    #[Route('/baidu/login', name: 'baidu_login')]
    public function login(): Response
    {
        $authUrl = $this->oauth2Service->generateAuthorizationUrl();
        return $this->redirect($authUrl);
    }
}
```

#### Handle Callback

```php
#[Route('/baidu/callback', name: 'baidu_callback')]
public function callback(Request $request): Response
{
    $code = $request->query->get('code');
    $state = $request->query->get('state');

    try {
        $user = $this->oauth2Service->handleCallback($code, $state);
        // Handle user login logic
        return $this->redirectToRoute('dashboard');
    } catch (BaiduOAuth2Exception $e) {
        // Handle OAuth2 errors
        return $this->redirectToRoute('login_failed');
    }
}
```

## Configuration

### Basic Configuration

Add to `config/packages/baidu_oauth2.yaml`:

```yaml
baidu_oauth2_integrate:
    # Redirect URI (optional, defaults to route 'baidu_oauth2_callback')
    redirect_uri: 'https://your-domain.com/baidu/callback'

    # Default scope (optional)
    default_scope: 'basic'

    # State token TTL in seconds
    state_ttl: 600

    # Enable debug logging
    debug: false
```

### EasyAdmin Backend Management

The bundle automatically integrates with EasyAdmin, providing:
- OAuth2 configuration management
- User information management
- State token management

## API Documentation

### Main Services

#### BaiduOAuth2Service

The main OAuth2 flow service.

```php
class BaiduOAuth2Service
{
    // Generate authorization URL
    public function generateAuthorizationUrl(?string $sessionId = null): string

    // Handle authorization callback
    public function handleCallback(string $code, string $state): BaiduOAuth2User

    // Refresh access token
    public function refreshToken(string $refreshToken): array
}
```

#### BaiduApiClient

Baidu API client for calling Baidu Open Platform APIs.

```php
class BaiduApiClient
{
    // Get user information
    public function getUserInfo(string $accessToken): array

    // Refresh token
    public function refreshToken(string $refreshToken, string $clientId, string $clientSecret): array
}
```

### Routes

The bundle automatically registers the following routes:

- `baidu_oauth2_login`: Baidu login entry point
- `baidu_oauth2_callback`: Baidu authorization callback

## Entity Documentation

### BaiduOAuth2Config

OAuth2 application configuration entity:

```php
class BaiduOAuth2Config
{
    private ?int $id;                    // Configuration ID
    private string $clientId;            // Baidu API Key
    private string $clientSecret;        // Baidu Secret Key
    private ?string $scope;              // Authorization scope
    private bool $valid;                 // Is enabled
    private \DateTime $createdAt;        // Created time
    private \DateTime $updatedAt;        // Updated time
}
```

### BaiduOAuth2User

User information entity:

```php
class BaiduOAuth2User
{
    private ?int $id;                    // User ID
    private string $openid;              // Baidu OpenID
    private ?string $unionid;            // Baidu UnionID
    private ?string $accessToken;        // Access token
    private ?string $refreshToken;       // Refresh token
    private ?\DateTime $tokenExpiresAt;  // Token expiration time
    private ?array $userInfo;            // User information
    private \DateTime $createdAt;        // Created time
    private \DateTime $updatedAt;        // Updated time
}
```

### BaiduOAuth2State

State token entity:

```php
class BaiduOAuth2State
{
    private ?int $id;                    // State ID
    private string $state;               // State token
    private ?string $sessionId;          // Session ID
    private bool $used;                  // Is used
    private \DateTime $expiresAt;        // Expiration time
    private BaiduOAuth2Config $config;   // Associated configuration
    private \DateTime $createdAt;        // Created time
    private \DateTime $updatedAt;        // Updated time
}
```

## Testing

Run the test suite:

```bash
# Run all tests
php bin/console phpunit

# Run specific test
php bin/console phpunit tests/Service/BaiduOAuth2ServiceTest.php
```

## Events

The bundle provides the following Symfony events:

- `BaiduOAuth2TokenReceivedEvent`: Token received successfully
- `BaiduOAuth2UserCreatedEvent`: User information created
- `BaiduOAuth2TokenRefreshedEvent`: Token refreshed successfully

## Error Handling

The bundle provides dedicated exception classes:

```php
use Tourze\BaiduOauth2IntegrateBundle\Exception\BaiduOAuth2Exception;

// Catch OAuth2 related errors
try {
    $user = $oauth2Service->handleCallback($code, $state);
} catch (BaiduOAuth2Exception $e) {
    // Handle error
    $this->logger->error('Baidu OAuth2 error: ' . $e->getMessage());
}
```

## Logging Configuration

Configure logging:

```yaml
# config/packages/monolog.yaml
monolog:
    handlers:
        baidu_oauth2:
            type: stream
            path: '%kernel.logs_dir%/baidu_oauth2.log'
            level: info
            channels: ['baidu_oauth2']
```

## Security Considerations

1. **Redirect URI Security**: Ensure redirect URIs are properly configured in Baidu Open Platform
2. **State Token Validation**: Bundle automatically handles state token validation to prevent CSRF attacks
3. **Token Security**: Access and refresh tokens are encrypted and stored in database
4. **HTTPS**: Production environment must use HTTPS
5. **Key Management**: Properly secure API Key and Secret Key

## License

This project is licensed under the [MIT License](LICENSE).

## Contributing

Issues and Pull Requests are welcome. Please ensure:

1. Follow PSR-12 coding standards
2. Add appropriate tests
3. Update relevant documentation

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version updates.

## Support

- 📧 Email: support@tourze.com
- 🐛 Issue Reporting: [GitHub Issues](https://github.com/tourze/php-monorepo/issues)
- 📖 Documentation: [Project Wiki](https://github.com/tourze/php-monorepo/wiki)

## Related Links

- [Baidu Open Platform OAuth2 Documentation](https://openauth.baidu.com/doc/doc.html)
- [Symfony Documentation](https://symfony.com/doc)
- [EasyAdmin Documentation](https://symfony.com/doc/current/bundles/EasyAdminBundle/index.html)
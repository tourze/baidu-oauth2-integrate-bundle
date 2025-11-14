# 百度 OAuth2 集成 Bundle

[English](README.md) | [中文](README.zh-CN.md)

一个为 Symfony 应用程序提供百度 OAuth2 集成功能的 Bundle，支持基于数据库配置的 OAuth2 认证流程。

## 功能特性

- 🔐 **完整的 OAuth2 流程支持**：实现标准的百度 OAuth2 授权流程
- 🗄️ **数据库配置**：基于 Entity 的配置管理，支持多配置和动态配置
- 🏗️ **Symfony 集成**：完全兼容 Symfony 7.x 生态系统
- 🛡️ **状态管理**：内置 CSRF 防护和状态令牌管理
- 📊 **EasyAdmin 后台**：提供完整的后台管理界面
- 🔧 **灵活配置**：支持自定义授权范围和回调地址
- 🧪 **完整测试**：包含完整的单元测试和集成测试
- 📝 **详细日志**：完整的调试和错误日志记录

## 安装

使用 Composer 安装：

```bash
composer require tourze/baidu-oauth2-integrate-bundle
```

## 快速开始

### 1. 启用 Bundle

在您的 `config/bundles.php` 文件中添加：

```php
return [
    // ...
    Tourze\BaiduOauth2IntegrateBundle\BaiduOauth2IntegrateBundle::class => ['all' => true],
];
```

### 2. 数据库配置

Bundle 提供了三个主要的 Entity：

- `BaiduOAuth2Config`: OAuth2 应用配置
- `BaiduOAuth2State`: 状态令牌管理
- `BaiduOAuth2User`: 用户信息存储

创建并运行数据库迁移：

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

### 3. 基础使用

#### 获取授权 URL

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

#### 处理回调

```php
#[Route('/baidu/callback', name: 'baidu_callback')]
public function callback(Request $request): Response
{
    $code = $request->query->get('code');
    $state = $request->query->get('state');

    try {
        $user = $this->oauth2Service->handleCallback($code, $state);
        // 处理用户登录逻辑
        return $this->redirectToRoute('dashboard');
    } catch (BaiduOAuth2Exception $e) {
        // 处理 OAuth2 错误
        return $this->redirectToRoute('login_failed');
    }
}
```

## 配置

### 基础配置

在 `config/packages/baidu_oauth2.yaml` 中：

```yaml
baidu_oauth2_integrate:
    # 回调地址（可选，默认使用路由 'baidu_oauth2_callback'）
    redirect_uri: 'https://your-domain.com/baidu/callback'

    # 授权范围（可选）
    default_scope: 'basic'

    # 状态令牌过期时间（秒）
    state_ttl: 600

    # 是否启用调试日志
    debug: false
```

### EasyAdmin 后台管理

Bundle 自动集成 EasyAdmin，提供以下管理界面：
- OAuth2 配置管理
- 用户信息管理
- 状态令牌管理

## API 文档

### 主要服务

#### BaiduOAuth2Service

主要的 OAuth2 流程服务。

```php
class BaiduOAuth2Service
{
    // 生成授权 URL
    public function generateAuthorizationUrl(?string $sessionId = null): string

    // 处理授权回调
    public function handleCallback(string $code, string $state): BaiduOAuth2User

    // 刷新访问令牌
    public function refreshToken(string $refreshToken): array
}
```

#### BaiduApiClient

百度 API 客户端，用于调用百度开放平台接口。

```php
class BaiduApiClient
{
    // 获取用户信息
    public function getUserInfo(string $accessToken): array

    // 刷新令牌
    public function refreshToken(string $refreshToken, string $clientId, string $clientSecret): array
}
```

### 路由

Bundle 自动注册以下路由：

- `baidu_oauth2_login`: 百度登录入口
- `baidu_oauth2_callback`: 百度授权回调

## Entity 说明

### BaiduOAuth2Config

OAuth2 应用配置实体：

```php
class BaiduOAuth2Config
{
    private ?int $id;                    // 配置 ID
    private string $clientId;            // 百度 API Key
    private string $clientSecret;        // 百度 Secret Key
    private ?string $scope;              // 授权范围
    private bool $valid;                 // 是否启用
    private \DateTime $createdAt;        // 创建时间
    private \DateTime $updatedAt;        // 更新时间
}
```

### BaiduOAuth2User

用户信息实体：

```php
class BaiduOAuth2User
{
    private ?int $id;                    // 用户 ID
    private string $openid;              // 百度 OpenID
    private ?string $unionid;            // 百度 UnionID
    private ?string $accessToken;        // 访问令牌
    private ?string $refreshToken;       // 刷新令牌
    private ?\DateTime $tokenExpiresAt;  // 令牌过期时间
    private ?array $userInfo;            // 用户信息
    private \DateTime $createdAt;        // 创建时间
    private \DateTime $updatedAt;        // 更新时间
}
```

### BaiduOAuth2State

状态令牌实体：

```php
class BaiduOAuth2State
{
    private ?int $id;                    // 状态 ID
    private string $state;               // 状态令牌
    private ?string $sessionId;          // 会话 ID
    private bool $used;                  // 是否已使用
    private \DateTime $expiresAt;        // 过期时间
    private BaiduOAuth2Config $config;   // 关联配置
    private \DateTime $createdAt;        // 创建时间
    private \DateTime $updatedAt;        // 更新时间
}
```

## 测试

运行测试套件：

```bash
# 运行所有测试
php bin/console phpunit

# 运行特定测试
php bin/console phpunit tests/Service/BaiduOAuth2ServiceTest.php
```

## 事件

Bundle 提供以下 Symfony 事件：

- `BaiduOAuth2TokenReceivedEvent`: 令牌获取成功
- `BaiduOAuth2UserCreatedEvent`: 用户信息创建
- `BaiduOAuth2TokenRefreshedEvent`: 令牌刷新成功

## 错误处理

Bundle 提供专门的异常类：

```php
use Tourze\BaiduOauth2IntegrateBundle\Exception\BaiduOAuth2Exception;

// 捕获 OAuth2 相关错误
try {
    $user = $oauth2Service->handleCallback($code, $state);
} catch (BaiduOAuth2Exception $e) {
    // 处理错误
    $this->logger->error('百度 OAuth2 错误: ' . $e->getMessage());
}
```

## 日志配置

配置日志记录：

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

## 安全注意事项

1. **回调地址安全**：确保回调地址在百度开放平台正确配置
2. **状态令牌验证**：Bundle 自动处理状态令牌验证，防止 CSRF 攻击
3. **令牌安全**：访问令牌和刷新令牌加密存储在数据库中
4. **HTTPS**：生产环境必须使用 HTTPS
5. **密钥管理**：妥善保管 API Key 和 Secret Key

## 许可证

本项目采用 [MIT 许可证](LICENSE)。

## 贡献

欢迎提交 Issue 和 Pull Request。请确保：

1. 遵循 PSR-12 编码标准
2. 添加适当的测试
3. 更新相关文档

## 更新日志

查看 [CHANGELOG.md](CHANGELOG.md) 了解版本更新信息。

## 支持

- 📧 邮箱：support@tourze.com
- 🐛 问题反馈：[GitHub Issues](https://github.com/tourze/php-monorepo/issues)
- 📖 详细文档：[项目 Wiki](https://github.com/tourze/php-monorepo/wiki)

## 相关链接

- [百度开放平台 OAuth2 文档](https://openauth.baidu.com/doc/doc.html)
- [Symfony 官方文档](https://symfony.com/doc)
- [EasyAdmin 文档](https://symfony.com/doc/current/bundles/EasyAdminBundle/index.html)
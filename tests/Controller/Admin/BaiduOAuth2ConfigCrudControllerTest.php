<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\Tests\Controller\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\BaiduOauth2IntegrateBundle\Controller\Admin\BaiduOAuth2ConfigCrudController;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2Config;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(BaiduOAuth2ConfigCrudController::class)]
#[RunTestsInSeparateProcesses]
final class BaiduOAuth2ConfigCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    public function testEntityFqcn(): void
    {
        $this->assertEquals(BaiduOAuth2Config::class, BaiduOAuth2ConfigCrudController::getEntityFqcn());
    }

    public function testValidationErrors(): void
    {
        $client = self::createAuthenticatedClient();

        // 创建一个无效的实体进行验证测试
        $config = new BaiduOAuth2Config();
        // 不设置必填字段 clientId 和 clientSecret，应该触发验证错误

        $validator = self::getContainer()->get('validator');
        /** @var ValidatorInterface $validator */
        $violations = $validator->validate($config);

        // 验证应该有验证错误
        $this->assertGreaterThan(0, $violations->count(), 'Expected validation errors for empty required fields');

        // 验证特定字段的错误
        $clientIdViolations = $validator->validateProperty($config, 'clientId');
        $this->assertGreaterThan(0, $clientIdViolations->count(), 'clientId should not be blank');

        $clientSecretViolations = $validator->validateProperty($config, 'clientSecret');
        $this->assertGreaterThan(0, $clientSecretViolations->count(), 'clientSecret should not be blank');
    }

    protected function getControllerService(): BaiduOAuth2ConfigCrudController
    {
        return self::getService(BaiduOAuth2ConfigCrudController::class);
    }


    /**
     * @return \Generator<string, array{string}, void, void>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield 'API Key' => ['API Key'];
        yield 'Secret Key' => ['Secret Key'];
        yield '是否启用' => ['是否启用'];
        yield '创建时间' => ['创建时间'];
    }

    /**
     * @return \Generator<string, array{string}, void, void>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'clientId' => ['clientId'];
        yield 'clientSecret' => ['clientSecret'];
        yield 'scope' => ['scope'];
        yield 'valid' => ['valid'];
    }

    /**
     * @return \Generator<string, array{string}, void, void>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'clientId' => ['clientId'];
        yield 'clientSecret' => ['clientSecret'];
        yield 'scope' => ['scope'];
        yield 'valid' => ['valid'];
    }
}

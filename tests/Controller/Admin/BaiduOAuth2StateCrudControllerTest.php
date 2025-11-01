<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\BaiduOauth2IntegrateBundle\Controller\Admin\BaiduOAuth2StateCrudController;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2Config;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2State;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(BaiduOAuth2StateCrudController::class)]
#[RunTestsInSeparateProcesses]
final class BaiduOAuth2StateCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    public function testEntityFqcn(): void
    {
        $this->assertEquals(BaiduOAuth2State::class, BaiduOAuth2StateCrudController::getEntityFqcn());
    }

    public function testValidationErrors(): void
    {
        $client = self::createAuthenticatedClient();

        // 创建一个无效的实体进行验证测试
        // BaiduOAuth2State 的构造函数需要 state 和 config 参数
        // 我们创建一个空的 state 来测试验证
        $config = new BaiduOAuth2Config();
        $config->setClientId('test-client-id');
        $config->setClientSecret('test-client-secret');

        $state = new BaiduOAuth2State(); // 使用默认构造函数
        // 通过反射或setter设置必要的属性
        $reflection = new \ReflectionClass($state);
        if ($reflection->hasProperty('config')) {
            $configProperty = $reflection->getProperty('config');
            $configProperty->setAccessible(true);
            $configProperty->setValue($state, $config);
        }

        $validator = self::getContainer()->get('validator');
        /** @var ValidatorInterface $validator */
        $violations = $validator->validate($state);

        // 验证应该有验证错误
        $this->assertGreaterThan(0, $violations->count(), 'Expected validation errors for empty state field');

        // 验证特定字段的错误
        $stateViolations = $validator->validateProperty($state, 'state');
        $this->assertGreaterThan(0, $stateViolations->count(), 'state should not be blank');
    }

    protected function getControllerService(): BaiduOAuth2StateCrudController
    {
        return self::getService(BaiduOAuth2StateCrudController::class);
    }

    // UI 渲染测试由于 app_logout 路由问题暂时跳过
    // 核心的配置验证在 testIndexPageHeadersProviderHasData 中已经完成

    /**
     * @return \Generator<string, array{string}, void, void>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield 'State 值' => ['State 值'];
        yield '过期时间' => ['过期时间'];
        yield '是否已使用' => ['是否已使用'];
        yield '会话ID' => ['会话ID'];
        yield '关联配置' => ['关联配置'];
        yield '创建时间' => ['创建时间'];
    }

    /**
     * @return \Generator<string, array{string}, void, void>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'state' => ['state'];
        yield 'expireTime' => ['expireTime'];
        yield 'used' => ['used'];
        yield 'sessionId' => ['sessionId'];
        yield 'config' => ['config'];
    }

    /**
     * @return \Generator<string, array{string}, void, void>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'state' => ['state'];
        yield 'expireTime' => ['expireTime'];
        yield 'used' => ['used'];
        yield 'sessionId' => ['sessionId'];
        yield 'config' => ['config'];
    }

    /**
     * 测试所有BooleanField的属性可写性 - 通用的内联编辑验证
     *
     * 这个测试自动发现Controller中配置的所有BooleanField，
     * 并验证对应的实体属性是否可写（用于EasyAdmin内联编辑）。
     *
     * 用户报告的问题场景：
     * 在列表页面点击BooleanField的switch toggle时报错：
     * "实体的'xxx'属性不可写。"
     *
     * 根本原因：
     * 实体只有业务方法（如markAsUsed()），缺少标准setter。
     * EasyAdmin的内联编辑依赖PropertyAccess组件，需要标准的getter/setter对。
     *
     * @dataProvider provideBooleanFieldsFromController
     * @phpstan-ignore-next-line PreferTestWithAttribute.ComplexDataProvider
     */
    public function testBooleanFieldIsWritableForInlineEditing(string $fieldName): void
    {
        // 1. 创建测试实体
        $entity = $this->createTestEntity();

        // 2. 使用PropertyAccess组件（EasyAdmin内部使用它）
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        // 3. 验证属性可读性
        $this->assertTrue(
            $propertyAccessor->isReadable($entity, $fieldName),
            sprintf(
                'The "%s" property must be readable. ' .
                'Ensure the entity has a getter method (get%s() or is%s()).',
                $fieldName,
                ucfirst($fieldName),
                ucfirst($fieldName)
            )
        );

        // 4. 核心验证：属性可写性（EasyAdmin内联编辑的关键）
        $this->assertTrue(
            $propertyAccessor->isWritable($entity, $fieldName),
            sprintf(
                'The "%s" property must be writable via a setter for EasyAdmin inline editing. ' .
                'Without set%s(bool) method, clicking the switch toggle will fail with: ' .
                '"The \'%s\' property of the entity is not writable." ' .
                'Add: public function set%s(bool $%s): void { $this->%s = $%s; }',
                $fieldName,
                ucfirst($fieldName),
                $fieldName,
                ucfirst($fieldName),
                $fieldName,
                $fieldName,
                $fieldName
            )
        );

        // 5. 验证双向切换（switch toggle需要支持true<->false）
        $propertyAccessor->setValue($entity, $fieldName, true);
        $this->assertTrue(
            $propertyAccessor->getValue($entity, $fieldName),
            sprintf('Setting %s to true should work', $fieldName)
        );

        $propertyAccessor->setValue($entity, $fieldName, false);
        $this->assertFalse(
            $propertyAccessor->getValue($entity, $fieldName),
            sprintf('Setting %s to false should work (bidirectional toggle)', $fieldName)
        );
    }

    /**
     * 从Controller配置中自动提取所有BooleanField
     *
     * 这个DataProvider自动扫描Controller的configureFields()，
     * 提取所有BooleanField的字段名，确保测试覆盖所有可内联编辑的字段。
     *
     * @return \Generator<string, array{string}>
     */
    public static function provideBooleanFieldsFromController(): iterable
    {
        // 获取Controller实例
        $controller = new BaiduOAuth2StateCrudController();

        // 获取字段配置（index页面通常包含所有字段）
        $fields = iterator_to_array($controller->configureFields('index'));

        // 过滤出BooleanField
        foreach ($fields as $field) {
            if ($field instanceof BooleanField) {
                $propertyName = $field->getAsDto()->getProperty();
                yield $propertyName => [$propertyName];
            }
        }
    }

    /**
     * 创建测试实体（为PropertyAccess测试提供实例）
     */
    private function createTestEntity(): BaiduOAuth2State
    {
        $config = new BaiduOAuth2Config();
        $config->setClientId('test-client');
        $config->setClientSecret('test-secret');

        $state = new BaiduOAuth2State();
        $state->setState('test-state');
        $state->setConfig($config);
        $state->setExpireTime((new \DateTimeImmutable())->modify('+10 minutes'));

        return $state;
    }
}

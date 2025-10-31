<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\Tests\Service;

use Knp\Menu\ItemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\BaiduOauth2IntegrateBundle\Service\AdminMenu;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;

/**
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
final class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    protected function onSetUp(): void
    {
        // 无需额外设置
    }

    private function createAdminMenu(): AdminMenu
    {
        return self::getService(AdminMenu::class);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createMockMenuItem(array $config = []): ItemInterface
    {
        $mock = $this->createMock(ItemInterface::class);

        if (isset($config['addChildCallback']) && is_callable($config['addChildCallback'])) {
            $mock->method('addChild')->willReturnCallback($config['addChildCallback']);
        } else {
            $mock->method('addChild')->willReturnSelf();
        }

        if (isset($config['getChildMap']) && is_array($config['getChildMap'])) {
            $mock->method('getChild')->willReturnCallback(function (string $name) use ($config) {
                $childMap = $config['getChildMap'];
                $this->assertIsArray($childMap);

                return $childMap[$name] ?? null;
            });
        } else {
            $mock->method('getChild')->willReturn(null);
        }

        return $mock;
    }

    public function testAdminMenuImplementsInterface(): void
    {
        $adminMenu = $this->createAdminMenu();
        $reflection = new \ReflectionClass($adminMenu);

        $this->assertTrue($reflection->implementsInterface('Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface'));
    }

    public function testAdminMenuConstructor(): void
    {
        $adminMenu = $this->createAdminMenu();
        $this->assertInstanceOf(AdminMenu::class, $adminMenu);
    }

    public function testInvokeCreatesOAuth2MenuWhenNotExists(): void
    {
        $adminMenu = $this->createAdminMenu();

        $childItems = [];
        $oauth2Menu = $this->createMockMenuItem([
            'addChildCallback' => function ($child, array $options = []) use (&$childItems) {
                $expectedChildren = ['Baidu配置', '授权用户', '状态管理'];
                $this->assertContains($child, $expectedChildren);
                $childItems[] = $child;

                return $this->createMockMenuItem();
            },
        ]);

        $addChildCalled = false;
        $rootItem = $this->createMock(ItemInterface::class);
        $rootItem->method('getChild')->with('OAuth2认证')->willReturnOnConsecutiveCalls(null, $oauth2Menu);
        $rootItem->method('addChild')->with('OAuth2认证')->willReturnCallback(function () use (&$addChildCalled, $oauth2Menu) {
            $addChildCalled = true;

            return $oauth2Menu;
        });

        $adminMenu->__invoke($rootItem);

        $this->assertTrue($addChildCalled);
        $this->assertCount(3, $childItems);
        $this->assertContains('Baidu配置', $childItems);
        $this->assertContains('授权用户', $childItems);
        $this->assertContains('状态管理', $childItems);
    }

    public function testInvokeUsesExistingOAuth2Menu(): void
    {
        $adminMenu = $this->createAdminMenu();

        $childItems = [];
        $oauth2Menu = $this->createMockMenuItem([
            'addChildCallback' => function ($child, array $options = []) use (&$childItems) {
                $expectedChildren = ['Baidu配置', '授权用户', '状态管理'];
                $this->assertContains($child, $expectedChildren);
                $childItems[] = $child;

                return $this->createMockMenuItem();
            },
        ]);

        $rootItem = $this->createMockMenuItem([
            'getChildMap' => ['OAuth2认证' => $oauth2Menu],
        ]);

        $adminMenu->__invoke($rootItem);

        $this->assertCount(3, $childItems);
        $this->assertContains('Baidu配置', $childItems);
        $this->assertContains('授权用户', $childItems);
        $this->assertContains('状态管理', $childItems);
    }
}

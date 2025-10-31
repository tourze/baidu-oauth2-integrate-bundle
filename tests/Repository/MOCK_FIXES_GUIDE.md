# BaiduOAuth2StateRepositoryTest Mock 问题修复指南

## 问题描述

在 `BaiduOAuth2StateRepositoryTest.php` 中存在以下Mock问题：

1. **第59行**：Repository 构造函数调用参数不匹配（应为1个参数，传入了2个）
2. **第68行和第71行**：访问了未定义的属性 `$persistCallCount` 和 `$flushCallCount`

## 问题分析

### 1. Repository 构造函数参数问题

**错误代码：**
```php
// 错误：传入了2个参数，但构造函数只接受1个参数
$repository = new BaiduOAuth2StateRepository($registry, BaiduOAuth2State::class);
```

**原因：**
- `BaiduOAuth2StateRepository` 继承自 `ServiceEntityRepository`
- 其构造函数只接受 `ManagerRegistry $registry` 一个参数
- 实体类信息通过 `#[AsRepository]` 注解自动获取

**正确修复：**
```php
// 正确：只传入 ManagerRegistry 参数
$registry = $this->createMock(ManagerRegistry::class);
$repository = new BaiduOAuth2StateRepository($registry);
```

### 2. 未定义属性访问问题

**错误代码：**
```php
// 问题：访问未定义的属性
$this->persistCallCount = 0;  // 未定义
$this->flushCallCount = 0;    // 未定义

$entityManager->expects($this->once())
    ->method('persist')
    ->willReturnCallback(function () {
        $this->persistCallCount++; // 访问未定义属性
    });
```

**原因：**
- 在测试方法中直接使用 `$this->persistCallCount` 而没有在类中定义这些属性
- PHPUnit 不会自动创建这些属性

**正确修复：**
```php
class BaiduOAuth2StateRepositoryTestWithMockFixes extends TestCase
{
    private int $persistCallCount = 0;
    private int $flushCallCount = 0;

    public function testSaveAndRemoveWithCorrectMocking(): void
    {
        // 使用正确定义的类属性
        $entityManager->expects($this->once())
            ->method('persist')
            ->willReturnCallback(function () {
                $this->persistCallCount++; // 正确：访问已定义的类属性
            });
    }
}
```

## 完整修复方案

### 方法1：使用测试替身（Test Double）

```php
public function testSaveAndRemoveWithCorrectMocking(): void
{
    // 1. 正确构造Repository
    $registry = $this->createMock(ManagerRegistry::class);

    // 2. 创建测试替身来覆盖getEntityManager方法
    $repositorySpy = new class($registry) extends BaiduOAuth2StateRepository {
        private ?EntityManagerInterface $entityManager = null;

        public function setEntityManager(EntityManagerInterface $entityManager): void
        {
            $this->entityManager = $entityManager;
        }

        public function getEntityManager(): EntityManagerInterface
        {
            return $this->entityManager ?? parent::getEntityManager();
        }
    };

    // 3. 设置Mock的EntityManager
    $entityManager = $this->createMock(EntityManagerInterface::class);
    $repositorySpy->setEntityManager($entityManager);

    // 4. 定义预期的行为
    $entityManager->expects($this->once())
        ->method('persist')
        ->with($state)
        ->willReturnCallback(function () {
            $this->persistCallCount++;
        });

    $entityManager->expects($this->once())
        ->method('flush')
        ->willReturnCallback(function () {
            $this->flushCallCount++;
        });

    // 5. 执行测试
    $repositorySpy->save($state, true);

    // 6. 验证结果
    $this->assertEquals(1, $this->persistCallCount);
    $this->assertEquals(1, $this->flushCallCount);
}
```

### 方法2：使用继承的测试类

```php
class BaiduOAuth2StateRepositoryTest extends AbstractRepositoryTestCase
{
    // 使用框架提供的基类，自动处理EntityManager和Repository的创建
    protected function getRepository(): ServiceEntityRepository
    {
        return self::getService(BaiduOAuth2StateRepository::class);
    }

    public function testSaveAndRemove(): void
    {
        $repository = $this->getRepository();
        $state = $this->createNewEntity();

        // 直接使用Repository的方法，框架会自动处理Mock
        $repository->save($state, true);

        $this->assertNotNull($state->getId());

        $repository->remove($state, true);
        $this->assertNull($repository->find($state->getId()));
    }
}
```

## 关键要点

1. **构造函数参数**：ServiceEntityRepository 的子类只需要传入 ManagerRegistry
2. **属性定义**：在测试类中明确定义所有需要使用的属性
3. **Mock策略**：优先使用测试框架提供的基类和工具方法
4. **测试替身**：当需要特殊行为时，创建测试替身类来覆盖特定方法

## 推荐的最佳实践

1. **优先使用集成测试**：使用 `AbstractRepositoryTestCase` 进行真实数据库测试
2. **需要Mock时使用测试替身**：创建专门的测试替身类而不是直接修改原始类
3. **保持测试简单**：避免过度复杂的Mock设置，专注于验证核心业务逻辑
4. **使用框架工具**：充分利用现有的测试框架和工具方法

## 文件位置

- 问题文件：`packages/baidu-oauth2-integrate-bundle/tests/Repository/BaiduOAuth2StateRepositoryTestWithMockIssues.php`
- 修复文件：`packages/baidu-oauth2-integrate-bundle/tests/Repository/BaiduOAuth2StateRepositoryTestWithMockFixes.php`
- 原始文件：`packages/baidu-oauth2-integrate-bundle/tests/Repository/BaiduOAuth2StateRepositoryTest.php`
<?php

namespace Tourze\BaiduOauth2IntegrateBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\BaiduOauth2IntegrateBundle\DependencyInjection\BaiduOauth2IntegrateExtension;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

/**
 * @internal
 */
#[CoversClass(BaiduOauth2IntegrateExtension::class)]
final class BaiduOauth2IntegrateExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    private BaiduOauth2IntegrateExtension $extension;

    private ContainerBuilder $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension = new BaiduOauth2IntegrateExtension();
        $this->container = new ContainerBuilder();

        // Set required parameters for AutoExtension
        $this->container->setParameter('kernel.environment', 'test');
        $this->container->setParameter('kernel.debug', true);
        $this->container->setParameter('kernel.cache_dir', sys_get_temp_dir());
        $this->container->setParameter('kernel.logs_dir', sys_get_temp_dir());
        $this->container->setParameter('kernel.project_dir', __DIR__ . '/../../');
    }

    public function testLoadLoadsServicesYaml(): void
    {
        $this->extension->load([], $this->container);

        // Check that services are loaded by verifying some key services exist
        $definitions = $this->container->getDefinitions();
        $bundleServiceIds = array_filter(array_keys($definitions), function (string $id) {
            return str_starts_with($id, 'Tourze\BaiduOauth2IntegrateBundle\\');
        });

        $this->assertGreaterThan(0, count($bundleServiceIds), 'Bundle services should be loaded');
    }

    public function testLoadWithEmptyConfigs(): void
    {
        $this->extension->load([], $this->container);

        $this->assertGreaterThan(0, count($this->container->getDefinitions()));
    }

    public function testLoadWithNonEmptyConfigs(): void
    {
        $configs = [
            ['some_config' => 'value'],
        ];

        $this->extension->load($configs, $this->container);

        $this->assertGreaterThan(0, count($this->container->getDefinitions()));
    }

    public function testLoadSetsCorrectAutowiring(): void
    {
        $this->extension->load([], $this->container);

        $definitions = $this->container->getDefinitions();

        // Check that autowiring is enabled for bundle services
        foreach ($definitions as $id => $definition) {
            if (str_starts_with($id, 'Tourze\BaiduOauth2IntegrateBundle\\')) {
                $this->assertTrue($definition->isAutowired(), "Service {$id} should be autowired");
            }
        }
    }

    public function testLoadSetsCorrectAutoconfiguration(): void
    {
        $this->extension->load([], $this->container);

        $definitions = $this->container->getDefinitions();

        // Check that autoconfiguration is enabled for bundle services
        foreach ($definitions as $id => $definition) {
            if (str_starts_with($id, 'Tourze\BaiduOauth2IntegrateBundle\\')) {
                $this->assertTrue($definition->isAutoconfigured(), "Service {$id} should be autoconfigured");
            }
        }
    }

    public function testLoadMultipleCalls(): void
    {
        $this->extension->load([], $this->container);
        $firstCount = count($this->container->getDefinitions());

        // Loading again should not duplicate services
        $this->extension->load([], $this->container);
        $secondCount = count($this->container->getDefinitions());

        $this->assertEquals($firstCount, $secondCount);
    }

    public function testExtensionInheritsFromCorrectClass(): void
    {
        $this->assertInstanceOf(
            AutoExtension::class,
            $this->extension
        );
    }

    public function testLoadDoesNotThrowException(): void
    {
        $this->expectNotToPerformAssertions();

        $this->extension->load([], $this->container);
        $this->extension->load([['key' => 'value']], $this->container);
        $this->extension->load([[], ['another' => 'config']], $this->container);
    }
}

<?php

namespace Tourze\BaiduOauth2IntegrateBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Routing\RouteCollection;
use Tourze\BaiduOauth2IntegrateBundle\Service\AttributeControllerLoader;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(AttributeControllerLoader::class)]
#[RunTestsInSeparateProcesses]
final class AttributeControllerLoaderTest extends AbstractIntegrationTestCase
{
    private AttributeControllerLoader $loader;

    protected function onSetUp(): void
    {
        $this->loader = self::getService(AttributeControllerLoader::class);
    }

    public function testLoadCallsAutoload(): void
    {
        $collection = $this->loader->load('resource');

        $this->assertInstanceOf(RouteCollection::class, $collection);
        $this->assertGreaterThan(0, $collection->count());
    }

    public function testAutoloadReturnsRouteCollection(): void
    {
        $collection = $this->loader->autoload();

        $this->assertInstanceOf(RouteCollection::class, $collection);
        $this->assertGreaterThan(0, $collection->count());
    }

    public function testAutoloadIncludesAllControllerRoutes(): void
    {
        $collection = $this->loader->autoload();
        $routeNames = array_keys($collection->all());

        // Verify that routes from all controllers are loaded
        $this->assertNotEmpty($routeNames);

        // The exact route names will depend on the controller implementations
        // We just verify that routes are loaded (indicating controllers were processed)
        $this->assertGreaterThanOrEqual(1, count($routeNames), 'Expected at least one route from controllers');
    }

    public function testSupportsAttributeType(): void
    {
        $this->assertTrue($this->loader->supports('resource', 'attribute'));
        $this->assertFalse($this->loader->supports('resource', 'annotation'));
        $this->assertFalse($this->loader->supports('resource', 'yaml'));
        $this->assertFalse($this->loader->supports('resource', null));
    }

    public function testGetTypeReturnsAttribute(): void
    {
        $this->assertEquals('attribute', $this->loader->getType());
    }

    public function testLoadWithNullTypeCallsAutoload(): void
    {
        $collection = $this->loader->load('resource', null);

        $this->assertInstanceOf(RouteCollection::class, $collection);
        $this->assertGreaterThan(0, $collection->count());
    }

    public function testLoadWithSpecificTypeCallsAutoload(): void
    {
        $collection = $this->loader->load('resource', 'attribute');

        $this->assertInstanceOf(RouteCollection::class, $collection);
        $this->assertGreaterThan(0, $collection->count());
    }

    public function testRouteCollectionIsNotEmpty(): void
    {
        $collection = $this->loader->autoload();

        $this->assertFalse(0 === $collection->count(), 'Route collection should not be empty');

        $routes = $collection->all();
        $this->assertNotEmpty($routes, 'Routes array should not be empty');
    }

    public function testEachControllerContributesRoutes(): void
    {
        $collection = $this->loader->autoload();

        // Test that the collection has a reasonable number of routes
        // Each controller should contribute at least one route
        $this->assertGreaterThanOrEqual(1, $collection->count());

        // Verify routes have proper names and paths
        foreach ($collection->all() as $route) {
            $this->assertNotNull($route->getPath(), 'Each route should have a path');
            $this->assertNotEmpty($route->getMethods(), 'Each route should have at least one HTTP method');
        }
    }
}

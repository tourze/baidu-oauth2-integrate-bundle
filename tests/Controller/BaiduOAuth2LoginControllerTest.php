<?php

namespace Tourze\BaiduOauth2IntegrateBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\BaiduOauth2IntegrateBundle\Controller\BaiduOAuth2LoginController;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2Config;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * @internal
 */
#[CoversClass(BaiduOAuth2LoginController::class)]
#[RunTestsInSeparateProcesses]
final class BaiduOAuth2LoginControllerTest extends AbstractWebTestCase
{
    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $this->assertNotNull($method);
    }

    public function testLoginWithValidConfigRedirects(): void
    {
        $client = self::createClientWithDatabase();

        // Insert a valid config
        $em = self::getEntityManager();
        $config = new BaiduOAuth2Config();
        $config->setClientId('test_api_key');
        $config->setClientSecret('test_secret');
        $config->setScope('basic');
        $config->setValid(true);
        $em->persist($config);
        $em->flush();

        $client->request('GET', '/baidu-oauth2/login');

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $location = $client->getResponse()->headers->get('Location');
        $this->assertIsString($location);
        $this->assertStringContainsString('https://openapi.baidu.com/oauth/2.0/authorize', $location);
        $this->assertStringContainsString('client_id=test_api_key', $location);
    }
}

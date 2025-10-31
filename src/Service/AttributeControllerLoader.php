<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\Service;

use Symfony\Bundle\FrameworkBundle\Routing\AttributeRouteControllerLoader;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Routing\RouteCollection;
use Tourze\BaiduOauth2IntegrateBundle\Controller\Admin\BaiduOAuth2ConfigCrudController;
use Tourze\BaiduOauth2IntegrateBundle\Controller\Admin\BaiduOAuth2UserCrudController;
use Tourze\BaiduOauth2IntegrateBundle\Controller\BaiduOAuth2CallbackController;
use Tourze\BaiduOauth2IntegrateBundle\Controller\BaiduOAuth2LoginController;
use Tourze\RoutingAutoLoaderBundle\Service\RoutingAutoLoaderInterface;

#[AutoconfigureTag(name: 'routing.loader')]
class AttributeControllerLoader extends Loader implements RoutingAutoLoaderInterface
{
    private AttributeRouteControllerLoader $controllerLoader;

    public function __construct()
    {
        parent::__construct();
        $this->controllerLoader = new AttributeRouteControllerLoader();
    }

    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        return $this->autoload();
    }

    public function autoload(): RouteCollection
    {
        $collection = new RouteCollection();
        $collection->addCollection($this->controllerLoader->load(BaiduOAuth2LoginController::class));
        $collection->addCollection($this->controllerLoader->load(BaiduOAuth2CallbackController::class));
        $collection->addCollection($this->controllerLoader->load(BaiduOAuth2ConfigCrudController::class));
        $collection->addCollection($this->controllerLoader->load(BaiduOAuth2UserCrudController::class));

        return $collection;
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return 'attribute' === $type;
    }

    public function getType(): string
    {
        return 'attribute';
    }
}

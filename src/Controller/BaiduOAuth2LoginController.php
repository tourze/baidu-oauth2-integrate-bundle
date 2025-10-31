<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\Controller;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\BaiduOauth2IntegrateBundle\Service\BaiduOAuth2Service;

#[WithMonologChannel(channel: 'baidu_oauth2_integrate')]
final class BaiduOAuth2LoginController extends AbstractController
{
    public function __construct(
        private readonly BaiduOAuth2Service $oauth2Service,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route(path: '/baidu-oauth2/login', name: 'baidu_oauth2_login', methods: ['GET'])]
    public function __invoke(Request $request): RedirectResponse|Response
    {
        try {
            $sessionId = $request->getSession()->getId();
            $authUrl = $this->oauth2Service->generateAuthorizationUrl($sessionId);

            return new RedirectResponse($authUrl);
        } catch (\Throwable $e) {
            $this->logger->error('Baidu OAuth2 login init failed', [
                'error' => $e->getMessage(),
                'ip' => $request->getClientIp(),
            ]);

            return new Response('Login failed: Configuration error', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

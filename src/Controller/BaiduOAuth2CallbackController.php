<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\Controller;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tourze\BaiduOauth2IntegrateBundle\Event\BaiduOAuth2LoginFailedEvent;
use Tourze\BaiduOauth2IntegrateBundle\Event\BaiduOAuth2LoginSuccessEvent;
use Tourze\BaiduOauth2IntegrateBundle\Exception\BaiduOAuth2Exception;
use Tourze\BaiduOauth2IntegrateBundle\Service\BaiduOAuth2Service;

#[WithMonologChannel(channel: 'baidu_oauth2_integrate')]
final class BaiduOAuth2CallbackController extends AbstractController
{
    public function __construct(
        private readonly BaiduOAuth2Service $oauth2Service,
        private readonly LoggerInterface $logger,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    #[Route(path: '/baidu-oauth2/callback', name: 'baidu_oauth2_callback', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $code = $request->query->get('code');
        $state = $request->query->get('state');
        $error = $request->query->get('error');

        if (null !== $error) {
            $msg = $request->query->get('error_description', 'Unknown error');
            $this->logger->warning('Baidu OAuth2 error response', [
                'error' => $error,
                'error_description' => $msg,
                'ip' => $request->getClientIp(),
            ]);

            $this->eventDispatcher->dispatch(new BaiduOAuth2LoginFailedEvent(
                'provider_error',
                ['error' => (string) $error, 'error_description' => (string) $msg]
            ));

            return new Response(sprintf('OAuth2 Error: %s', $msg), Response::HTTP_BAD_REQUEST);
        }

        if (null === $code || null === $state) {
            $this->logger->warning('Invalid Baidu OAuth2 callback parameters', [
                'has_code' => null !== $code && '' !== $code,
                'has_state' => null !== $state && '' !== $state,
                'ip' => $request->getClientIp(),
            ]);

            $this->eventDispatcher->dispatch(new BaiduOAuth2LoginFailedEvent(
                'invalid_params',
                ['has_code' => null !== $code, 'has_state' => null !== $state]
            ));

            return new Response('Invalid callback parameters', Response::HTTP_BAD_REQUEST);
        }

        try {
            $user = $this->oauth2Service->handleCallback((string) $code, (string) $state);

            $this->logger->info('Baidu OAuth2 login successful', [
                'baidu_uid' => $user->getBaiduUid(),
                'ip' => $request->getClientIp(),
            ]);

            $this->eventDispatcher->dispatch(new BaiduOAuth2LoginSuccessEvent($user));

            return new Response(sprintf('Successfully logged in as %s', $user->getUsername() ?? $user->getBaiduUid()));
        } catch (BaiduOAuth2Exception $e) {
            $this->logger->warning('Baidu OAuth2 validation failed', [
                'error' => $e->getMessage(),
                'ip' => $request->getClientIp(),
                'state' => $state,
            ]);

            $this->eventDispatcher->dispatch(new BaiduOAuth2LoginFailedEvent(
                'validation_error',
                ['message' => $e->getMessage()]
            ));

            return new Response('Login failed: ' . $e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            $this->logger->error('Baidu OAuth2 login failed', [
                'error' => $e->getMessage(),
                'ip' => $request->getClientIp(),
                'state' => $state,
            ]);

            $this->eventDispatcher->dispatch(new BaiduOAuth2LoginFailedEvent(
                'exception',
                ['message' => $e->getMessage()]
            ));

            return new Response('Login failed: Authentication error', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

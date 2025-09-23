<?php

declare(strict_types=1);

namespace App\Tests\Unit\General\Infrastructure\Service;

use App\General\Domain\ValueObject\UserId;
use App\General\Infrastructure\Service\LexikJwtAuthenticatorService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class LexikJwtAuthenticatorServiceTest extends TestCase
{
    public function testOnKernelControllerUsesConfiguredUserIdClaim(): void
    {
        $tokenExtractor = $this->createMock(TokenExtractorInterface::class);
        $tokenManager = $this->createMock(JWTTokenManagerInterface::class);

        $expectedUserId = '123e4567-e89b-12d3-a456-426614174000';

        $tokenExtractor->expects(self::once())
            ->method('extract')
            ->willReturn('jwt-token');

        $tokenManager->expects(self::once())
            ->method('parse')
            ->with('jwt-token')
            ->willReturn(['custom_claim' => $expectedUserId]);

        $tokenManager->expects(self::once())
            ->method('getUserIdClaim')
            ->willReturn('custom_claim');

        $service = new LexikJwtAuthenticatorService($tokenManager, $tokenExtractor, '^/api/.*$');

        $request = Request::create('/api/example');
        $kernel = $this->createMock(HttpKernelInterface::class);
        $controller = static fn (): void => null;

        $event = new ControllerEvent($kernel, $controller, $request, HttpKernelInterface::MAIN_REQUEST);

        $service->onKernelController($event);

        $userId = $service->getUserId();

        self::assertInstanceOf(UserId::class, $userId);
        self::assertSame($expectedUserId, (string)$userId);
    }
}

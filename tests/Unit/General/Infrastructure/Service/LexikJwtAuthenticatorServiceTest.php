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
    public function testGetUserIdReturnsNullWhenIdentifierMissing(): void
    {
        $service = $this->createService();

        self::assertNull($service->getUserId());
        self::assertNull($service->getSymfonyUser());
    }

    public function testGetUserIdReturnsValueObjectWhenIdentifierPresent(): void
    {
        $service = $this->createService();

        $this->setServiceProperty($service, 'userId', '123e4567-e89b-12d3-a456-426614174000');
        $this->setServiceProperty($service, 'fullName', 'Test User');
        $this->setServiceProperty($service, 'avatar', 'avatar.png');
        $this->setServiceProperty($service, 'roles', ['ROLE_USER']);

        $userId = $service->getUserId();
        self::assertInstanceOf(UserId::class, $userId);
        self::assertSame('123e4567-e89b-12d3-a456-426614174000', (string) $userId);

        $symfonyUser = $service->getSymfonyUser();
        self::assertInstanceOf(SymfonyUser::class, $symfonyUser);
        self::assertSame('123e4567-e89b-12d3-a456-426614174000', $symfonyUser->getUserIdentifier());
        self::assertSame('Test User', $symfonyUser->getFullName());
        self::assertSame('avatar.png', $symfonyUser->getAvatar());
        self::assertSame(['ROLE_USER'], $symfonyUser->getRoles());
    }

    private function createService(): LexikJwtAuthenticatorService
    {
        $tokenManager = $this->createMock(JWTTokenManagerInterface::class);
        $tokenExtractor = $this->createMock(TokenExtractorInterface::class);

        return new LexikJwtAuthenticatorService($tokenManager, $tokenExtractor, '/api');
    }

    private function setServiceProperty(object $service, string $property, mixed $value): void
    {
        $reflection = new \ReflectionProperty($service, $property);
        $reflection->setAccessible(true);
        $reflection->setValue($service, $value);
    }
}

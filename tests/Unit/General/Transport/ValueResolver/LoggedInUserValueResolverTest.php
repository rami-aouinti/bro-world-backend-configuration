<?php

declare(strict_types=1);

namespace App\Tests\Unit\General\Transport\ValueResolver;

use App\General\Infrastructure\Service\LexikJwtAuthenticatorService;
use App\General\Infrastructure\ValueObject\SymfonyUser;
use App\General\Transport\ValueResolver\LoggedInUserValueResolver;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\MissingTokenException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * @package App\Tests\Unit\General\Transport\ValueResolver
 */
class LoggedInUserValueResolverTest extends TestCase
{
    public function testSupportsThrowsMissingTokenExceptionWhenTokenMissing(): void
    {
        $service = $this->createMock(LexikJwtAuthenticatorService::class);
        $service->method('getUserId')->willReturn(null);

        $resolver = new LoggedInUserValueResolver($service);
        $request = new Request();
        $argument = new ArgumentMetadata('symfonyUser', SymfonyUser::class, false, false, null, false);

        $this->expectException(MissingTokenException::class);
        $resolver->supports($request, $argument);
    }

    public function testResolveYieldsNullWhenUserMissing(): void
    {
        $service = $this->createMock(LexikJwtAuthenticatorService::class);
        $service->method('getSymfonyUser')->willReturn(null);

        $resolver = new LoggedInUserValueResolver($service);
        $request = new Request();
        $argument = new ArgumentMetadata('symfonyUser', SymfonyUser::class, false, false, null, true);

        $result = iterator_to_array($resolver->resolve($request, $argument));

        self::assertSame([null], $result);
    }
}

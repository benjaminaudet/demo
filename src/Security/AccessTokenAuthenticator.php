<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Security;

use App\Repository\AccessTokenRepository;
use App\Utils\HttpResponseMessage;
use App\Utils\JsonResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * @see https://symfony.com/doc/current/security/custom_authenticator.html
 */
class AccessTokenAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{

    public function __construct(
        private AccessTokenRepository $repository,
    ) {}
    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning `false` will cause this authenticator
     * to be skipped.
     */
    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization');
    }

    public function start(Request $request, ?AuthenticationException $authException = null): JsonResponse
    {
        return JsonResponseFactory::unauthorized(HttpResponseMessage::AUTH_HEADER_REQUIRED);
    }

    public function authenticate(Request $request): Passport
    {
        if ('Bearer ' !== substr($request->headers->get('Authorization'), 0, 7)) {
            throw new CustomUserMessageAuthenticationException('No API token provided');
        }
        $accessToken = trim(str_replace("Bearer ", "", $request->headers->get('Authorization')));
        if (null === $accessToken) {
            // The token header was empty, authentication fails with HTTP Status
            // Code 401 "Unauthorized"
            throw new CustomUserMessageAuthenticationException('No API token provided');
        }

        $accessToken = $this->repository->findOneBy(['token' => $accessToken]);
        // if (null === $accessToken || !$accessToken->isValid()) {
        //     throw new BadCredentialsException('Invalid credentials.');
        // }

        return new SelfValidatingPassport(new UserBadge($accessToken->getUser()->getUserIdentifier()));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?JsonResponse
    {
        // on success, let the request continue
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?JsonResponse
    {
        $data = [
            // you may want to customize or obfuscate the message first
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData()),

            // or to translate this message
            // $this->translator->trans($exception->getMessageKey(), $exception->getMessageData())
        ];

        return JsonResponseFactory::unauthorized(HttpResponseMessage::UNAUTHORIZED);
    }

    // public function start(Request $request, ?AuthenticationException $authException = null): Response
    // {
    //     /*
    //      * If you would like this class to control what happens when an anonymous user accesses a
    //      * protected page (e.g. redirect to /login), uncomment this method and make this class
    //      * implement Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface.
    //      *
    //      * For more details, see https://symfony.com/doc/current/security/experimental_authenticators.html#configuring-the-authentication-entry-point
    //      */
    // }
}

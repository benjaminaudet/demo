<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\User;
use App\Entity\AccessToken;
use App\Utils\JsonResponseFactory;
use App\Utils\User\UserHttpResponseMessage;
use App\Repository\UserRepository;
use App\Repository\AccessTokenRepository;
use App\Security\CurrentToken;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Controller used to manage the application security.
 * See https://symfony.com/doc/current/security/form_login_setup.html.
 *
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class SecurityController extends AbstractController
{
    use TargetPathTrait;

    /*
     * The $user argument type (?User) must be nullable because the login page
     * must be accessible to anonymous visitors too.
     */
    #[Route('/login', name: 'security_login')]
    public function login(
        #[CurrentUser] ?User $user,
        Request $request,
        AuthenticationUtils $helper,
    ): Response {
        // if user is already logged in, don't display the login page again
        if ($user) {
            return $this->redirectToRoute('blog_index');
        }

        // this statement solves an edge-case: if you change the locale in the login
        // page, after a successful login you are redirected to a page in the previous
        // locale. This code regenerates the referrer URL whenever the login page is
        // browsed, to ensure that its locale is always the current one.
        $this->saveTargetPath($request->getSession(), 'main', $this->generateUrl('admin_index'));

        return $this->render('security/login.html.twig', [
            // last username entered by the user (if any)
            'last_username' => $helper->getLastUsername(),
            // last authentication error (if any)
            'error' => $helper->getLastAuthenticationError(),
        ]);
    }

    #[Route('/api/oauth/token', name: 'security_api_oauth_create_token', methods: ['POST'])]
    public function createToken(
        Request $request,
        UserRepository $userRepository,
        AccessTokenRepository $accessTokenRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $data = $request->getContent();
        $jsonData = json_decode($data, true);

        if (!isset($jsonData['email']) || empty($jsonData['email'])) {
            return JsonResponseFactory::badRequest(UserHttpResponseMessage::EMAIL_REQUIRED);
        }
        if (!isset($jsonData['password']) || empty($jsonData['password'])) {
            return JsonResponseFactory::badRequest(UserHttpResponseMessage::PASSWORD_REQUIRED);
        }

        $user = $userRepository->findOneByEmail($jsonData['email']);
        if (!$user || !$passwordHasher->isPasswordValid($user, $jsonData['password'])) {
            return JsonResponseFactory::unauthorized(UserHttpResponseMessage::INVALID_CREDENTIALS);
        }

        $existingToken = $accessTokenRepository->findOneBy(['user' => $user]);
        if ($existingToken && $existingToken->isValid()) {
            return new JsonResponse(['token' => $existingToken->getToken()]);
        }

        $accessToken = new AccessToken($user);

        $entityManager->persist($accessToken);
        $entityManager->flush();

        return new JsonResponse(['token' => $accessToken->getToken()]);
    }

    #[Route('/api/oauth/revoke_token', name: 'security_api_oauth_revoke_token', methods: ['POST'])]
    public function revokeToken(
        Request $request,
        AccessTokenRepository $accessTokenRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        #[CurrentToken] ?string $currentToken,
    ): JsonResponse {
        $data = $request->getContent();
        $jsonData = json_decode($data, true);
        $token = $jsonData['token'];

        if (!isset($token) || empty($token)) {
            return JsonResponseFactory::badRequest();
        }

        $foundAccessToken = $accessTokenRepository->findOneBy(['token' => $token]);

        if (!$foundAccessToken) {
            return JsonResponseFactory::notFound();
        }
        if ($currentToken != $foundAccessToken->getToken()) {
            return JsonResponseFactory::forbidden();
        }

        $entityManager->remove($foundAccessToken);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Token revoked']);
    }
}

<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\AccessTokenRepository;
use App\Repository\UserRepository;
use App\Utils\JsonResponseFactory;
use App\Utils\User\UserUtils;
use App\Utils\User\UserHttpResponseMessage;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Controller used to manage blog contents in the backend.
 *
 * Please note that the application backend is developed manually for learning
 * purposes. However, in your real Symfony application you should use any of the
 * existing bundles that let you generate ready-to-use backends without effort.
 * See https://symfony.com/bundles
 *
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
#[Route('/api/user')]
final class UserController extends AbstractController
{
    /**
     * Lists all User entities.
     */
    #[Route('/', name: 'api_index', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(
        UserRepository $users,
    ): Response {
        $allUsers = $users->findAll(['createdAt' => 'DESC']);

        return $this->json(iterator_to_array((function () use ($allUsers) {
            foreach ($allUsers as $user) {
                yield [
                    'id' => $user->getId(),
                    'username' => $user->getUsername(),
                    'fullName' => $user->getFullName(),
                    'email' => $user->getEmail(),
                    'createdAt' => $user->getCreatedAt(),
                    'modifiedAt' => $user->getModifiedAt(),
                ];
            }
        })()), Response::HTTP_OK);
    }

    /**
     * Creates a new User entity.
     */
    #[Route('/new', name: 'api_user_new', methods: ['POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        SerializerInterface $serializer,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {

        $data = $request->getContent();
        $jsonData = json_decode($data, true);

        if (!isset($jsonData['email']) || empty($jsonData['email'])) {
            return JsonResponseFactory::badRequest(UserHttpResponseMessage::EMAIL_REQUIRED);
        }
        if (!isset($jsonData['username']) || empty($jsonData['username'])) {
            return JsonResponseFactory::badRequest(UserHttpResponseMessage::USERNAME_REQUIRED);
        }
        if (!isset($jsonData['fullName']) || empty($jsonData['fullName'])) {
            return JsonResponseFactory::badRequest(UserHttpResponseMessage::FULLNAME_REQUIRED);
        }
        if (!isset($jsonData['password']) || empty($jsonData['password'])) {
            return JsonResponseFactory::badRequest(UserHttpResponseMessage::PASSWORD_REQUIRED);
        }

        if ($userRepository->findOneBy(['email' => $jsonData['email']])) {
            return JsonResponseFactory::conflict(UserHttpResponseMessage::EMAIL_ALREADY_EXISTS);
        }
        if ($userRepository->findOneBy(['username' => $jsonData['username']])) {
            return JsonResponseFactory::conflict(UserHttpResponseMessage::USERNAME_ALREADY_EXISTS);
        }
        if ($userRepository->findOneBy(['fullName' => $jsonData['fullName']])) {
            return JsonResponseFactory::conflict(UserHttpResponseMessage::FULLNAME_ALREADY_EXISTS);
        }

        $user = User::createFromPayload($jsonData);


        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $jsonData['password']
        );

        $user->setPassword($hashedPassword);

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse(
            $serializer->serialize(
                [
                    "username" => $user->getUsername(),
                    "fullName" => $user->getFullName(),
                    "email" => $user->getEmail()
                ],
                'json'
            ),
            Response::HTTP_CREATED,
            [],
            true
        );
    }

    /**
     * Finds and displays a User entity found by id.
     */
    #[Route('/{id}', name: 'api_user_show', requirements: ['id' => Requirement::POSITIVE_INT], methods: ['GET'])]
    public function show(
        int $id,
        UserRepository $userRepository,
        SerializerInterface $serializer
    ): JsonResponse {
        $user = UserUtils::getUser($id, $userRepository);

        if (!$user) {
            return JsonResponseFactory::notFound(UserHttpResponseMessage::USER_NOT_FOUND);
        }

        return new JsonResponse(UserUtils::serialize($user, $serializer), Response::HTTP_OK, [], true);
    }

    /**
     * Edits an existing User entity.
     */
    #[Route('/{id}/edit', name: 'api_user_edit', requirements: ['id' => Requirement::POSITIVE_INT], methods: ['PATCH'])]
    public function edit(
        int $id,
        Request $request,
        UserRepository $userRepository,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = UserUtils::getUser($id, $userRepository);

        if (!$user) {
            return JsonResponseFactory::notFound(UserHttpResponseMessage::USER_NOT_FOUND);
        }

        $serializer->deserialize($request->getContent(), User::class, 'json', ['object_to_populate' => $user]);

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse(UserUtils::serialize($user, $serializer), Response::HTTP_OK, [], true);
    }

    /**
     * Deletes a User entity.
     */
    #[Route('/{id}', name: 'api_user_delete', requirements: ['id' => Requirement::POSITIVE_INT], methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        #[CurrentUser] User $currentUser,
        int $id,
        UserRepository $userRepository,
        AccessTokenRepository $accessTokenRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = UserUtils::getUser($id, $userRepository);

        if (!$user) {
            return JsonResponseFactory::notFound(UserHttpResponseMessage::USER_NOT_FOUND);
        }

        $accessToken = $accessTokenRepository->findOneBy(['user' => $user]);
        if ($accessToken) {
            $entityManager->remove($accessToken);
        }

        $entityManager->remove($user);
        $entityManager->flush();

        return JsonResponseFactory::deleted(UserHttpResponseMessage::USER_DELETED);
    }
}

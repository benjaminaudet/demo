<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Admin;

use App\Entity\User;
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
#[Route('/admin/user')]
#[IsGranted(User::ROLE_ADMIN)]
final class UserController extends AbstractController
{
    /**
     * Lists all User entities.
     *
     * This controller responds to two different routes with the same URL:
     *   * 'admin_post_index' is the route with a name that follows the same
     *     structure as the rest of the controllers of this class.
     *   * 'admin_index' is a nice shortcut to the backend homepage. This allows
     *     to create simpler links in the templates. Moreover, in the future we
     *     could move this annotation to any other controller while maintaining
     *     the route name and therefore, without breaking any existing link.
     */
    #[Route('/', name: 'admin_index', methods: ['GET'])]
    #[Route('/', name: 'admin_user_index', methods: ['GET'])]
    public function index(
        UserRepository $users
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
     *
     * NOTE: the Method annotation is optional, but it's a recommended practice
     * to constraint the HTTP methods each controller responds to (by default
     * it responds to all methods).
     */
    #[Route('/new', name: 'admin_user_new', methods: ['POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $user = new User();
        $data = $request->getContent();
        $jsonData = json_decode($request->getContent(), true);

        if (!isset($jsonData['email']) || empty($jsonData['email'])) {
            return $this->json(['error' => UserHttpResponseMessage::EMAIL_REQUIRED], Response::HTTP_BAD_REQUEST);
        }
        if (!isset($jsonData['username']) || empty($jsonData['username'])) {
            return $this->json(['error' => UserHttpResponseMessage::USERNAME_REQUIRED], Response::HTTP_BAD_REQUEST);
        }
        if (!isset($jsonData['fullName']) || empty($jsonData['fullName'])) {
            return $this->json(['error' => UserHttpResponseMessage::FULLNAME_REQUIRED], Response::HTTP_BAD_REQUEST);
        }
        if (!isset($jsonData['password']) || empty($jsonData['password'])) {
            return $this->json(['error' => UserHttpResponseMessage::PASSWORD_REQUIRED], Response::HTTP_BAD_REQUEST);
        }

        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $data['password']
        );

        UserUtils::deserialize($data, $user, $serializer);

        $user->setPassword($hashedPassword);

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse($serializer->serialize($user, 'json'), Response::HTTP_CREATED, [], true);
    }

    /**
     * Finds and displays a User entity found by id.
     */
    #[Route('/{id}', name: 'admin_user_show', requirements: ['id' => Requirement::POSITIVE_INT], methods: ['GET'])]
    public function show(int $id, UserRepository $userRepository, SerializerInterface $serializer): JsonResponse
    {
        $user = UserUtils::getUser($id, $userRepository);

        if (!$user) {
            return JsonResponseFactory::notFound(UserHttpResponseMessage::USER_NOT_FOUND);
        }

        return new JsonResponse(UserUtils::serialize($user, $serializer), Response::HTTP_OK, [], true);
    }

    /**
     * Displays a form to edit an existing User entity.
     */
    #[Route('/{id}/edit', name: 'admin_user_edit', requirements: ['id' => Requirement::POSITIVE_INT], methods: ['PATCH'])]
    public function edit(int $id, Request $request, UserRepository $userRepository, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
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
    #[Route('/{id}', name: 'admin_user_delete', requirements: ['id' => Requirement::POSITIVE_INT], methods: ['DELETE'])]
    public function delete(int $id, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $user = UserUtils::getUser($id, $userRepository);

        if (!$user) {
            return JsonResponseFactory::notFound(UserHttpResponseMessage::USER_NOT_FOUND);
        }

        $entityManager->remove($user);
        $entityManager->flush();

        return JsonResponseFactory::deleted(UserHttpResponseMessage::USER_DELETED);
    }
}

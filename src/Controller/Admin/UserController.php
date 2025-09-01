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

use App\Entity\Post;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        SerializerInterface $serializer
    ): JsonResponse {
        $user = new User();
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || empty($data['email'])) {
            return $this->json(['error' => 'Email is required'], Response::HTTP_BAD_REQUEST);
        }
        if (!isset($data['username']) || empty($data['username'])) {
            return $this->json(['error' => 'Username is required'], Response::HTTP_BAD_REQUEST);
        }
        if (!isset($data['fullName']) || empty($data['fullName'])) {
            return $this->json(['error' => 'Full name is required'], Response::HTTP_BAD_REQUEST);
        }
        if (!isset($data['password']) || empty($data['password'])) {
            return $this->json(['error' => 'Password is required'], Response::HTTP_BAD_REQUEST);
        }

        $serializer->deserialize($request->getContent(), User::class, 'json', ['object_to_populate' => $user]);

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse($serializer->serialize($user, 'json'), Response::HTTP_CREATED, [], true);
    }

    /**
     * Finds and displays a User entity.
     */
    #[Route('/{id:user}', name: 'admin_user_show', requirements: ['id' => Requirement::POSITIVE_INT], methods: ['GET'])]
    public function show(User $user, SerializerInterface $serializer): JsonResponse
    {
        return new JsonResponse($serializer->serialize($user, 'json'), Response::HTTP_OK, [], true);
    }

    /**
     * Displays a form to edit an existing User entity.
     */
    #[Route('/{id:user}/edit', name: 'admin_user_edit', requirements: ['id' => Requirement::POSITIVE_INT], methods: ['PATCH'])]
    public function edit(Request $request, User $user, UserRepository $users, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        $foundUserToEdit = $users->findOneBy(['id' => $user->getId()]);

        $serializer->deserialize($request->getContent(), User::class, 'json', ['object_to_populate' => $foundUserToEdit]);

        $entityManager->persist($foundUserToEdit);
        $entityManager->flush();

        return new JsonResponse($serializer->serialize($foundUserToEdit, 'json'), Response::HTTP_OK, [], true);
    }

    /**
     * Deletes a Post entity.
     */
    #[Route('/{id:post}/delete', name: 'admin_post_delete', requirements: ['id' => Requirement::POSITIVE_INT], methods: ['POST'])]
    #[IsGranted('delete', subject: 'post')]
    public function delete(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        /** @var string|null $token */
        $token = $request->getPayload()->get('token');

        if (!$this->isCsrfTokenValid('delete', $token)) {
            return $this->redirectToRoute('admin_post_index', [], Response::HTTP_SEE_OTHER);
        }

        // Delete the tags associated with this blog post. This is done automatically
        // by Doctrine, except for SQLite (the database used in this application)
        // because foreign key support is not enabled by default in SQLite
        $post->getTags()->clear();

        $entityManager->remove($post);
        $entityManager->flush();

        $this->addFlash('success', 'post.deleted_successfully');

        return $this->redirectToRoute('admin_post_index', [], Response::HTTP_SEE_OTHER);
    }
}

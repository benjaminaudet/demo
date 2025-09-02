<?php

namespace App\Utils\User;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Serializer\SerializerInterface;


class UserUtils
{
    public static function serialize(User $user, SerializerInterface $serializer): string
    {
        return $serializer->serialize($user, 'json');
    }

    public static function deserialize(mixed $data, User $user, SerializerInterface $serializer): array
    {
        return $serializer->deserialize($data, User::class, 'json', ['object_to_populate' => $user]);
    }

    public static function getUser(int $id, UserRepository $userRepository): ?User
    {
        return $userRepository->find($id);
    }
}

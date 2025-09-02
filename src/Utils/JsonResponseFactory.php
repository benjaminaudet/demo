<?php

namespace App\Utils;

use Symfony\Component\HttpFoundation\JsonResponse;
use App\Utils\HttpResponseMessage;

class JsonResponseFactory
{
    public static function success($data = [], int $status = JsonResponse::HTTP_OK, string $message = HttpResponseMessages::SUCCESS): JsonResponse
    {
        return new JsonResponse(['success' => true, 'code' => $status, 'data' => $data, 'message' => $message], $status);
    }

    public static function error(string $message, int $status = JsonResponse::HTTP_BAD_REQUEST): JsonResponse
    {
        return new JsonResponse(['success' => false, 'code' => $status, 'error' => $message], $status);
    }

    public static function notFound(string $message = HttpResponseMessage::NOT_FOUND): JsonResponse
    {
        return self::error($message, JsonResponse::HTTP_NOT_FOUND);
    }

    public static function forbidden(string $message = HttpResponseMessage::FORBIDDEN): JsonResponse
    {
        return self::error($message, JsonResponse::HTTP_FORBIDDEN);
    }

    public static function unauthorized(string $message = HttpResponseMessage::UNAUTHORIZED): JsonResponse
    {
        return self::error($message, JsonResponse::HTTP_UNAUTHORIZED);
    }

    public static function deleted(string $message = HttpResponseMessage::DELETED): JsonResponse
    {
        return self::success(['message' => $message], JsonResponse::HTTP_NO_CONTENT);
    }
}

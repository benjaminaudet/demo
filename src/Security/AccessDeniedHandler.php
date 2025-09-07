<?php

namespace App\Security;

use App\Utils\JsonResponseFactory;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?JsonResponse
    {
        return JsonResponseFactory::forbidden(
            'You do not have permission to access this resource.'
        );
    }
}

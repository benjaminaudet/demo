<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class CurrentTokenValueResolver implements ValueResolverInterface
{
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        // Only act if attribute #[CurrentToken] is present
        $attributes = $argument->getAttributes(CurrentToken::class, ArgumentMetadata::IS_INSTANCEOF);
        if (!$attributes) {
            return [];
        }

        // Extract Authorization header
        $authHeader = $request->headers->get('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return [null]; // Or throw exception if required
        }

        $token = substr($authHeader, 7); // Remove "Bearer "

        return [$token];
    }
}

<?php

namespace App\Utils;

class HttpResponseMessage
{
    public const NOT_FOUND = 'Resource not found';
    public const DELETED = 'Resource deleted successfully';
    public const CREATED = 'Resource created successfully';
    public const UPDATED = 'Resource updated successfully';

    public const SUCCESS = 'Success';
    public const UNAUTHORIZED = 'Unauthorized';
    public const FORBIDDEN = 'Access denied';
}

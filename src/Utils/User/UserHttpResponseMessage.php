<?php

namespace App\Utils\User;

class UserHttpResponseMessage
{
    public const USER_NOT_FOUND = 'User not found';
    public const USER_DELETED = 'User deleted successfully';
    public const USER_CREATED = 'User created successfully';
    public const USER_UPDATED = 'User updated successfully';

    public const EMAIL_REQUIRED = 'Email is required';
    public const USERNAME_REQUIRED = 'Username is required';
    public const FULLNAME_REQUIRED = 'Full name is required';
    public const PASSWORD_REQUIRED = 'Password is required';
    public const EMAIL_ALREADY_EXISTS = 'Email already exists';
    public const USERNAME_ALREADY_EXISTS = 'Username already exists';
    public const FULLNAME_ALREADY_EXISTS = 'Full name already exists';
}

<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;

    protected function authorizePermission(string $permission): void
    {
        if (! auth()->user()?->can($permission)) {
            throw new AuthorizationException('You do not have permission to perform this action.');
        }
    }
}

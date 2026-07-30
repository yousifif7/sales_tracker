<?php

namespace App\Support;

use RuntimeException;

class CurrentUserResolver
{
    public function id(): int
    {
        $userId = auth()->id();

        if (! $userId) {
            throw new RuntimeException('You must be signed in to perform this action.');
        }

        return (int) $userId;
    }
}

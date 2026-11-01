<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Attempt;
use App\Models\User;

class AttemptPolicy
{
    /** Students only ever see and change their own attempts. */
    public function view(User $user, Attempt $attempt): bool
    {
        return $attempt->user_id === $user->id;
    }

    public function update(User $user, Attempt $attempt): bool
    {
        return $attempt->user_id === $user->id;
    }
}

<?php

namespace App\Policies;

use App\Models\Refill;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RefillPolicy
{
    // Only the owner can open a refill. Anyone else gets 404, not 403,
    // so they can't tell that the refill exists.
    public function view(User $user, Refill $refill): Response
    {
        return $refill->user()->is($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}

<?php

namespace App\Policies;

use App\Models\ApiKey;
use App\Models\User;

class ApiKeyPolicy
{
    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ApiKey $apiKey): bool
    {
        return $user->id === $apiKey->user_id;
    }
}

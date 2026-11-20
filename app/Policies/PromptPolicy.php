<?php

namespace App\Policies;

use App\Models\Prompt;
use App\Models\User;

class PromptPolicy
{
    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Prompt $prompt): bool
    {
        return $user->id === $prompt->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Prompt $prompt): bool
    {
        return $user->id === $prompt->user_id;
    }
}

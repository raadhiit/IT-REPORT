<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;

class ActivityPolicy
{
    /**
     * Determine whether the user can update the model.
     *
     * Owners can edit their own logged activities; admins can edit anyone's.
     */
    public function update(User $user, Activity $activity): bool
    {
        return $user->id === $activity->user_id || $user->isAdmin();
    }
}

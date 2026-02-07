<?php

namespace App\Policies;

use App\Models\Presentation;
use App\Models\User;

class PresentationPolicy
{
    /**
     * Determine if the user can view any presentations.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can view the presentation.
     */
    public function view(User $user, Presentation $presentation): bool
    {
        // Allow if user owns it or if user_id is null (legacy data)
        return $presentation->user_id === null || $user->id === $presentation->user_id;
    }

    /**
     * Determine if the user can create presentations.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can update the presentation.
     */
    public function update(User $user, Presentation $presentation): bool
    {
        // Allow if user owns it or if user_id is null (legacy data)
        return $presentation->user_id === null || $user->id === $presentation->user_id;
    }

    /**
     * Determine if the user can delete the presentation.
     */
    public function delete(User $user, Presentation $presentation): bool
    {
        // Allow if user owns it or if user_id is null (legacy data)
        return $presentation->user_id === null || $user->id === $presentation->user_id;
    }
}

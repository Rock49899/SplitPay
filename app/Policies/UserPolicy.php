<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if $auth can assign a role to $target for $annexeId.
     */
    public function assignRole(User $auth, User $target, $annexeId): bool
    {
        // Super admin institution => full control
        if ($auth->roles->contains('code', 'super_admin_institution')) {
            return true;
        }

        // Super admin annexe => only for annexes they belong to
        if ($auth->roles->contains('code', 'super_admin_annexe')) {
            return $auth->annexes->contains('id', $annexeId);
        }

        return false;
    }

    /**
     * Determine if $auth can remove a role from $target for $annexeId.
     */
    public function removeRole(User $auth, User $target, $annexeId): bool
    {
        // same rules as assign
        if ($auth->roles->contains('code', 'super_admin_institution')) {
            return true;
        }

        if ($auth->roles->contains('code', 'super_admin_annexe')) {
            return $auth->annexes->contains('id', $annexeId);
        }

        return false;
    }
}

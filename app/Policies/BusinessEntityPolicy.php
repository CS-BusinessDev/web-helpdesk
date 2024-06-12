<?php

namespace App\Policies;

use App\Models\User;
use App\Models\BusinessEntity;

class BusinessEntityPolicy
{
    /**
     * Determine whether the user can view any business entities.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view-any BusinessEntity');
    }

    /**
     * Determine whether the user can view the business entity.
     */
    public function view(User $user, BusinessEntity $businessEntity): bool
    {
        return $user->can('view BusinessEntity');
    }

    /**
     * Determine whether the user can create business entities.
     */
    public function create(User $user): bool
    {
        return $user->can('create BusinessEntity');
    }

    /**
     * Determine whether the user can update the business entity.
     */
    public function update(User $user, BusinessEntity $businessEntity): bool
    {
        return $user->can('update BusinessEntity');
    }

    /**
     * Determine whether the user can delete the business entity.
     */
    public function delete(User $user, BusinessEntity $businessEntity): bool
    {
        return $user->can('delete BusinessEntity');
    }

    /**
     * Determine whether the user can restore the business entity.
     */
    public function restore(User $user, BusinessEntity $businessEntity): bool
    {
        return $user->can('restore BusinessEntity');
    }

    /**
     * Determine whether the user can permanently delete the business entity.
     */
    public function forceDelete(User $user, BusinessEntity $businessEntity): bool
    {
        return $user->can('force-delete BusinessEntity');
    }
}

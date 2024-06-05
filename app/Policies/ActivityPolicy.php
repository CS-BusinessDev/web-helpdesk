<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ActivityPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->hasRole('Super Admin');
    }

    // public function view(User $user, $log)
    // {
    //     return $user->hasRole('admin') || $user->id === $log->user_id;
    // }
}

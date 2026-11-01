<?php

namespace App\Policies;

use App\Models\User;

/**
 * R03: solo el administrador gestiona cuentas. Las cuentas se desactivan, nunca se borran.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->esAdministrador();
    }

    public function view(User $user, User $model): bool
    {
        return $user->esAdministrador() || $user->is($model);
    }

    public function create(User $user): bool
    {
        return $user->esAdministrador();
    }

    public function update(User $user, User $model): bool
    {
        return $user->esAdministrador();
    }

    public function delete(User $user, User $model): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, User $model): bool
    {
        return false;
    }

    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }
}

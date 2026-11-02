<?php

namespace App\Policies;

use App\Models\Feriado;
use App\Models\User;

/**
 * El calendario cambia cada año por decreto, así que el administrador lo mantiene al día.
 */
class FeriadoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->esAdministrador();
    }

    public function view(User $user, Feriado $feriado): bool
    {
        return $user->esAdministrador();
    }

    public function create(User $user): bool
    {
        return $user->esAdministrador();
    }

    public function update(User $user, Feriado $feriado): bool
    {
        return $user->esAdministrador();
    }

    public function delete(User $user, Feriado $feriado): bool
    {
        return $user->esAdministrador();
    }
}

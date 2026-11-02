<?php

namespace App\Policies;

use App\Models\MetaAtencion;
use App\Models\User;

/**
 * R03: el administrador configura los plazos. Una meta no se edita ni se borra:
 * se reemplaza por otra para conservar el historial de plazos.
 */
class MetaAtencionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->esAdministrador();
    }

    public function view(User $user, MetaAtencion $meta): bool
    {
        return $user->esAdministrador();
    }

    public function create(User $user): bool
    {
        return $user->esAdministrador();
    }

    public function update(User $user, MetaAtencion $meta): bool
    {
        return false;
    }

    public function delete(User $user, MetaAtencion $meta): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}

<?php

namespace App\Policies;

use App\Models\Categoria;
use App\Models\User;

/**
 * R03: solo el administrador configura categorias. Se desactivan con "activa", nunca se borran.
 */
class CategoriaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->esAdministrador();
    }

    public function view(User $user, Categoria $categoria): bool
    {
        return $user->esAdministrador();
    }

    /**
     * RN-01: la versión 1 solo atiende 3 categorías.
     */
    public function create(User $user): bool
    {
        return $user->esAdministrador() && Categoria::count() < 3;
    }

    public function update(User $user, Categoria $categoria): bool
    {
        return $user->esAdministrador();
    }

    public function delete(User $user, Categoria $categoria): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, Categoria $categoria): bool
    {
        return false;
    }

    public function forceDelete(User $user, Categoria $categoria): bool
    {
        return false;
    }
}

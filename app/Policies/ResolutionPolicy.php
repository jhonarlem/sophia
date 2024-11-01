<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Resolution;
use Illuminate\Auth\Access\HandlesAuthorization;

class ResolutionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_resolution');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Resolution $resolution): bool
    {
        return $user->can('view_resolution');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_resolution');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Resolution $resolution): bool
    {
        return $user->can('update_resolution');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Resolution $resolution): bool
    {
        return $user->can('delete_resolution');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_resolution');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Resolution $resolution): bool
    {
        return $user->can('force_delete_resolution');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_resolution');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Resolution $resolution): bool
    {
        return $user->can('restore_resolution');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_resolution');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Resolution $resolution): bool
    {
        return $user->can('replicate_resolution');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_resolution');
    }
}

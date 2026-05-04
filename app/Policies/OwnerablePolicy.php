<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class OwnerablePolicy
{
    /**
     * `viewAny` and `view` intentionally return true for any authenticated user. Per-user
     * visibility is enforced at the query layer via the `visibleTo()` scope on the model
     * (see `App\Models\Concerns\BelongsToOwner`) and `getEloquentQuery()` overrides on
     * each Filament resource. Returning `false` here would block Filament from even
     * rendering the edit page chrome, breaking the UI.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Model $record): bool
    {
        return true;
    }

    /**
     * @param \App\Models\User $user
     *
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isEditor();
    }

    /**
     * @param \App\Models\User                    $user
     * @param \Illuminate\Database\Eloquent\Model $record
     *
     * @return bool
     */
    public function update(User $user, Model $record): bool
    {
        return $user->isAdmin() || $this->ownsOrIsOrphan($user, $record);
    }

    /**
     * @param \App\Models\User                    $user
     * @param \Illuminate\Database\Eloquent\Model $record
     *
     * @return bool
     */
    public function delete(User $user, Model $record): bool
    {
        return $user->isAdmin() || $this->ownsOrIsOrphan($user, $record);
    }

    /**
     * @param \App\Models\User                    $user
     * @param \Illuminate\Database\Eloquent\Model $record
     *
     * @return bool
     */
    public function restore(User $user, Model $record): bool
    {
        return $user->isAdmin() || $this->ownsOrIsOrphan($user, $record);
    }

    /**
     * Editors may manage records they own. Records whose original owner has been deleted
     * (user_id nulled via nullOnDelete) are considered shared and may be managed by any editor —
     * otherwise they would be permanently locked from anyone but admins.
     *
     * The user_id comparison is intentionally non-strict because PDO drivers vary in whether
     * BIGINT FK columns hydrate as int (SQLite) or string (some MySQL configurations).
     */
    protected function ownsOrIsOrphan(User $user, Model $record): bool
    {
        if (! $user->isEditor()) {
            return false;
        }

        if ($record->user_id === null) {
            return true;
        }

        return (int) $record->user_id === (int) $user->id;
    }

    /**
     * @param \App\Models\User                    $user
     * @param \Illuminate\Database\Eloquent\Model $record
     *
     * @return bool
     */
    public function forceDelete(User $user, Model $record): bool
    {
        return $user->isAdmin();
    }
}

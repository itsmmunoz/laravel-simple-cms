<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shared query scope for models with a `user_id` ownership column under nullOnDelete.
 *
 * Editors see records they own plus orphaned records (user_id null after the original
 * owner was deleted). Admins see everything. Mirrors {@see \App\Policies\OwnerablePolicy}.
 */
trait BelongsToOwner
{
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user || $user->isAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('user_id', $user->id)->orWhereNull('user_id');
        });
    }
}

<?php

namespace App\Models\Concerns;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;

trait HasUniqueSlug
{
    public function save(array $options = []): bool
    {
        try {
            return parent::save($options);
        } catch (UniqueConstraintViolationException $e) {
            // Race condition: the slug we picked is now taken.
            // Only retry if (a) we're inserting (not updating), (b) we have a slug to mutate,
            // and (c) the violation is verifiably about the slug column — not some other unique
            // constraint that happens to share the same exception type. Without (c) we'd silently
            // mutate the slug for a totally unrelated failure.
            if (! $this->exists && ! empty($this->slug) && static::where('slug', $this->slug)->exists()) {
                $this->slug .= '-'.Str::lower(Str::random(5));

                return parent::save($options);
            }

            throw $e;
        }
    }
}

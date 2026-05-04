<?php

use App\Models\Article;
use Illuminate\Database\UniqueConstraintViolationException;

it('appends random suffix when slug collides on insert', function () {
    Article::forceCreate(['title' => 'Existing', 'slug' => 'collision-target', 'content' => 'C', 'is_published' => true]);

    // Manually construct a model bypassing the booted dedup hook (which would normally pick
    // collision-target-2). We want to force the trait's race-condition retry path.
    $article = new Article(['title' => 'Race', 'content' => 'C']);
    $article->slug = 'collision-target';
    $article->saveQuietly();

    expect($article->slug)
        ->not->toBe('collision-target')
        ->toStartWith('collision-target-');
});

it('does not retry when slug is empty', function () {
    $article = new Article(['title' => 'No Slug', 'content' => 'C']);
    // Boot hook will assign slug from title because $article->slug is empty
    $article->save();

    expect($article->slug)->toBe('no-slug');
});

it('does not retry on update of existing record', function () {
    $a = Article::forceCreate(['title' => 'Update One', 'slug' => 'update-target-a', 'content' => 'C', 'is_published' => true]);
    Article::forceCreate(['title' => 'Update Two', 'slug' => 'update-target-b', 'content' => 'C', 'is_published' => true]);

    // Try to update $a's slug to collide with $b's — this should throw, not silently mutate
    $a->slug = 'update-target-b';
    expect(fn () => $a->save())->toThrow(UniqueConstraintViolationException::class);
});

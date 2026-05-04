<?php

use App\Enums\UserRole;
use App\Models\Article;
use App\Models\Page;
use App\Models\User;

it('admin sees every record', function () {
    $admin = User::create(['name' => 'A', 'email' => 'a-bto@test.com', 'password' => bcrypt('x')]);
    $admin->role = UserRole::Admin;
    $admin->save();

    $editor = User::create(['name' => 'E', 'email' => 'e-bto@test.com', 'password' => bcrypt('x')]);
    $editor->role = UserRole::Editor;
    $editor->save();

    $a = Article::forceCreate(['title' => 'A', 'slug' => 'bto-a', 'content' => 'C', 'is_published' => true, 'user_id' => $admin->id]);
    $b = Article::forceCreate(['title' => 'B', 'slug' => 'bto-b', 'content' => 'C', 'is_published' => true, 'user_id' => $editor->id]);
    $c = Article::forceCreate(['title' => 'C', 'slug' => 'bto-c', 'content' => 'C', 'is_published' => true, 'user_id' => $editor->id]);
    $editor->delete();
    $c->refresh();

    $ids = Article::query()->visibleTo($admin)->pluck('id')->all();
    expect($ids)->toContain($a->id, $b->id, $c->id);
});

it('editor sees own records and orphan records', function () {
    $admin = User::create(['name' => 'A', 'email' => 'a2-bto@test.com', 'password' => bcrypt('x')]);
    $admin->role = UserRole::Admin;
    $admin->save();

    $editor = User::create(['name' => 'E', 'email' => 'e2-bto@test.com', 'password' => bcrypt('x')]);
    $editor->role = UserRole::Editor;
    $editor->save();

    $previousOwner = User::create(['name' => 'P', 'email' => 'p-bto@test.com', 'password' => bcrypt('x')]);
    $previousOwner->role = UserRole::Editor;
    $previousOwner->save();

    $adminArticle = Article::forceCreate(['title' => 'A', 'slug' => 'bto2-a', 'content' => 'C', 'is_published' => true, 'user_id' => $admin->id]);
    $editorArticle = Article::forceCreate(['title' => 'E', 'slug' => 'bto2-e', 'content' => 'C', 'is_published' => true, 'user_id' => $editor->id]);
    $orphanArticle = Article::forceCreate(['title' => 'O', 'slug' => 'bto2-o', 'content' => 'C', 'is_published' => true, 'user_id' => $previousOwner->id]);
    $previousOwner->delete();
    $orphanArticle->refresh();

    $ids = Article::query()->visibleTo($editor)->pluck('id')->all();
    expect($ids)->toContain($editorArticle->id, $orphanArticle->id)
        ->not->toContain($adminArticle->id);
});

it('null user receives an unscoped query (auth-required contexts must guard themselves)', function () {
    $editor = User::create(['name' => 'X', 'email' => 'x-bto@test.com', 'password' => bcrypt('x')]);
    $editor->role = UserRole::Editor;
    $editor->save();
    Article::forceCreate(['title' => 'X', 'slug' => 'bto-null', 'content' => 'C', 'is_published' => true, 'user_id' => $editor->id]);

    // visibleTo(null) intentionally returns the unscoped query; consumers (Filament resources,
    // dashboard widgets) all run in panel-required-auth contexts where user is guaranteed.
    // Documenting the behavior so a future change here is a deliberate decision.
    $count = Article::query()->visibleTo(null)->count();
    expect($count)->toBeGreaterThan(0);
});

it('scope chains correctly with where and other constraints', function () {
    $editor = User::create(['name' => 'C', 'email' => 'c-bto@test.com', 'password' => bcrypt('x')]);
    $editor->role = UserRole::Editor;
    $editor->save();

    Article::forceCreate(['title' => 'P', 'slug' => 'bto-pub', 'content' => 'C', 'is_published' => true, 'user_id' => $editor->id]);
    Article::forceCreate(['title' => 'D', 'slug' => 'bto-draft', 'content' => 'C', 'is_published' => false, 'user_id' => $editor->id]);

    $publishedCount = Article::query()->visibleTo($editor)->where('is_published', true)->count();
    expect($publishedCount)->toBe(1);
});

it('works on Page model too', function () {
    $editor = User::create(['name' => 'P', 'email' => 'p2-bto@test.com', 'password' => bcrypt('x')]);
    $editor->role = UserRole::Editor;
    $editor->save();

    $page = Page::forceCreate(['title' => 'P', 'slug' => 'bto-page', 'is_published' => true, 'user_id' => $editor->id]);

    $ids = Page::query()->visibleTo($editor)->pluck('id')->all();
    expect($ids)->toContain($page->id);
});

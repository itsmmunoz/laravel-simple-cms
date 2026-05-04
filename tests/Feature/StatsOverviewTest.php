<?php

use App\Enums\UserRole;
use App\Filament\Widgets\ArticleViewsChart;
use App\Filament\Widgets\RecentActivityTable;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\TopArticlesTable;
use App\Models\Article;
use App\Models\Page;
use Livewire\Livewire;

beforeEach(function () {
    cache()->flush();
});

it('shows total users stat to admin', function () {
    $admin = createUser(UserRole::Admin);
    $this->actingAs($admin);

    Livewire::test(StatsOverview::class)
        ->assertSeeText('Total Articles')
        ->assertSeeText('Total Users');
});

it('hides total users stat from editor', function () {
    $editor = createUser(UserRole::Editor);
    $this->actingAs($editor);

    Livewire::test(StatsOverview::class)
        ->assertSeeText('Total Articles')
        ->assertDontSeeText('Total Users');
});

it('scopes article and page counts to editor own content', function () {
    $admin = createUser(UserRole::Admin);
    $editor = createUser(UserRole::Editor);

    Article::forceCreate(['title' => 'A1', 'slug' => 'a1', 'content' => 'C', 'is_published' => true, 'user_id' => $admin->id]);
    Article::forceCreate(['title' => 'A2', 'slug' => 'a2', 'content' => 'C', 'is_published' => true, 'user_id' => $admin->id]);
    Article::forceCreate(['title' => 'A3', 'slug' => 'a3', 'content' => 'C', 'is_published' => false, 'user_id' => $admin->id]);
    Article::forceCreate(['title' => 'E1', 'slug' => 'e1', 'content' => 'C', 'is_published' => true, 'user_id' => $editor->id]);
    Article::forceCreate(['title' => 'E2', 'slug' => 'e2', 'content' => 'C', 'is_published' => false, 'user_id' => $editor->id]);

    Page::forceCreate(['title' => 'AP1', 'slug' => 'ap1', 'is_published' => true, 'user_id' => $admin->id]);
    Page::forceCreate(['title' => 'EP1', 'slug' => 'ep1', 'is_published' => true, 'user_id' => $editor->id]);

    $this->actingAs($editor);
    Livewire::test(StatsOverview::class);

    $editorCounts = cache()->get('dashboard_stats:'.$editor->id);
    expect($editorCounts['totalArticles'])->toBe(2)
        ->and($editorCounts['publishedArticles'])->toBe(1)
        ->and($editorCounts['totalPages'])->toBe(1)
        ->and($editorCounts)->not->toHaveKey('totalUsers');

    $this->actingAs($admin);
    Livewire::test(StatsOverview::class);

    $adminCounts = cache()->get('dashboard_stats:'.$admin->id);
    expect($adminCounts['totalArticles'])->toBe(5)
        ->and($adminCounts['publishedArticles'])->toBe(3)
        ->and($adminCounts['totalPages'])->toBe(2)
        ->and($adminCounts)->toHaveKey('totalUsers')
        ->and($adminCounts['totalUsers'])->toBe(2);
});

it('counts orphaned content toward editor totals (consistent with resource listing)', function () {
    $admin = createUser(UserRole::Admin);
    $editor = createUser(UserRole::Editor);
    $formerEditor = createUser(UserRole::Editor);

    Article::forceCreate(['title' => 'O1', 'slug' => 'o1', 'content' => 'C', 'is_published' => true, 'user_id' => $formerEditor->id]);
    Article::forceCreate(['title' => 'O2', 'slug' => 'o2', 'content' => 'C', 'is_published' => false, 'user_id' => $formerEditor->id]);
    Article::forceCreate(['title' => 'A1', 'slug' => 'a1', 'content' => 'C', 'is_published' => true, 'user_id' => $admin->id]);
    Article::forceCreate(['title' => 'E1', 'slug' => 'e1', 'content' => 'C', 'is_published' => true, 'user_id' => $editor->id]);

    $formerEditor->delete();

    $this->actingAs($editor);
    Livewire::test(StatsOverview::class);

    $counts = cache()->get('dashboard_stats:'.$editor->id);
    expect($counts['totalArticles'])->toBe(3)
        ->and($counts['publishedArticles'])->toBe(2);
});

it('uses per-user cache keys so editors do not share counts', function () {
    $editorA = createUser(UserRole::Editor);
    $editorB = createUser(UserRole::Editor);

    Article::forceCreate(['title' => 'A1', 'slug' => 'a1-cache', 'content' => 'C', 'is_published' => true, 'user_id' => $editorA->id]);
    Article::forceCreate(['title' => 'B1', 'slug' => 'b1-cache', 'content' => 'C', 'is_published' => true, 'user_id' => $editorB->id]);
    Article::forceCreate(['title' => 'B2', 'slug' => 'b2-cache', 'content' => 'C', 'is_published' => true, 'user_id' => $editorB->id]);

    $this->actingAs($editorA);
    Livewire::test(StatsOverview::class);

    $this->actingAs($editorB);
    Livewire::test(StatsOverview::class);

    expect(cache()->get('dashboard_stats:'.$editorA->id)['totalArticles'])->toBe(1)
        ->and(cache()->get('dashboard_stats:'.$editorB->id)['totalArticles'])->toBe(2);
});

it('RecentActivityTable hides admin articles from editor', function () {
    $admin = createUser(UserRole::Admin);
    $editor = createUser(UserRole::Editor);

    $adminArticle = Article::forceCreate(['title' => 'Admin Article', 'slug' => 'admin-article-rat', 'content' => 'C', 'is_published' => true, 'user_id' => $admin->id]);
    $editorArticle = Article::forceCreate(['title' => 'Editor Article', 'slug' => 'editor-article-rat', 'content' => 'C', 'is_published' => true, 'user_id' => $editor->id]);

    $this->actingAs($editor);
    Livewire::test(RecentActivityTable::class)
        ->assertCanSeeTableRecords([$editorArticle])
        ->assertCanNotSeeTableRecords([$adminArticle]);
});

it('RecentActivityTable shows orphan articles to editor', function () {
    $formerOwner = createUser(UserRole::Editor);
    $orphan = Article::forceCreate(['title' => 'Orphan Article', 'slug' => 'orphan-rat', 'content' => 'C', 'is_published' => true, 'user_id' => $formerOwner->id]);
    $formerOwner->delete();

    $editor = createUser(UserRole::Editor);
    $this->actingAs($editor);
    Livewire::test(RecentActivityTable::class)
        ->assertCanSeeTableRecords([$orphan->fresh()]);
});

it('RecentActivityTable shows all articles to admin', function () {
    $admin = createUser(UserRole::Admin);
    $editor = createUser(UserRole::Editor);

    $adminArticle = Article::forceCreate(['title' => 'Admin Article', 'slug' => 'admin-article-rat-a', 'content' => 'C', 'is_published' => true, 'user_id' => $admin->id]);
    $editorArticle = Article::forceCreate(['title' => 'Editor Article', 'slug' => 'editor-article-rat-a', 'content' => 'C', 'is_published' => true, 'user_id' => $editor->id]);

    $this->actingAs($admin);
    Livewire::test(RecentActivityTable::class)
        ->assertCanSeeTableRecords([$adminArticle, $editorArticle]);
});

it('TopArticlesTable hides admin articles from editor', function () {
    $admin = createUser(UserRole::Admin);
    $editor = createUser(UserRole::Editor);

    $adminArticle = Article::forceCreate(['title' => 'Admin', 'slug' => 'admin-tat', 'content' => 'C', 'is_published' => true, 'user_id' => $admin->id]);
    $editorArticle = Article::forceCreate(['title' => 'Editor', 'slug' => 'editor-tat', 'content' => 'C', 'is_published' => true, 'user_id' => $editor->id]);

    $this->actingAs($editor);
    Livewire::test(TopArticlesTable::class)
        ->assertCanSeeTableRecords([$editorArticle])
        ->assertCanNotSeeTableRecords([$adminArticle]);
});

it('ArticleViewsChart counts only editor own and orphan article views', function () {
    $admin = createUser(UserRole::Admin);
    $editor = createUser(UserRole::Editor);

    $adminArticle = Article::forceCreate(['title' => 'A', 'slug' => 'a-avc', 'content' => 'C', 'is_published' => true, 'user_id' => $admin->id]);
    $editorArticle = Article::forceCreate(['title' => 'E', 'slug' => 'e-avc', 'content' => 'C', 'is_published' => true, 'user_id' => $editor->id]);

    $adminArticle->views()->create(['ip_address' => '1.1.1.1', 'viewed_at' => now()]);
    $adminArticle->views()->create(['ip_address' => '1.1.1.2', 'viewed_at' => now()]);
    $editorArticle->views()->create(['ip_address' => '2.2.2.1', 'viewed_at' => now()]);

    $this->actingAs($editor);
    $component = Livewire::test(ArticleViewsChart::class);
    $data = invade($component->instance())->getData();
    $totalCounted = array_sum($data['datasets'][0]['data']);
    expect($totalCounted)->toBe(1); // only editor's 1 view

    $this->actingAs($admin);
    $component = Livewire::test(ArticleViewsChart::class);
    $data = invade($component->instance())->getData();
    $totalCounted = array_sum($data['datasets'][0]['data']);
    expect($totalCounted)->toBe(3); // all 3 views
});

it('caches only primitives, never PHP objects', function () {
    $admin = createUser(UserRole::Admin);
    Article::forceCreate(['title' => 'X', 'slug' => 'x', 'content' => 'C', 'is_published' => true, 'user_id' => $admin->id]);

    $this->actingAs($admin);
    Livewire::test(StatsOverview::class);

    $cached = cache()->get('dashboard_stats:'.$admin->id);
    expect($cached)->toBeArray();
    foreach ($cached as $value) {
        expect($value)->toBeInt();
    }
});

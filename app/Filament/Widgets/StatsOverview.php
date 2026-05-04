<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\Article;
use App\Models\ArticleView;
use App\Models\Category;
use App\Models\Page;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getColumns(): int
    {
        return Auth::user()?->isAdmin() ? 5 : 4;
    }

    protected function getStats(): array
    {
        $user = Auth::user();
        $isAdmin = $user?->isAdmin() ?? false;

        $counts = cache()->remember(
            'dashboard_stats:'.($user?->id ?? 'guest'),
            60,
            fn () => $this->collectCounts($user, $isAdmin),
        );

        $stats = [
            Stat::make('Total Views', Number::abbreviate($counts['totalViews'] ?? 0))
                ->description('Today: '.($counts['todayViews'] ?? 0).' | This week: '.($counts['weekViews'] ?? 0))
                ->color('primary'),
            Stat::make('Total Articles', $counts['totalArticles'] ?? 0)
                ->description('Published: '.($counts['publishedArticles'] ?? 0))
                ->color('success'),
            Stat::make('Total Categories', $counts['totalCategories'] ?? 0)
                ->description('Active: '.($counts['activeCategories'] ?? 0))
                ->color('info'),
            Stat::make('Total Pages', $counts['totalPages'] ?? 0)
                ->description('Published: '.($counts['publishedPages'] ?? 0))
                ->color('warning'),
        ];

        if ($isAdmin) {
            // Defensive `?? 0`: User::booted() busts this cache key on role change so these keys
            // should always be present for admins, but a stale cache from before that hook existed
            // (or any future race) shouldn't crash the dashboard with an undefined-key error.
            $stats[] = Stat::make('Total Users', $counts['totalUsers'] ?? 0)
                ->description('Admins: '.($counts['totalAdmins'] ?? 0).' | Editors: '.($counts['totalEditors'] ?? 0))
                ->color('gray');
        }

        return $stats;
    }

    /**
     * @return array<string, int>
     */
    protected function collectCounts(?User $user, bool $isAdmin): array
    {
        $articleQuery = Article::query()->visibleTo($user);
        $pageQuery = Page::query()->visibleTo($user);

        $viewQuery = $isAdmin || ! $user
            ? ArticleView::query()
            : ArticleView::whereIn('article_id', (clone $articleQuery)->select('id'));

        $counts = [
            'totalViews' => (clone $viewQuery)->count(),
            'todayViews' => (clone $viewQuery)->whereDate('viewed_at', today())->count(),
            'weekViews' => (clone $viewQuery)->where('viewed_at', '>=', now()->subWeek())->count(),
            'totalArticles' => (clone $articleQuery)->count(),
            'publishedArticles' => (clone $articleQuery)->where('is_published', true)->count(),
            'totalCategories' => Category::count(),
            'activeCategories' => Category::where('is_active', true)->count(),
            'totalPages' => (clone $pageQuery)->count(),
            'publishedPages' => (clone $pageQuery)->where('is_published', true)->count(),
        ];

        if ($isAdmin) {
            $counts['totalUsers'] = User::count();
            $counts['totalAdmins'] = User::where('role', UserRole::Admin)->count();
            $counts['totalEditors'] = User::where('role', UserRole::Editor)->count();
        }

        return $counts;
    }
}

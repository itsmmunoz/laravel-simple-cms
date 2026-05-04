<?php

namespace App\View\Composers;

use App\Models\Page;
use Illuminate\View\View;

class NavigationComposer
{
    protected static array $footerOnlySlugs = [
        'about',
        'privacy-policy',
        'terms-of-service',
    ];

    public function compose(View $view): void
    {
        $cached = cache()->remember('nav_pages', 300, fn () => Page::published()
            ->roots()
            ->orderBy('sort_order')
            ->get(['slug', 'title'])
            ->map(fn (Page $p) => ['slug' => $p->slug, 'title' => $p->title])
            ->all());

        $pages = collect($cached)->map(fn (array $a) => (object) $a);

        $view->with([
            'navPages' => $pages->reject(fn ($p) => in_array($p->slug, self::$footerOnlySlugs)),
            'footerPages' => $pages->filter(fn ($p) => in_array($p->slug, self::$footerOnlySlugs)),
        ]);
    }
}

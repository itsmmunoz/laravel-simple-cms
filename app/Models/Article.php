<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOwner;
use App\Models\Concerns\HasUniqueSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Stevebauman\Purify\Facades\Purify;

class Article extends Model
{
    use BelongsToOwner;
    use HasUniqueSlug;

    /**
     * @var string[]
     */
    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'is_published',
        'published_at',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    /**
     * @return void
     */
    protected static function booted(): void
    {
        static::creating(function (Article $article) {
            // Auto-assign current user ID when creating
            if (empty($article->user_id) && auth()->check()) {
                $article->user_id = auth()->id();
            }

            if (empty($article->slug)) {
                $baseSlug = Str::slug($article->title);
                $slug = $baseSlug;
                $count = 1;
                while (Article::where('slug', $slug)->exists()) {
                    $slug = $baseSlug.'-'.++$count;
                }
                $article->slug = $slug;
            }
        });

        static::saving(function (Article $article) {
            if ($article->isDirty('content') && $article->content) {
                $article->content = Purify::clean($article->content);
            }
        });

        static::saved(fn (Article $article) => static::forgetDashboardStats($article));
        static::deleted(fn (Article $article) => static::forgetDashboardStats($article));
    }

    protected static function forgetDashboardStats(Article $article): void
    {
        if ($article->user_id) {
            cache()->forget('dashboard_stats:'.$article->user_id);
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function views(): HasMany
    {
        return $this->hasMany(ArticleView::class);
    }

    /**
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @param string|null $referer
     *
     * @return \App\Models\ArticleView
     */
    public function recordView(?string $ipAddress = null, ?string $userAgent = null, ?string $referer = null): ArticleView
    {
        return $this->views()->create([
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent ? mb_substr($userAgent, 0, 255) : null,
            'referer' => $referer ? mb_substr($referer, 0, 255) : null,
            'viewed_at' => now(),
        ]);
    }

    /**
     * @param $query
     *
     * @return mixed
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

}

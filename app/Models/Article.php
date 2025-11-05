<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

class Article extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'articles';

    /**
     * Mass-assignable fields.
     */
    protected $fillable = [
        'title', 'slug', 'excerpt', 'content',
        'cover_url', 'cover_alt',
        'category_id', 'tags',
        'seo_title', 'canonical_url', 'meta_description', 'meta_keywords',
        'noindex', 'nofollow',
        'status', 'published_at',
        'author_id',
    ];

    /**
     * Casts for JSON/booleans/datetime.
     */
    protected $casts = [
        'content' => 'array',
        'tags' => 'array',
        'meta_keywords' => 'array',
        'noindex' => 'boolean',
        'nofollow' => 'boolean',
        'published_at' => 'datetime',
    ];

    /**
     * Accessor appends (optional helpers).
     */
    protected $appends = [
        'url',
        'read_time',
    ];

    /** Route model binding via slug. */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** Primary category. */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ArticleCategory::class, 'category_id');
    }

    /** Additional categories via pivot. */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            ArticleCategory::class,
            'article_content_categories',
            'article_id',       // foreign key on pivot table for this model
            'category_id'       // foreign key on pivot table for related model
        )
            ->using(ArticleContentCategory::class)
            ->withPivot(['is_primary', 'position'])
            ->withTimestamps()
            ->orderBy('article_content_categories.position');
    }

    /** Author relationship (to users table). */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** Scope for published & visible articles. */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /** Computed article URL. */
    public function getUrlAttribute(): string
    {
        if (Route::has('articles.show')) {
            try {
                return route('articles.show', $this->slug);
            } catch (\Throwable $e) {
                // ignore and fallback
            }
        }

        return URL::to('/articles/'.$this->slug);
    }

    /** Estimated read time in minutes (200 wpm). */
    public function getReadTimeAttribute(): int
    {
        $text = is_array($this->content) ? json_encode($this->content) : (string) $this->content;
        $wordCount = str_word_count(strip_tags($text));

        return max(1, (int) ceil($wordCount / 200));
    }

    /**
     * Helper to sync categories with primary flag.
     *
     * @param  array<int,int>  $categoryIds
     */
    public function syncCategories(array $categoryIds, ?int $primaryId = null): void
    {
        $syncData = [];
        $pos = 0;
        foreach (array_values(array_unique($categoryIds)) as $cid) {
            $syncData[$cid] = [
                'is_primary' => $primaryId && $primaryId === (int) $cid,
                'position' => $pos++,
            ];
        }

        $this->categories()->sync($syncData);

        // optionally keep primary on main FK column too
        if ($primaryId) {
            $this->category_id = $primaryId;
            $this->save();
        }
    }
}

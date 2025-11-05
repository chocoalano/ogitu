<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArticleCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'article_categories';

    protected $fillable = [
        'name', 'slug', 'description',
        'parent_id',
        'seo_title', 'meta_description', 'meta_keywords',
        'is_active', 'position',
    ];

    protected $casts = [
        'meta_keywords' => 'array',
        'is_active' => 'boolean',
    ];

    /** Route model binding by slug. */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** Parent/children hierarchy. */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /** Related articles via pivot. */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(
            Article::class,
            'article_content_categories',
            'category_id',      // foreign key on pivot table for this model
            'article_id'        // foreign key on pivot table for related model
        )
            ->withTimestamps()
            ->withPivot(['is_primary', 'position'])
            ->orderBy('article_content_categories.position');
    }

    /** Scope only active categories. */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ArticleContentCategory extends Pivot
{
    protected $table = 'article_content_categories';

    public $incrementing = false;

    public $timestamps = true;

    protected $fillable = [
        'article_id',
        'category_id',
        'is_primary',
        'position',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'position' => 'integer',
    ];
}

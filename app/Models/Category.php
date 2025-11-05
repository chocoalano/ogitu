<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Category
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property string $slug
 * @property string $path
 * @property bool $is_age_restricted
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Category|null $category
 * @property Collection|Category[] $categories
 * @property Collection|Product[] $products
 */
class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';

    protected $casts = [
        'parent_id' => 'int',
        'is_age_restricted' => 'bool',
    ];

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'path',
        'is_age_restricted',
    ];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'primary_category_id');
    }

    public function scopeActive($q)
    {
        return $q->when(\Schema::hasColumn('categories', 'is_active'), fn ($qq) => $qq->where('is_active', true));
    }
}

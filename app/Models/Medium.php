<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Medium
 *
 * @property int $id
 * @property string $owner_type
 * @property int $owner_id
 * @property string $url
 * @property string|null $alt
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Medium extends Model
{
    use HasFactory;

    protected $table = 'media';

    protected $casts = [
        'owner_id' => 'int',
        'position' => 'int',
    ];

    protected $fillable = [
        'owner_type',
        'owner_id',
        'url',
        'alt',
        'position',
    ];
}

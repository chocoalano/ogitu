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
 * Class LedgerTransaction
 *
 * @property int $id
 * @property string $type
 * @property string $status
 * @property string|null $ref_type
 * @property int|null $ref_id
 * @property Carbon $occurred_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection|LedgerEntry[] $ledger_entries
 */
class LedgerTransaction extends Model
{
    use HasFactory;

    protected $table = 'ledger_transactions';

    protected $casts = [
        'ref_id' => 'int',
        'occurred_at' => 'datetime',
    ];

    protected $fillable = [
        'type',
        'status',
        'ref_type',
        'ref_id',
        'occurred_at',
    ];

    public function ledger_entries()
    {
        return $this->hasMany(LedgerEntry::class);
    }
}

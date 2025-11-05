<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

/**
 * Class Customer
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property Carbon|null $dob
 * @property string $password_hash
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection|Address[] $addresses
 * @property Collection|AgeCheck[] $age_checks
 * @property Collection|KycProfile[] $kyc_profiles
 * @property Collection|Order[] $orders
 * @property Collection|ProductReview[] $product_reviews
 * @property Collection|Topup[] $topups
 * @property Collection|Vendor[] $vendors
 */
class Customer extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'customers';

    protected $casts = [
        'dob' => 'datetime',
    ];

    protected $fillable = [
        'customer_code',
        'name',
        'email',
        'phone',
        'dob',
        'password',
        'status',
        'remember_token',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Kita bungkus dalam transaksi untuk mengunci baris dan mencegah race condition
            // ini sangat penting untuk memastikan urutan kode yang unik dan benar.
            DB::transaction(function () use ($model) {
                $prefix = 'CST-';
                $codeLength = 5; // Misalnya, kita ingin nomor urut 5 digit: 00001, 00002
                // 1. Cari pelanggan terakhir yang ada.
                // Kita gunakan lockForUpdate() agar proses lain tidak bisa membaca/mengubah baris ini
                // sebelum transaksi selesai.
                $latestCustomer = static::lockForUpdate()
                    ->where('customer_code', 'LIKE', $prefix.'%')
                    ->orderBy('customer_code', 'desc')
                    ->first();
                $newNumber = 1;
                if ($latestCustomer) {
                    // 2. Ambil bagian nomor urut dari kode terakhir (misal dari CUST-000123 diambil 123)
                    $lastCode = $latestCustomer->customer_code;
                    $lastNumber = (int) substr($lastCode, strlen($prefix));
                    // 3. Naikkan nomor urut
                    $newNumber = $lastNumber + 1;
                }
                // 4. Format nomor urut menjadi string dengan padding nol
                $model->customer_code = $prefix.str_pad($newNumber, $codeLength, '0', STR_PAD_LEFT);
            });
        });
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function age_checks()
    {
        return $this->hasMany(AgeCheck::class);
    }

    public function kyc_profiles()
    {
        return $this->hasMany(KycProfile::class);
    }

    public function wallet_accounts()
    {
        return $this->morphMany(WalletAccount::class, 'owner');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function product_reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function topups()
    {
        return $this->hasMany(Topup::class);
    }

    public function vendors()
    {
        return $this->hasMany(Vendor::class);
    }

    public function wishlist()
    {
        return $this->hasOne(Wishlist::class);
    }

    public function mlmMember()
    {
        return $this->hasOne(MlmMember::class);
    }
}

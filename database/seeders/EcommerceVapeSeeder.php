<?php

namespace Database\Seeders;

use App\Enums\ListingStatus;
use App\Enums\WalletOwnerType;
use App\Models\Address;
use App\Models\AgeCheck;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\KycProfile;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use App\Models\Medium;
use App\Models\Product;
use App\Models\ProductRelation;
use App\Models\ProductRestriction;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\Vendor;
use App\Models\VendorListing;
use App\Models\WalletAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EcommerceVapeSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            /* -------------------------------------------------------
             | 1) CUSTOMER + ADDRESS + KYC + AGE CHECK
             ------------------------------------------------------- */
            $customers = Customer::factory()
                ->count(30)->adult(21, 50)->create();

            // Buat 2 alamat per customer (shipping & billing default)
            $customers->each(function (Customer $c) {
                $ship = Address::factory()->forCustomer($c)->defaultShipping()->create();
                $bill = Address::factory()->forCustomer($c)->defaultBilling()->create();

                // KYC: 70% verified, sisanya pending/rejected
                $kyc = KycProfile::factory()->forCustomer($c)->idKtp()->create();
                if (rand(1, 100) <= 70) {
                    $kyc->update(['status' => 'verified', 'verified_at' => now()->subDays(rand(0, 20))]);
                } elseif (rand(0, 1)) {
                    $kyc->update(['status' => 'rejected', 'notes' => 'Dokumen tidak terbaca']);
                }

                // Age check: 85% pass
                AgeCheck::factory()
                    ->forCustomer($c)
                    ->state(['method' => 'kyc', 'result' => (rand(1, 100) <= 85 ? 'pass' : 'fail')])
                    ->create();
            });

            /* -------------------------------------------------------
             | 2) VENDOR + SHOPS
             ------------------------------------------------------- */
            // Pilih 8 customer untuk jadi vendor
            $vendorOwners = $customers->random(min(8, $customers->count()));
            $vendors = collect();
            $shops = collect();

            foreach ($vendorOwners as $owner) {
                $vendor = Vendor::factory()->forCustomer($owner)->active()->create();
                $vendors->push($vendor);

                // 1-2 shop per vendor
                $shopCount = rand(1, 2);
                for ($i = 0; $i < $shopCount; $i++) {
                    $shop = Shop::factory()
                        ->forVendor($vendor)
                        ->open()
                        ->withRating(rand(35, 48) / 10) // 3.5–4.8
                        ->create();

                    // pakai alamat shipping default customer sebagai pickup ketika ada
                    $pickup = $owner->addresses()->where('is_default_shipping', true)->first();
                    if ($pickup) {
                        $shop->update(['pickup_address_id' => $pickup->id]);
                    }
                    $shops->push($shop);
                }
            }

            /* -------------------------------------------------------
             | 3) BRANDS
             ------------------------------------------------------- */
            Brand::factory()->device()->count(8)->create();
            Brand::factory()->liquid()->count(10)->create();
            Brand::factory()->accessory()->count(6)->create();

            /* -------------------------------------------------------
             | 4) CATEGORIES (root + children)
             ------------------------------------------------------- */
            $devicesRoot = Category::firstOrCreate(
                ['slug' => 'devices'],
                ['name' => 'Devices', 'path' => 'devices', 'parent_id' => null, 'is_age_restricted' => true]
            );
            $liquidsRoot = Category::firstOrCreate(
                ['slug' => 'liquids'],
                ['name' => 'Liquids', 'path' => 'liquids', 'parent_id' => null, 'is_age_restricted' => true]
            );
            $accessRoot = Category::firstOrCreate(
                ['slug' => 'accessories'],
                ['name' => 'Accessories', 'path' => 'accessories', 'parent_id' => null, 'is_age_restricted' => true]
            );

            foreach (['mod', 'pod-system', 'pod-refillable', 'disposable', 'aio'] as $s) {
                Category::firstOrCreate(
                    ['slug' => $s],
                    ['name' => Str::title(str_replace('-', ' ', $s)), 'path' => $devicesRoot->path.'/'.$s, 'parent_id' => $devicesRoot->id, 'is_age_restricted' => true]
                );
            }
            foreach (['freebase', 'salt'] as $s) {
                Category::firstOrCreate(
                    ['slug' => $s],
                    ['name' => Str::title($s), 'path' => $liquidsRoot->path.'/'.$s, 'parent_id' => $liquidsRoot->id, 'is_age_restricted' => true]
                );
            }
            foreach (['rda', 'rta', 'rdta', 'tank', 'cartridge', 'coil', 'cotton', 'battery', 'charger', 'tools', 'replacement-pod'] as $s) {
                Category::firstOrCreate(
                    ['slug' => $s],
                    ['name' => Str::title(str_replace('-', ' ', $s)), 'path' => $accessRoot->path.'/'.$s, 'parent_id' => $accessRoot->id, 'is_age_restricted' => true]
                );
            }

            /* -------------------------------------------------------
             | 5) PRODUCTS (otomatis buat subtype via ProductFactory::configure)
             ------------------------------------------------------- */
            $products = collect();
            $products = $products->merge(Product::factory()->liquid()->count(20)->create());
            $products = $products->merge(Product::factory()->device()->count(12)->create());
            $products = $products->merge(Product::factory()->accessory()->count(8)->create());

            /* -------------------------------------------------------
             | 6) VARIANTS per PRODUCT (3–5/produk)
             ------------------------------------------------------- */
            $variants = collect();
            $products->each(function (Product $p) use (&$variants) {
                $variants = $variants->merge(
                    ProductVariant::factory()->count(rand(3, 5))->forProduct($p)->create()
                );
            });

            /* -------------------------------------------------------
             | 7) MEDIA (produk, varian, shop)
             ------------------------------------------------------- */
            $products->each(function (Product $p) {
                // 1 foto utama + 1 foto tambahan
                Medium::factory()->forProduct($p)->primary()->create();
                Medium::factory()->forProduct($p)->position(1)->create();
            });

            $variants->random(min(60, $variants->count()))->each(function ($v) {
                Medium::factory()->forVariant($v)->primary()->create();
            });

            $shops->each(function (Shop $s) {
                Medium::factory()->forShop($s)->primary()->create();
            });

            /* -------------------------------------------------------
             | 8) LISTINGS (varian → shop)
             ------------------------------------------------------- */
            // Untuk efisiensi: setiap shop ambil subset varian acak dan buat listing
            $variantsPool = $variants->pluck('id')->all();
            foreach ($shops as $shop) {
                $take = min(60, count($variantsPool));
                $sub = Arr::random($variantsPool, $take);

                foreach ($sub as $variantId) {
                    VendorListing::factory()
                        ->forShop($shop)
                        ->forVariant($variantId)
                        ->state(function () {
                            // 10% banned, 10% inactive, 15% out_of_stock, sisanya active
                            $roll = rand(1, 100);
                            $status = ListingStatus::ACTIVE->value;
                            if ($roll <= 10) {
                                $status = ListingStatus::BANNED->value;
                            } elseif ($roll <= 20) {
                                $status = ListingStatus::INACTIVE->value;
                            } elseif ($roll <= 35) {
                                $status = ListingStatus::OUT_OF_STOCK->value;
                            }

                            return ['status' => $status];
                        })
                        ->create();
                }
            }

            /* -------------------------------------------------------
             | 9) COUPONS (platform + per shop)
             ------------------------------------------------------- */
            // 8 kupon platform aktif
            Coupon::factory()->percent()->platform()->liveNow()->count(5)->create();
            Coupon::factory()->amount()->platform()->liveNow()->count(3)->create();

            // 1–2 kupon per shop
            foreach ($shops as $shop) {
                Coupon::factory()->percent(rand(10, 25))->forShop($shop)->liveNow()->withMinOrder(150_000)->create();
                if (rand(0, 1)) {
                    Coupon::factory()->amount(rand(20_000, 50_000))->forShop($shop)->future()->withUsageLimit(100)->create();
                }
            }

            /* -------------------------------------------------------
             | 10) PRODUCT RESTRICTIONS (ID: min 21 utk sebagian besar)
             ------------------------------------------------------- */
            $products->random(min(30, $products->count()))->each(function (Product $p) {
                ProductRestriction::factory()->forProduct($p)->inCountry('ID')->min21()->create();
            });

            /* -------------------------------------------------------
             | 11) PRODUCT RELATIONS (similar/compatible, dua arah)
             ------------------------------------------------------- */
            ProductRelation::factory()
                ->count(40)
                ->preventDuplicates()
                ->bidirectional()
                ->create();

            /* -------------------------------------------------------
             | 12) WALLETS + TOPUP SEDERHANA (Ledger optional)
             |     - Buat wallet untuk platform, setiap customer & shop
             |     - Contoh topup: DEBIT customer, CREDIT platform (double-entry)
             |     Catatan: saldo platform dibuat besar agar tidak negatif.
             ------------------------------------------------------- */
            $platformWallet = WalletAccount::firstOrCreate([
                'owner_type' => WalletOwnerType::PLATFORM->value,
                'owner_id' => 0, // dummy
                'currency' => 'IDR',
            ], [
                'balance' => 100_000_000, // modal awal platform utk demo ledger
                'status' => 'active',
            ]);

            // Wallet customers
            $customers->each(function (Customer $c) {
                WalletAccount::firstOrCreate([
                    'owner_type' => WalletOwnerType::CUSTOMER->value,
                    'owner_id' => $c->id,
                    'currency' => 'IDR',
                ], [
                    'balance' => 0,
                    'status' => 'active',
                ]);
            });

            // Wallet shops
            $shops->each(function (Shop $s) {
                WalletAccount::firstOrCreate([
                    'owner_type' => WalletOwnerType::SHOP->value,
                    'owner_id' => $s->id,
                    'currency' => 'IDR',
                ], [
                    'balance' => 0,
                    'status' => 'active',
                ]);
            });

            // TOPUP sample untuk 12 customer (pakai LedgerTransaction + LedgerEntry factories)
            $topupCustomers = $customers->random(min(12, $customers->count()));
            foreach ($topupCustomers as $cust) {
                $custWallet = WalletAccount::where([
                    'owner_type' => WalletOwnerType::CUSTOMER->value,
                    'owner_id' => $cust->id,
                ])->first();

                $amount = rand(200_000, 1_200_000);

                // transaksi ledger
                $txn = LedgerTransaction::factory()
                    ->topup()->posted()->forCustomer($cust)
                    ->withinLastDays(10)
                    ->create();

                // DEBIT customer (saldo naik)
                LedgerEntry::factory()
                    ->forTransaction($txn)
                    ->forAccount($custWallet)
                    ->debit($amount)
                    ->syncAccountCache()
                    ->create();

                // CREDIT platform (saldo turun dari modal awal)
                LedgerEntry::factory()
                    ->forTransaction($txn)
                    ->forAccount($platformWallet)
                    ->credit($amount)
                    ->syncAccountCache()
                    ->create();
            }

            /* -------------------------------------------------------
             | 13) OPSIONAL: Seed review/escrow jika tabel order tersedia
             | (Aman untuk dilewati jika modul order belum dibuat)
             ------------------------------------------------------- */
            // contoh aman:
            // if (\Illuminate\Support\Facades\Schema::hasTable('order_items')) {
            //     $oi = \App\Models\OrderItem::inRandomOrder()->first();
            //     if ($oi) {
            //         \App\Models\ProductReview::factory()->forOrderItem($oi)->positive()->create();
            //     }
            // }

        });
    }
}

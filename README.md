# E-Commerce Vape Multi-Vendor – README

Dokumentasi ringkas tentang **alur sistem**, **menu**, dan **cara kerja** platform e-commerce **multi-vendor** khusus produk vape (device, liquid, aksesori) dengan **age-gate + KYC**, **e-wallet + double-entry ledger**, **escrow**, dan **panel admin/vendor** berbasis **Laravel + Filament**.

---

## 1) Fitur Utama

- **Katalog Pusat**: Brand, Category (tree devices/liquids/accessories), Product (+ subtype device/liquid/accessory), Product Variant, Media.
- **Multi-Vendor**: Vendor (pemilik toko), Shop (satu vendor bisa punya beberapa toko), Vendor Listing (harga/stok per varian per toko).
- **Kepatuhan & Keamanan**: Age-check, KYC, Product Restriction (min age & country).
- **Promosi**: Kupon platform & kupon toko (percent/amount, min order, usage limit, periode).
- **Dompet & Keuangan**: Wallet Account (customer/shop/platform/escrow), Ledger (transaction & entry double-entry), Escrow untuk order per-toko.
- **Relasi Produk**: compatible_with, recommended_with, uses, replacement_for (untuk lintas perangkat/aksesoris).
- **Ulasan Produk**: Product Review (pasca pembelian—opsional jika modul order aktif).

---

## 2) Arsitektur Singkat

**Lapisan utama:**
- **Domain Data (DB/Models)**: Tabel inti sesuai migrasi (customers, vendors, shops, products, product_variants, vendor_listings, media, coupons, product_restrictions, product_relations, wallet_accounts, ledger_transactions, ledger_entries, escrows, kyc_profiles, age_checks, addresses).
- **Aplikasi (Filament Panels)**:
  - **Admin Panel**: kurasi katalog, moderasi listing/kupon, audit ledger/escrow, verifikasi KYC/usia.
  - **Vendor Panel**: kelola toko, listing, kupon toko, dan dompet toko.
- **Integritas Keuangan**: **double-entry** (setiap transaksi punya minimal 2 **ledger_entries**: debit & kredit).

---

## 3) Alur Proses Utama

### 3.1. Pendaftaran & Verifikasi Usia (Customer)
1. **Customer** membuat akun → tabel `customers`.
2. **Age-gate**: 
   - `age_checks` dicatat (method `kyc|selfie|third_party`, `result pass|fail`).
   - **KYC** (opsional/mandatory) → `kyc_profiles` (`pending|verified|rejected`).
3. **Restriksi Produk**:
   - `product_restrictions` (per product, `country_code`, `min_age`).  
   - Saat browsing/checkout, sistem validasi usia & negara.

### 3.2. Menjadi Vendor & Membuka Toko
1. **Customer → Vendor** (`vendors`) → status `pending|active|suspended`.
2. **Vendor membuat Shop** (`shops`), set **pickup_address**.
3. **Media Shop** (logo/banner) melalui `media`.

### 3.3. Katalog Pusat (Admin)
1. Admin kelola **Brand** & **Category** (tree: `devices/liquids/accessories` dan child).
2. Admin buat **Product** (type `device|liquid|accessory`):
   - Subtype disimpan di tabel khusus (device/liquid/accessory) untuk spesifikasi.
3. **Product Variant** (warna, coil ohm, bottle ml, nic mg, dll).
4. **Media** produk/varian dihubungkan via `owner_type` (`product|variant|shop`).

### 3.4. Listing oleh Toko (Vendor)
1. Vendor pilih **Product Variant** dari katalog → buat **Vendor Listing** (`vendor_listings`) berisi: `price`, `promo_price`, `promo_ends_at`, `qty_available`, `min_order_qty`, `condition (new|used)`, `status (active|inactive|out_of_stock|banned)`.
2. Admin dapat melakukan **moderasi** (mis. ban listing).

### 3.5. Kupon
- `coupons`:
  - `type`: `percent|amount`
  - `applies_to`: `platform|shop`
  - `shop_id` jika `shop`
  - `min_order`, `max_uses`, `starts_at`, `ends_at`, `status`
- Validasi penggunaan saat checkout (nilai akhir, kuota, periode).

### 3.6. Dompet, Ledger & Escrow
- **Wallet Accounts (`wallet_accounts`)**:
  - `owner_type`: `customer|shop|platform|escrow`
  - `balance` adalah **cache**; kebenaran ada di **ledger**.
- **Ledger**:
  - `ledger_transactions`: header (type `topup|purchase_hold|purchase_capture|refund|payout|withdrawal|reversal|fee_capture`, status `posted|pending|void`, referensi bisnis).
  - `ledger_entries`: detail sisi akun (`debit|credit`, `amount`, `balance_after`).
  - **Prinsip**: Akun aset (wallet) **DEBIT = tambah saldo**, **CREDIT = kurangi saldo**.
- **Escrow** (`escrows`):
  - Menahan dana untuk **sub-order toko**; rilis **full/partial/refund** memicu ledger posting ke akun toko / balik ke customer sesuai alur.

> Catatan: Alur checkout/order tidak dibahas detail di README ini. Saat modul order aktif, integrasikan: **Order → OrderShop → Escrow → Capture/Release → Payout**.

### 3.7. Relasi Produk
- `product_relations.relation_type` **ENUM**: `compatible_with`, `recommended_with`, `uses`, `replacement_for`.
- Simetris: `compatible_with`, `recommended_with` (opsi buat **relasi balik** otomatis).
- Asimetris: `uses`, `replacement_for` (jangan buat relasi balik otomatis).

### 3.8. Ulasan Produk (opsional)
- `product_reviews` ditautkan ke `order_items` untuk validasi **verified purchase**.

---

## 4) Menu & Panel (Filament)

### 4.1. Admin Panel (Backoffice)
- **Catalog**
  - **Brands**
  - **Categories** (tree)
  - **Products** (+ RelationManagers: Variants, Media, Restrictions, Relations)
  - **Product Variants**
  - **Media**
  - **Product Restrictions**
  - **Product Relations**
- **Vendors**
  - **Customers** (+ Addresses, KYC, Age Checks, Wallet)
  - **Vendors** (+ Shops)
  - **Shops** (+ Media, Listings, Wallet)
  - **Vendor Listings** (moderasi)
  - **Coupons** (platform & shop)
- **Finance**
  - **Wallet Accounts** (audit & link ke entries)
  - **Ledger Transactions** (header; expand ke entries)
  - **Ledger Entries** (read-only)
  - **Escrows**
- **Compliance**
  - **KYC Profiles** (verify/reject)
  - **Age Checks**
  - **Addresses**
- **Dashboard Widgets** (opsional)
  - KYC pending, saldo platform, top product/listing, escrow summary.

### 4.2. Vendor Panel (Toko)
- **Toko Saya** (Shop scoped)
  - Profil toko, pickup address, media.
- **Katalog Saya**
  - **My Listings** (scoped ke shop vendor)
  - **Shop Coupons** (scoped)
  - **Catalog Browser** (custom page: pilih variant → buat listing)
- **Keuangan**
  - **Shop Wallet** (saldo & riwayat ledger, request withdrawal)

> Batasi akses dengan **Policies/Scopes** sehingga vendor hanya melihat data miliknya (`whereBelongsTo(auth()->user()->vendor)`).

---

## 5) Model Data & Enum (Ringkas)

- **Product.type**: `device|liquid|accessory`
- **Device.form_factor**: `mod|pod_system|pod_refillable|disposable|aio`
- **Liquid.intended_device**: `mod|pod|both`
- **Liquid.flavor_family**: `fruit|drink|dessert|mint_ice|tobacco|other`
- **Accessory.accessory_type**: `atomizer|tank|cartridge|coil|cotton|battery|charger|tools|replacement_pod`
- **ProductRelation.relation_type**: `compatible_with|recommended_with|uses|replacement_for`
- **VendorListing.condition**: `new|used` *(pastikan enum/tipe kolom cocok—lihat Troubleshooting)*
- **WalletAccount.owner_type**: `customer|shop|platform|escrow`
- **WalletAccount.status**: `active|suspended`
- **LedgerTransaction.type**: `topup|purchase_hold|purchase_capture|refund|payout|withdrawal|reversal|fee_capture`
- **LedgerTransaction.status**: `posted|pending|void`
- **LedgerEntry.direction**: `debit|credit`
- **Escrow.status**: `held|released|partial_released|refunded`
- **Coupon.type**: `percent|amount`
- **Coupon.applies_to**: `platform|shop`

---

## 6) Instalasi & Seeding

### 6.1. Persiapan
```bash
cp .env.example .env
# Set DB_*, APP_URL, dsb.
composer install
php artisan key:generate
```

### 6.2. Migrasi & Seed
```bash
php artisan migrate
# atau jika fresh
php artisan migrate:fresh

# Seeder utama:
php artisan db:seed --class=Database\Seeders\EcommerceVapeSeeder
```

> Seeder ini membuat: customers (+address, kyc, age-check), vendors & shops, brands & categories, products + variants + media, vendor listings, coupons, product restrictions/relations, wallet platform + wallet customer/shop, dan contoh **ledger top-up**.

### 6.3. Akses Filament
```bash
php artisan make:filament-user
```
Buat akun admin, lalu akses **/admin** (atau sesuai panel route yang kamu set).

---

## 7) Cara Kerja (Operational Notes)

- **Age-Gate**: sebelum izinkan lihat/checkout produk age-restricted (semua vape), validasi umur & country. `product_restrictions` menentukan `min_age` per negara.
- **Listing**: vendor hanya bisa listing **Product Variant** dari katalog pusat. Harga/promo/stok-nya spesifik per toko.
- **Kupon**: 
  - Platform coupon berlaku lintas toko.
  - Shop coupon hanya untuk toko tersebut.
  - Terapkan validasi: periode, min order, kuota (`max_uses - used`).
- **E-Wallet & Ledger**:
  - Jangan mutasi `wallet_accounts.balance` langsung di bisnis. Gunakan **Ledger** (transactions + entries).  
  - **DEBIT akun aset** → saldo naik; **CREDIT** → saldo turun.  
  - Simpan ringkasan di `balance` via sinkronisasi (contoh helper `syncAccountCache()` pada factory).
- **Escrow**: dana hold per sub-order toko (`order_shop`). Release/refund menghasilkan posting ledger ke akun **shop/customer/platform** sesuai alur.

---

## 8) Skenario Uji Cepat

- **Vendor Listing**: buat listing aktif dengan promo 15% → pastikan `promo_ends_at` & kalkulasi harga tampil di frontend.
- **Kupon Platform**: buat percent 10% aktif → cek validasi min_order, periode, usage.
- **Age/KYC**: set customer underage → pastikan produk/checkout tertolak sesuai `min_age`.
- **Ledger**: buat transaksi top-up → verifikasi **sepasang entry** (DEBIT customer, CREDIT platform) dan saldo wallet tersinkron.
- **Product Relations**:
  - `compatible_with` → buat relasi dua arah otomatis.
  - `uses` → **tanpa** relasi balik.

---

## 9) Troubleshooting (Umum)

- **`customer_code` null / 1364**  
  Tambahkan pengisian `customer_code` di **CustomerFactory** atau hook `creating` (pastikan event **tidak** dimatikan dengan `WithoutModelEvents`).  

- **Hook `boot()` tidak jalan saat seeding**  
  Cek `DatabaseSeeder`—hapus/keluarkan dari trait `WithoutModelEvents`, atau aktifkan event dispatcher sebelum memanggil seeder.

- **`vendor_listings.condition` Data truncated**  
  Pastikan tipe kolom menerima `'new'|'used'`.  
  - Jika kolom `TINYINT`, kirim **0/1**.  
  - Jika `ENUM('NEW','USED')`, kirim huruf **besar**.  
  Disarankan ganti skema jadi string/enum `'new'|'used'`.

- **`product_relations.relation_type` ENUM mismatch**  
  Pastikan hanya pakai: `compatible_with|recommended_with|uses|replacement_for`.  
  Factory sudah disesuaikan.

---

## 10) Roadmap (Opsional)

- Modul **Order** lengkap (Cart → Checkout → Payment → Order → OrderShop → Escrow → Capture/Release → Payout).
- **Payout/Withdrawal** workflow + approval Finance.
- **Search & Facet** (Elasticsearch/Meilisearch) untuk katalog & listing.
- **Import Bulk** listing vendor (CSV/XLSX).
- **Notifikasi** (email/WA) untuk KYC, escrow release, low stock, dsb.

---

## 11) Lisensi

Internal project (sesuaikan kebutuhanmu).

---

## 12) Kredit

- Laravel, Filament.
- Semua factory & seeder khusus **multi-vendor vape** di repo ini.



<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

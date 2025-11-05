<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /* =============================================================
         * 1) IDENTITAS & OTENTIKASI
         * ============================================================= */
        Schema::create('customers', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->comment('Tabel akun pelanggan/pengguna (juga dapat menjadi PIC vendor).');
            $table->id()->comment('Primary key pelanggan.');
            $table->string('customer_code')->unique()->comment('Kode unik pelanggan untuk referensi eksternal.');
            $table->string('name', 150)->comment('Nama lengkap pelanggan untuk tampilan.');
            $table->string('email', 191)->unique()->comment('Email unik untuk login & notifikasi.');
            $table->string('phone', 50)->nullable()->comment('Nomor telepon pelanggan untuk kontak/OTP.');
            $table->date('dob')->nullable()->comment('Tanggal lahir untuk verifikasi usia (age gate).');
            $table->string('password', 255)->comment('Hash kata sandi (jangan simpan plain text).');
            $table->enum('status', ['active', 'suspended'])->default('active')->comment('Status akun pelanggan.');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('kyc_profiles', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->comment('Profil KYC untuk verifikasi usia/kepatuhan regulasi.');
            $table->id()->comment('Primary key KYC.');
            $table->unsignedBigInteger('customer_id')->comment('FK ke customers.id (pemilik data KYC).');
            $table->enum('id_type', ['ktp', 'passport', 'other'])->comment('Jenis identitas resmi yang digunakan.');
            $table->string('id_number', 100)->comment('Nomor identitas resmi (KTP/Passport).');
            $table->string('full_name_on_id', 191)->comment('Nama sesuai yang tercetak di identitas.');
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending')->comment('Status verifikasi KYC.');
            $table->timestamp('verified_at')->nullable()->comment('Waktu diverifikasi (jika lolos).');
            $table->text('notes')->nullable()->comment('Catatan verifikator/alasan penolakan.');
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers');
            $table->index(['customer_id', 'status']);
        });

        Schema::create('addresses', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->comment('Alamat pelanggan untuk pengiriman/tagihan serta titik ambil toko.');
            $table->id()->comment('Primary key alamat.');
            $table->unsignedBigInteger('customer_id')->comment('FK ke customers.id (pemilik alamat).');
            $table->string('label', 100)->nullable()->comment('Label alamat, contoh: Rumah/Kantor.');
            $table->string('recipient_name', 150)->comment('Nama penerima paket.');
            $table->string('phone', 50)->nullable()->comment('Nomor telepon penerima.');
            $table->string('line1', 191)->comment('Baris alamat 1 (jalan/nomor).');
            $table->string('line2', 191)->nullable()->comment('Baris alamat 2 (opsional).');
            $table->string('city', 120)->comment('Kota/kabupaten.');
            $table->string('state', 120)->nullable()->comment('Provinsi/region.');
            $table->string('postal_code', 30)->nullable()->comment('Kode pos.');
            $table->string('country_code', 2)->default('ID')->comment('Kode negara ISO 3166-1 alpha-2.');
            $table->boolean('is_default_shipping')->default(false)->comment('Tandai sebagai alamat default pengiriman.');
            $table->boolean('is_default_billing')->default(false)->comment('Tandai sebagai alamat default penagihan.');
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers');
            $table->index(['customer_id', 'is_default_shipping']);
        });

        /* =============================================================
         * 2) VENDOR & TOKO
         * ============================================================= */
        Schema::create('vendors', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->comment('Entitas vendor (badan usaha) yang mengoperasikan toko.');
            $table->id()->comment('Primary key vendor.');
            $table->unsignedBigInteger('customer_id')->comment('FK ke customers.id (PIC/penanggung jawab vendor).');
            $table->string('company_name', 191)->comment('Nama perusahaan/badan usaha terdaftar.');
            $table->string('npwp', 64)->nullable()->comment('NPWP (opsional).');
            $table->string('phone', 50)->nullable()->comment('Nomor telepon kontak vendor.');
            $table->enum('status', ['pending', 'active', 'suspended'])->default('pending')->comment('Status onboarding/operasional vendor.');
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers');
            $table->index(['customer_id', 'status']);
        });

        Schema::create('shops', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->comment('Toko etalase milik vendor (bisa lebih dari satu per vendor).');
            $table->id()->comment('Primary key toko.');
            $table->unsignedBigInteger('vendor_id')->comment('FK ke vendors.id (pemilik toko).');
            $table->string('name', 191)->comment('Nama toko yang ditampilkan ke publik.');
            $table->string('slug', 191)->unique()->comment('Slug unik untuk URL toko.');
            $table->text('description')->nullable()->comment('Deskripsi/biografi toko.');
            $table->unsignedBigInteger('pickup_address_id')->nullable()->comment('FK ke addresses.id untuk lokasi ambil di tempat.');
            $table->decimal('rating_avg', 3, 2)->default(0.00)->comment('Rata-rata rating toko (dari ulasan).');
            $table->enum('status', ['open', 'closed', 'suspended'])->default('open')->comment('Status operasional toko.');
            $table->timestamps();

            $table->foreign('vendor_id')->references('id')->on('vendors');
            $table->foreign('pickup_address_id')->references('id')->on('addresses');
            $table->index(['vendor_id', 'status']);
        });

        /* =============================================================
         * 3) KATALOG (Brand, Kategori, Produk)
         * ============================================================= */
        Schema::create('brands', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->comment('Merek produk untuk device/liquid/aksesoris.');
            $table->id()->comment('Primary key brand.');
            $table->string('name', 191)->unique()->comment('Nama merek (unik).');
            $table->string('slug', 191)->unique()->comment('Slug unik untuk URL merek.');
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->comment('Taksonomi kategori produk (mendukung parent-child).');
            $table->id()->comment('Primary key kategori.');
            $table->unsignedBigInteger('parent_id')->nullable()->comment('Self FK ke categories.id (NULL untuk root).');
            $table->string('name', 191)->comment('Nama kategori.');
            $table->string('slug', 191)->unique()->comment('Slug unik kategori.');
            $table->string('path', 255)->comment('Path materialized seperti device/mod atau liquid/salt.');
            $table->boolean('is_age_restricted')->default(true)->comment('Apakah kategori butuh verifikasi usia.');
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('categories');
            $table->index(['parent_id', 'slug']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->comment('Produk dasar di katalog terpadu; detail spesifik via subtype & varian.');
            $table->id()->comment('Primary key produk.');
            $table->unsignedBigInteger('brand_id')->nullable()->comment('FK ke brands.id (opsional).');
            $table->unsignedBigInteger('primary_category_id')->comment('FK ke categories.id (kategori utama).');
            $table->enum('type', ['device', 'liquid', 'accessory'])->comment('Tipe produk tingkat atas.');
            $table->string('name', 191)->comment('Nama produk untuk tampilan.');
            $table->string('slug', 191)->unique()->comment('Slug unik untuk URL produk.');
            $table->mediumText('description')->nullable()->comment('Deskripsi detail produk.');
            $table->boolean('is_active')->default(true)->comment('Apakah produk aktif ditampilkan.');
            $table->boolean('is_age_restricted')->default(true)->comment('Harus lolos age-check untuk dibeli.');
            $table->json('specs')->nullable()->comment('JSON fleksibel untuk spesifikasi umum.');
            $table->timestamps();
            $table->softDeletes()->comment('Timestamp soft delete produk.');

            $table->foreign('brand_id')->references('id')->on('brands');
            $table->foreign('primary_category_id')->references('id')->on('categories');
            $table->index(['type', 'is_active']);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->comment('Varian jual produk (warna, nikotin mg, rasio VG/PG, dll).');
            $table->id()->comment('Primary key varian.');
            $table->unsignedBigInteger('product_id')->comment('FK ke products.id (produk induk).');
            $table->string('sku', 100)->unique()->comment('SKU varian (unik).');
            $table->string('barcode', 100)->nullable()->comment('Barcode/UPC/EAN (opsional).');
            $table->string('name', 191)->comment('Nama varian, contoh: Black 80W; Mango 30mg 30ml.');
            $table->string('color', 64)->nullable()->comment('Warna (untuk device/aksesoris).');
            $table->decimal('capacity_ml', 6, 2)->nullable()->comment('Kapasitas cairan/pod dalam mililiter.');
            $table->enum('nicotine_type', ['freebase', 'salt'])->nullable()->comment('Tipe nikotin untuk liquid; NULL jika bukan liquid.');
            $table->smallInteger('nicotine_mg')->nullable()->comment('Kadar nikotin (mg/mL) untuk liquid.');
            $table->tinyInteger('vg_ratio')->nullable()->comment('Persentase VG (0-100).');
            $table->tinyInteger('pg_ratio')->nullable()->comment('Persentase PG (0-100).');
            $table->decimal('coil_resistance_ohm', 5, 2)->nullable()->comment('Resistansi coil (ohm), relevan untuk coil/heads.');
            $table->integer('puff_count')->nullable()->comment('Perkiraan jumlah hisapan (untuk disposable).');
            $table->json('specs')->nullable()->comment('JSON fleksibel untuk spesifikasi khusus varian.');
            $table->boolean('is_active')->default(true)->comment('Apakah varian aktif untuk dijual.');
            $table->timestamps();
            $table->softDeletes()->comment('Timestamp soft delete varian.');

            $table->foreign('product_id')->references('id')->on('products');
            $table->index(['product_id', 'is_active']);
            $table->index(['nicotine_type', 'nicotine_mg']);
            $table->index(['vg_ratio', 'pg_ratio']);
        });

        // Subtipe: Device
        Schema::create('product_devices', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->comment('Detail subtipe untuk produk device. One-to-one dengan products.type=device.');
            $table->unsignedBigInteger('product_id')->primary()->comment('PK & FK ke products.id.');
            $table->enum('form_factor', ['mod', 'pod_system', 'pod_refillable', 'disposable', 'aio'])->comment('Keluarga/form factor perangkat.');
            $table->enum('battery_type', ['internal', 'external'])->nullable()->comment('Jenis baterai yang digunakan.');
            $table->integer('battery_size_mah')->nullable()->comment('Kapasitas baterai (mAh) jika internal.');
            $table->enum('external_battery_format', ['18650', '20700', '21700'])->nullable()->comment('Ukuran sel baterai eksternal.');
            $table->smallInteger('watt_min')->nullable()->comment('Watt minimum yang didukung.');
            $table->smallInteger('watt_max')->nullable()->comment('Watt maksimum yang didukung.');
            $table->enum('charger_type', ['type-c', 'micro-usb', 'proprietary'])->nullable()->comment('Jenis port pengisian daya.');

            $table->foreign('product_id')->references('id')->on('products');
        });

        // Subtipe: Liquid
        Schema::create('product_liquids', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->comment('Detail subtipe untuk produk liquid. One-to-one dengan products.type=liquid.');
            $table->unsignedBigInteger('product_id')->primary()->comment('PK & FK ke products.id.');
            $table->enum('intended_device', ['mod', 'pod', 'both'])->default('both')->comment('Rekomendasi tipe device untuk liquid ini.');
            $table->enum('flavor_family', ['fruit', 'drink', 'dessert', 'mint_ice', 'tobacco', 'other'])->comment('Keluarga rasa utama.');
            $table->smallInteger('bottle_size_ml')->nullable()->comment('Ukuran botol (fallback bila varian tidak set kapasitas).');

            $table->foreign('product_id')->references('id')->on('products');
        });

        // Subtipe: Accessories
        Schema::create('product_accessories', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->comment('Detail subtipe untuk produk aksesoris. One-to-one dengan products.type=accessory.');
            $table->unsignedBigInteger('product_id')->primary()->comment('PK & FK ke products.id.');
            $table->enum('accessory_type', ['atomizer', 'tank', 'cartridge', 'coil', 'cotton', 'battery', 'charger', 'tools', 'replacement_pod'])->comment('Klasifikasi aksesoris.');
            $table->enum('atomizer_type', ['rda', 'rta', 'rdta', 'tank'])->nullable()->comment('Sub-tipe atomizer bila relevan.');

            $table->foreign('product_id')->references('id')->on('products');
        });

        Schema::create('product_relations', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->comment('Relasi kompatibilitas/rekomendasi antar produk.');
            $table->id()->comment('Primary key relasi produk.');
            $table->unsignedBigInteger('product_id')->comment('FK ke products.id (sumber).');
            $table->unsignedBigInteger('related_product_id')->comment('FK ke products.id (tujuan).');
            $table->enum('relation_type', ['compatible_with', 'uses', 'replacement_for', 'recommended_with'])->comment('Jenis relasi (kompatibel, menggunakan, pengganti, rekomendasi).');
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('related_product_id')->references('id')->on('products');
            $table->index(['product_id', 'relation_type']);
        });

        Schema::create('media', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->comment('Aset media (gambar) milik produk/varian/toko.');
            $table->id()->comment('Primary key media.');
            $table->enum('owner_type', ['product', 'variant', 'shop'])->comment('Tipe entitas pemilik media.');
            $table->unsignedBigInteger('owner_id')->comment('ID entitas pemilik (bergantung owner_type).');
            $table->string('url', 1024)->comment('URL publik dari file media.');
            $table->string('alt', 191)->nullable()->comment('Teks alternatif untuk aksesibilitas.');
            $table->integer('position')->default(0)->comment('Urutan tampilan di antara media sejenis.');
            $table->timestamps();

            $table->index(['owner_type', 'owner_id']);
        });

        /* =============================================================
         * 4) LISTING, HARGA, & STOK
         * ============================================================= */
        Schema::create('vendor_listings', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->comment('Listing jual per toko untuk varian produk (harga, stok, status).');
            $table->id()->comment('Primary key listing.');
            $table->unsignedBigInteger('shop_id')->comment('FK ke shops.id (toko penjual).');
            $table->unsignedBigInteger('product_variant_id')->comment('FK ke product_variants.id (varian yang dijual).');
            $table->enum('condition', ['new', 'refurbished'])->default('new')->comment('Kondisi barang.');
            $table->decimal('price', 18, 2)->comment('Harga jual yang ditetapkan toko.');
            $table->decimal('promo_price', 18, 2)->nullable()->comment('Harga promo (opsional).');
            $table->dateTime('promo_ends_at')->nullable()->comment('Tanggal berakhirnya promo.');
            $table->integer('qty_available')->default(0)->comment('Jumlah stok yang tersedia untuk dijual.');
            $table->integer('min_order_qty')->default(1)->comment('Minimal jumlah pembelian.');
            $table->enum('status', ['active', 'inactive', 'out_of_stock', 'banned'])->default('active')->comment('Status ketersediaan listing.');
            $table->timestamps();
            $table->softDeletes()->comment('Timestamp soft delete listing.');

            $table->unique(['shop_id', 'product_variant_id']);
            $table->foreign('shop_id')->references('id')->on('shops');
            $table->foreign('product_variant_id')->references('id')->on('product_variants');
            $table->index(['shop_id', 'status']);
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->comment('Catatan perubahan stok untuk setiap listing (reserve, kirim, retur, dst).');
            $table->id()->comment('Primary key movement stok.');
            $table->unsignedBigInteger('vendor_listing_id')->comment('FK ke vendor_listings.id yang terdampak.');
            $table->enum('type', ['init', 'purchase_in', 'manual_adjust', 'order_reserve', 'order_release', 'shipment', 'return'])->comment('Jenis pergerakan stok (positif/negatif).');
            $table->integer('qty')->comment('Delta kuantitas (bisa positif/negatif).');
            $table->string('ref_type', 50)->nullable()->comment('Tipe referensi opsional (mis. order_item).');
            $table->unsignedBigInteger('ref_id')->nullable()->comment('ID referensi pendamping ref_type.');
            $table->text('note')->nullable()->comment('Catatan bebas untuk audit.');
            $table->timestamp('created_at')->useCurrent()->comment('Waktu terjadinya movement.');

            $table->foreign('vendor_listing_id')->references('id')->on('vendor_listings');
            $table->index(['vendor_listing_id', 'type']);
        });

        /* =============================================================
         * 5) KERANJANG & CHECKOUT
         * ============================================================= */
        Schema::create('carts', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->comment('Keranjang belanja (berdasarkan customer_id atau session untuk tamu).');
            $table->id()->comment('Primary key cart.');
            $table->unsignedBigInteger('customer_id')->nullable()->comment('FK ke customers.id bila login.');
            $table->string('session_id', 100)->nullable()->comment('Kunci sesi untuk keranjang tamu.');
            $table->timestamps();

            $table->index(['customer_id', 'session_id']);
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->comment('Item baris di keranjang mengarah ke listing vendor.');
            $table->id()->comment('Primary key cart item.');
            $table->unsignedBigInteger('cart_id')->comment('FK ke carts.id.');
            $table->unsignedBigInteger('vendor_listing_id')->comment('FK ke vendor_listings.id yang dipilih.');
            $table->integer('qty')->comment('Jumlah yang diinginkan.');
            $table->decimal('price_snapshot', 18, 2)->comment('Harga saat item dimasukkan (untuk UX; validasi ulang saat checkout).');
            $table->json('variant_snapshot')->nullable()->comment('Snapshot atribut varian (mis. nikotin mg).');
            $table->timestamps();

            $table->foreign('cart_id')->references('id')->on('carts');
            $table->foreign('vendor_listing_id')->references('id')->on('vendor_listings');
            $table->index(['cart_id']);
        });

        /* =============================================================
         * 6) PESANAN (split per toko) & PENGIRIMAN
         * ============================================================= */
        Schema::create('orders', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->comment('Pesanan pelanggan (dapat mencakup banyak toko).');
            $table->id()->comment('Primary key order.');
            $table->unsignedBigInteger('customer_id')->comment('FK ke customers.id (pembeli).');
            $table->string('order_no', 50)->unique()->comment('Nomor pesanan yang ramah manusia (unik).');
            $table->unsignedBigInteger('shipping_address_id')->comment('FK ke addresses.id (alamat pengiriman).');
            $table->unsignedBigInteger('billing_address_id')->nullable()->comment('FK ke addresses.id (alamat penagihan).');
            $table->decimal('subtotal', 18, 2)->comment('Jumlah item sebelum ongkir/diskon/pajak.');
            $table->decimal('shipping_total', 18, 2)->default(0)->comment('Total ongkir gabungan lintas toko.');
            $table->decimal('discount_total', 18, 2)->default(0)->comment('Total diskon gabungan.');
            $table->decimal('tax_total', 18, 2)->default(0)->comment('Total pajak gabungan.');
            $table->decimal('grand_total', 18, 2)->comment('Jumlah akhir yang harus dibayar.');
            $table->enum('payment_method', ['wallet', 'gateway', 'cod'])->comment('Metode pembayaran yang dipilih.');
            $table->enum('payment_status', ['unpaid', 'paid', 'refunded', 'partial_refunded'])->default('unpaid')->comment('Status pembayaran agregat.');
            $table->enum('status', ['pending', 'processing', 'completed', 'cancelled'])->default('pending')->comment('Status siklus hidup pesanan.');
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers');
            $table->foreign('shipping_address_id')->references('id')->on('addresses');
            $table->foreign('billing_address_id')->references('id')->on('addresses');
            $table->index(['customer_id', 'status']);
        });

        Schema::create('order_shops', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->comment('Sub-order per toko dengan pemenuhan & escrow independen.');
            $table->id()->comment('Primary key sub-order toko.');
            $table->unsignedBigInteger('order_id')->comment('FK ke orders.id (induk order).');
            $table->unsignedBigInteger('shop_id')->comment('FK ke shops.id (toko pemenuhan).');
            $table->decimal('subtotal', 18, 2)->comment('Subtotal item untuk toko ini.');
            $table->decimal('shipping_cost', 18, 2)->default(0)->comment('Biaya pengiriman untuk toko ini.');
            $table->decimal('discount_total', 18, 2)->default(0)->comment('Diskon yang diterapkan pada toko ini.');
            $table->decimal('tax_total', 18, 2)->default(0)->comment('Pajak yang dikenakan pada toko ini.');
            $table->decimal('commission_fee', 18, 2)->default(0)->comment('Komisi platform untuk sub-order ini.');
            $table->enum('status', ['awaiting_payment', 'awaiting_fulfillment', 'shipped', 'delivered', 'cancelled', 'refunded'])->default('awaiting_payment')->comment('Status siklus hidup sub-order.');
            $table->unsignedBigInteger('escrow_id')->nullable()->comment('FK ke escrows.id (jika escrow telah dibuat).');
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders');
            $table->foreign('shop_id')->references('id')->on('shops');
            $table->index(['order_id', 'shop_id']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->comment('Snapshot final item yang dibeli pada sub-order tertentu.');
            $table->id()->comment('Primary key order item.');
            $table->unsignedBigInteger('order_shop_id')->comment('FK ke order_shops.id (sub-order terkait).');
            $table->unsignedBigInteger('product_variant_id')->comment('FK ke product_variants.id (varian dibeli).');
            $table->unsignedBigInteger('vendor_listing_id')->comment('FK ke vendor_listings.id (listing pada saat beli).');
            $table->string('name', 191)->comment('Snapshot nama produk/varian.');
            $table->string('sku', 100)->comment('Snapshot SKU varian.');
            $table->integer('qty')->comment('Jumlah yang dibeli.');
            $table->decimal('unit_price', 18, 2)->comment('Harga satuan saat pembelian.');
            $table->decimal('discount_amount', 18, 2)->default(0)->comment('Diskon untuk baris ini.');
            $table->decimal('tax_amount', 18, 2)->default(0)->comment('Pajak untuk baris ini.');
            $table->decimal('total', 18, 2)->comment('Total baris setelah diskon & pajak.');
            $table->json('attributes')->nullable()->comment('Snapshot atribut (mg nikotin, VG/PG, warna, dsb).');
            $table->timestamps();

            $table->foreign('order_shop_id')->references('id')->on('order_shops');
            $table->foreign('product_variant_id')->references('id')->on('product_variants');
            $table->foreign('vendor_listing_id')->references('id')->on('vendor_listings');
            $table->index(['order_shop_id']);
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->comment('Pengiriman keluar untuk setiap sub-order toko.');
            $table->id()->comment('Primary key shipment.');
            $table->unsignedBigInteger('order_shop_id')->comment('FK ke order_shops.id.');
            $table->string('courier_code', 50)->comment('Kode singkat kurir/logistik.');
            $table->string('service_name', 100)->nullable()->comment('Layanan pengiriman, contoh: REG/YES.');
            $table->string('tracking_no', 100)->nullable()->comment('Nomor resi pelacakan dari kurir.');
            $table->timestamp('shipped_at')->nullable()->comment('Waktu barang diserahkan ke kurir.');
            $table->timestamp('delivered_at')->nullable()->comment('Waktu kurir menandai terkirim.');
            $table->enum('status', ['pending', 'shipped', 'delivered', 'returned', 'lost'])->default('pending')->comment('Status pengiriman.');
            $table->timestamps();

            $table->foreign('order_shop_id')->references('id')->on('order_shops');
            $table->index(['order_shop_id', 'status']);
        });

        Schema::create('shipment_items', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->comment('Pemetaan item order yang termasuk dalam satu shipment.');
            $table->unsignedBigInteger('shipment_id')->comment('FK ke shipments.id.');
            $table->unsignedBigInteger('order_item_id')->comment('FK ke order_items.id.');
            $table->integer('qty')->comment('Jumlah item order dalam shipment ini.');

            $table->primary(['shipment_id', 'order_item_id']);
            $table->foreign('shipment_id')->references('id')->on('shipments');
            $table->foreign('order_item_id')->references('id')->on('order_items');
        });

        Schema::create('returns', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->comment('Pengajuan retur/RMA pada level item pesanan.');
            $table->id()->comment('Primary key retur.');
            $table->unsignedBigInteger('order_item_id')->comment('FK ke order_items.id (item yang diretur).');
            $table->text('reason')->nullable()->comment('Alasan retur dari pembeli.');
            $table->enum('status', ['requested', 'approved', 'rejected', 'received', 'refunded'])->default('requested')->comment('Status proses RMA.');
            $table->integer('qty')->comment('Jumlah yang diretur.');
            $table->decimal('amount_requested', 18, 2)->comment('Nominal yang diminta untuk direfund.');
            $table->decimal('amount_refunded', 18, 2)->nullable()->comment('Nominal yang benar-benar direfund saat selesai.');
            $table->timestamps();

            $table->foreign('order_item_id')->references('id')->on('order_items');
            $table->index(['order_item_id', 'status']);
        });

        /* =============================================================
         * 7) PROMO & ULASAN
         * ============================================================= */
        Schema::create('coupons', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->comment('Kode kupon promo, bisa untuk seluruh platform atau per toko.');
            $table->id()->comment('Primary key kupon.');
            $table->string('code', 50)->unique()->comment('Kode kupon (case-insensitive).');
            $table->enum('type', ['percent', 'amount'])->comment('Jenis diskon: persen atau nominal tetap.');
            $table->decimal('value', 18, 2)->comment('Nilai diskon: persen (0-100) atau nominal mata uang.');
            $table->enum('applies_to', ['platform', 'shop'])->default('platform')->comment('Cakupan kupon: seluruh platform atau toko tertentu.');
            $table->unsignedBigInteger('shop_id')->nullable()->comment('FK ke shops.id bila applies_to=shop.');
            $table->decimal('min_order', 18, 2)->nullable()->comment('Minimal nilai pesanan untuk memakai kupon.');
            $table->integer('max_uses')->nullable()->comment('Maksimum jumlah pemakaian kupon.');
            $table->integer('used')->default(0)->comment('Jumlah sudah dipakai.');
            $table->timestamp('starts_at')->nullable()->comment('Mulai masa berlaku kupon.');
            $table->timestamp('ends_at')->nullable()->comment('Akhir masa berlaku kupon.');
            $table->enum('status', ['active', 'inactive'])->default('active')->comment('Status aktif kupon.');
            $table->timestamps();

            $table->foreign('shop_id')->references('id')->on('shops');
            $table->index(['applies_to', 'shop_id', 'status']);
        });

        Schema::create('product_reviews', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->comment('Ulasan produk pasca pembelian oleh pelanggan.');
            $table->id()->comment('Primary key ulasan.');
            $table->unsignedBigInteger('order_item_id')->comment('FK ke order_items.id (menjamin verified purchase).');
            $table->unsignedBigInteger('customer_id')->comment('FK ke customers.id (penulis ulasan).');
            $table->tinyInteger('rating')->comment('Rating bintang 1-5.');
            $table->text('comment')->nullable()->comment('Isi ulasan teks bebas.');
            $table->timestamp('created_at')->useCurrent()->comment('Waktu ulasan dibuat.');

            $table->foreign('order_item_id')->references('id')->on('order_items');
            $table->foreign('customer_id')->references('id')->on('customers');
            $table->index(['customer_id', 'rating']);
        });

        /* =============================================================
         * 8) E-WALLET, LEDGER (double-entry), ESCROW
         * ============================================================= */
        Schema::create('wallet_accounts', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->comment('Rekening dompet elektronik untuk pelanggan, toko, platform, dan escrow.');
            $table->id()->comment('Primary key wallet.');
            $table->enum('owner_type', ['customer', 'shop', 'platform', 'escrow'])->comment('Jenis pemilik rekening.');
            $table->unsignedBigInteger('owner_id')->comment('ID pemilik sesuai owner_type.');
            $table->char('currency', 3)->default('IDR')->comment('Kode mata uang ISO 4217.');
            $table->decimal('balance', 18, 2)->default(0.00)->comment('Saldo terkini (cache; sumber kebenaran = ledger).');
            $table->enum('status', ['active', 'suspended'])->default('active')->comment('Status operasional rekening.');
            $table->timestamps();

            $table->index(['owner_type', 'owner_id']);
        });

        Schema::create('ledger_transactions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->comment('Header transaksi ledger (setiap transaksi berisi debit/kredit seimbang).');
            $table->id()->comment('Primary key transaksi ledger.');
            $table->enum('type', ['topup', 'purchase_hold', 'purchase_capture', 'refund', 'payout', 'withdrawal', 'reversal', 'fee_capture'])->comment('Jenis transaksi tingkat tinggi.');
            $table->enum('status', ['pending', 'posted', 'void'])->default('pending')->comment('Status posting transaksi.');
            $table->string('ref_type', 50)->nullable()->comment('Referensi bisnis opsional (mis. order_shop).');
            $table->unsignedBigInteger('ref_id')->nullable()->comment('ID referensi bisnis berkaitan dengan ref_type.');
            $table->dateTime('occurred_at')->comment('Waktu bisnis terjadinya transaksi.');
            $table->timestamps();

            $table->index(['type', 'status', 'occurred_at']);
        });

        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->comment('Butir posting ledger yang memengaruhi satu rekening (double-entry).');
            $table->id()->comment('Primary key butir entry.');
            $table->unsignedBigInteger('ledger_transaction_id')->comment('FK ke ledger_transactions.id (header transaksi).');
            $table->unsignedBigInteger('account_id')->comment('FK ke wallet_accounts.id (rekening terdampak).');
            $table->enum('direction', ['debit', 'credit'])->comment('Arah posting: debit atau kredit.');
            $table->decimal('amount', 18, 2)->comment('Nominal absolut entry.');
            $table->decimal('balance_after', 18, 2)->nullable()->comment('Snapshot saldo setelah entry (opsional).');
            $table->string('memo', 255)->nullable()->comment('Keterangan singkat yang mudah dibaca.');
            $table->timestamp('created_at')->useCurrent()->comment('Waktu posting entry.');

            $table->foreign('ledger_transaction_id')->references('id')->on('ledger_transactions');
            $table->foreign('account_id')->references('id')->on('wallet_accounts');
            $table->index(['account_id', 'created_at']);
        });

        Schema::create('escrows', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->comment('Rekening escrow per sub-order toko, menahan dana pembeli hingga rilis.');
            $table->id()->comment('Primary key escrow.');
            $table->unsignedBigInteger('order_shop_id')->comment('FK ke order_shops.id (pemilik escrow).');
            $table->unsignedBigInteger('wallet_account_id')->comment('FK ke wallet_accounts.id (owner_type=escrow).');
            $table->decimal('amount_held', 18, 2)->default(0.00)->comment('Nominal yang sedang ditahan.');
            $table->enum('status', ['held', 'released', 'refunded', 'partial_released'])->default('held')->comment('Status dana escrow.');
            $table->timestamps();

            $table->foreign('order_shop_id')->references('id')->on('order_shops');
            $table->foreign('wallet_account_id')->references('id')->on('wallet_accounts');
            $table->unique(['order_shop_id', 'wallet_account_id']);
        });

        Schema::create('topups', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->comment('Top-up saldo dompet oleh pelanggan melalui gateway pembayaran.');
            $table->id()->comment('Primary key topup.');
            $table->unsignedBigInteger('customer_id')->comment('FK ke customers.id (pelaku top-up).');
            $table->decimal('amount', 18, 2)->comment('Nominal top-up dalam mata uang wallet.');
            $table->enum('gateway', ['va', 'qris', 'cc', 'bank_transfer'])->comment('Metode pembayaran yang digunakan.');
            $table->string('gateway_ref', 191)->nullable()->comment('Referensi/ID transaksi dari gateway.');
            $table->enum('status', ['pending', 'paid', 'failed'])->default('pending')->comment('Status pemrosesan top-up.');
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers');
            $table->index(['customer_id', 'status']);
        });

        Schema::create('withdrawals', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->comment('Penarikan dana dari dompet toko ke rekening bank.');
            $table->id()->comment('Primary key withdrawal.');
            $table->unsignedBigInteger('wallet_account_id')->comment('FK ke wallet_accounts.id (dompet toko).');
            $table->decimal('amount', 18, 2)->comment('Nominal yang ditarik.');
            $table->string('bank_code', 20)->comment('Kode bank tujuan.');
            $table->string('account_number', 64)->comment('Nomor rekening tujuan.');
            $table->string('account_name', 191)->comment('Nama pemilik rekening tujuan.');
            $table->enum('status', ['requested', 'processing', 'paid', 'rejected'])->default('requested')->comment('Status proses penarikan.');
            $table->timestamps();

            $table->foreign('wallet_account_id')->references('id')->on('wallet_accounts');
            $table->index(['wallet_account_id', 'status']);
        });

        Schema::create('payouts', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->comment('Pembayaran hasil penjualan dari escrow ke toko/platform saat capture.');
            $table->id()->comment('Primary key payout.');
            $table->unsignedBigInteger('order_shop_id')->comment('FK ke order_shops.id (sumber dana).');
            $table->unsignedBigInteger('wallet_account_id')->comment('FK ke wallet_accounts.id (dompet toko penerima).');
            $table->decimal('gross_amount', 18, 2)->comment('Nominal capture kotor untuk sub-order ini.');
            $table->decimal('fee_platform', 18, 2)->default(0.00)->comment('Komisi platform yang dipotong.');
            $table->decimal('net_amount', 18, 2)->comment('Nominal bersih yang diterima toko setelah biaya.');
            $table->enum('status', ['scheduled', 'paid', 'failed'])->default('scheduled')->comment('Status eksekusi payout.');
            $table->timestamp('paid_at')->nullable()->comment('Waktu ditandai sebagai dibayar.');
            $table->timestamps();

            $table->foreign('order_shop_id')->references('id')->on('order_shops');
            $table->foreign('wallet_account_id')->references('id')->on('wallet_accounts');
            $table->index(['order_shop_id', 'status']);
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->comment('Catatan refund (dapat per sub-order toko atau per item).');
            $table->id()->comment('Primary key refund.');
            $table->unsignedBigInteger('order_shop_id')->nullable()->comment('FK ke order_shops.id (refund level toko).');
            $table->unsignedBigInteger('order_item_id')->nullable()->comment('FK ke order_items.id (refund level item).');
            $table->decimal('amount', 18, 2)->comment('Nominal refund ke pelanggan.');
            $table->text('reason')->nullable()->comment('Alasan refund.');
            $table->enum('status', ['pending', 'approved', 'rejected', 'paid'])->default('pending')->comment('Status pemrosesan refund.');
            $table->timestamps();

            $table->foreign('order_shop_id')->references('id')->on('order_shops');
            $table->foreign('order_item_id')->references('id')->on('order_items');
            $table->index(['order_shop_id', 'order_item_id', 'status']);
        });

        /* =============================================================
         * 9) AGE-GATE & RESTRIKSI LEGAL
         * ============================================================= */
        Schema::create('age_checks', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->comment('Audit pengecekan usia yang dilakukan pada pelanggan.');
            $table->id()->comment('Primary key age-check.');
            $table->unsignedBigInteger('customer_id')->comment('FK ke customers.id (subjek cek).');
            $table->enum('method', ['kyc', 'selfie_liveness', 'manual'])->comment('Metode verifikasi yang digunakan.');
            $table->enum('result', ['pass', 'fail'])->comment('Hasil pengecekan usia.');
            $table->timestamp('checked_at')->useCurrent()->comment('Waktu pengecekan dilakukan.');

            $table->foreign('customer_id')->references('id')->on('customers');
            $table->index(['customer_id', 'result']);
        });

        Schema::create('product_restrictions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->comment('Batasan geografis/hukum untuk penjualan produk tertentu.');
            $table->id()->comment('Primary key restriksi.');
            $table->unsignedBigInteger('product_id')->comment('FK ke products.id.');
            $table->string('country_code', 2)->default('ID')->comment('Kode negara ISO 3166-1 alpha-2.');
            $table->string('state', 120)->nullable()->comment('Provinsi/region yang dibatasi.');
            $table->string('city', 120)->nullable()->comment('Kota yang dibatasi.');
            $table->tinyInteger('min_age')->default(18)->comment('Usia minimal untuk pembelian.');
            $table->boolean('is_banned')->default(false)->comment('Jika true, produk dilarang di wilayah ini.');
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products');
            $table->index(['product_id', 'country_code', 'state', 'city']);
        });
    }

    public function down(): void
    {
        // Urutan drop terbalik mengikuti ketergantungan FK
        Schema::dropIfExists('product_restrictions');
        Schema::dropIfExists('age_checks');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payouts');
        Schema::dropIfExists('withdrawals');
        Schema::dropIfExists('topups');
        Schema::dropIfExists('escrows');
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('ledger_transactions');
        Schema::dropIfExists('wallet_accounts');
        Schema::dropIfExists('product_reviews');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('returns');
        Schema::dropIfExists('shipment_items');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('order_shops');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('vendor_listings');
        Schema::dropIfExists('media');
        Schema::dropIfExists('product_relations');
        Schema::dropIfExists('product_accessories');
        Schema::dropIfExists('product_liquids');
        Schema::dropIfExists('product_devices');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('shops');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('kyc_profiles');
        Schema::dropIfExists('customers');
    }
};

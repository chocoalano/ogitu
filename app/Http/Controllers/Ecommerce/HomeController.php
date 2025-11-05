<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Medium;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Models\VendorListing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $recommended = $this->buildRecommendedItems();
        $hero = $this->buildHero($recommended);
        $about = $this->buildAbout();

        return view('pages.home', compact('hero', 'about', 'recommended'));
    }

    /* =========================
     * DATA BUILDER — HERO
     * ========================= */

    protected function buildHero(array $recommended): array
    {
        // Badge = 2 kategori dengan produk terbanyak
        $badges = Cache::remember('home.badges', 600, function () {
            $cats = Category::query()
                ->withCount('products')                 // asumsi relasi Category->products (hasMany by primary_category_id)
                ->orderByDesc('products_count')
                ->limit(2)
                ->get(['id', 'name']);

            return $cats->map(
                fn ($c) => ['label' => '#'.Str::of($c->name)->slug('-')->replace('-', ' ')->title()]
            )->values()->all();
        });

        // Rating global dari ulasan produk
        [$avgScore, $countLabel] = Cache::remember('home.site_rating', 600, function () {
            $avg = (float) (ProductReview::query()->avg('rating') ?? 0);
            $cnt = (int) (ProductReview::query()->count() ?? 0);

            return [round($avg ?: 4.7, 1), $this->formatCountLabel($cnt)];
        });

        // Hero image: ambil media milik 'shop' (posisi paling depan) atau fallback
        $heroImg = Cache::remember('home.hero_img', 600, function () {
            return Medium::query()
                ->where('owner_type', 'shop')
                ->orderBy('position')
                ->orderByDesc('id')
                ->value('url')
                ?: 'https://res.cloudinary.com/dqta7pszj/image/upload/v1740024751/rclmcqugizwgji0550x4.png';
        });

        // Brand icon kecil (pakai media 'shop' juga atau fallback)
        $brandIcon = Cache::remember('home.brand_icon', 600, function () {
            return Medium::query()
                ->where('owner_type', 'shop')
                ->orderBy('position')
                ->orderByDesc('id')
                ->value('url')
                ?: 'https://res.cloudinary.com/dqta7pszj/image/upload/v1740025626/t3sek3wmopczltto6a9i.svg';
        });

        // Review card: review terbaru (fallback statis jika kosong)
        $reviewCard = Cache::remember('home.review_card', 600, function () {
            $r = ProductReview::query()
                ->with(['customer:id,name', 'product:id,name'])
                ->latest('id')
                ->first();

            if (! $r) {
                return [
                    'avatar' => asset('assets/images/avatar1-25906796.png'),
                    'name' => 'Pelanggan Ogitu',
                    'text' => 'Device original, kirimnya cepat. Mantap!',
                    'score' => 5,
                ];
            }

            return [
                'avatar' => asset('assets/images/avatar1-25906796.png'), // ganti jika kamu simpan avatar di media
                'name' => $r->customer?->name ?? 'Pelanggan',
                'text' => Str::limit($r->content ?? 'Kualitas mantap & pengiriman cepat.', 80),
                'score' => (int) ($r->rating ?? 5),
            ];
        });

        // Chip kecil di hero: pakai item pertama recommended jika ada
        $first = $recommended[0] ?? null;
        $chip = [
            'icon' => $first['image'] ?? $brandIcon,
            'title' => $first['title'] ?? 'Liquid Favorit',
            'score' => (int) ($first['rating'] ?? 5),
            'price' => (int) ($first['price'] ?? 135000),
            'currency' => 'Rp',
        ];

        return [
            'badges' => ! empty($badges) ? $badges : [
                ['label' => '#LiquidPremium'],
                ['label' => '#BelanjaAman'],
            ],
            'title' => [
                'prefix' => 'Toko Resmi',
                'highlight' => 'Rokok Elektrik & Liquid Premium',
                'suffix' => 'dengan garansi kualitas Ogitu.',
            ],
            'desc' => 'Belanja tanpa ragu: semua device & liquid kami terseleksi, bergaransi toko, dan siap kirim cepat ke seluruh Indonesia.',
            'buttons' => [
                ['type' => 'primary', 'label' => 'Belanja Sekarang', 'url' => url('products')],
                ['type' => 'video',   'label' => 'Cara belanja di Ogitu', 'url' => 'javascript:void(0)'],
            ],
            'avatars' => [
                asset('assets/images/avatar1-25906796.png'),
                asset('assets/images/avatar2-189b0d01.png'),
                asset('assets/images/avatar3-2bbdc0fd.png'),
            ],
            'rating' => ['score' => $avgScore, 'count_label' => $countLabel],
            'hero_img' => $heroImg,
            'review_card' => $reviewCard,
            'chip' => $chip,
            'brand_icon' => $brandIcon,
            'notice' => 'Produk ini mengandung nikotin. Hanya untuk 21+. Mohon gunakan secara bertanggung jawab.',
        ];
    }

    /* =========================
     * DATA BUILDER — ABOUT
     * ========================= */

    protected function buildAbout(): array
    {
        // Ambil satu ikon dari media 'shop' (kalau ada) atau fallback
        $aboutIcon = Cache::remember('home.about_icon', 600, function () {
            return Medium::query()
                ->where('owner_type', 'shop')
                ->orderBy('position')
                ->orderByDesc('id')
                ->value('url')
                ?: 'https://res.cloudinary.com/dqta7pszj/image/upload/v1740024751/rclmcqugizwgji0550x4.png';
        });

        return [
            'badge' => 'Tentang Ogitu',
            'title' => 'Solusi lengkap rokok elektrik & liquid premium untuk pengalaman vaping yang konsisten.',
            'desc' => 'Ogitu.com menghadirkan kurasi device resmi, pilihan liquid terfavorit, dan layanan purna jual yang responsif—membuat pengalaman belanja Anda lebih mudah, aman, dan memuaskan.',
            'image' => $aboutIcon,
            'features' => [
                [
                    'icon' => asset('assets/liquids.svg'),
                    'title' => 'Pilihan Liquid Lengkap',
                    'desc' => 'Saltnic & freebase dari brand tepercaya—rasa jelas, throat hit pas.',
                ],
                [
                    'icon' => asset('assets/vape-kit.svg'),
                    'title' => 'Device Resmi & Bergaransi',
                    'desc' => 'Pod kit, mod, cartridge, coil—semuanya original dengan garansi toko.',
                ],
                [
                    'icon' => asset('assets/shipping.svg'),
                    'title' => 'Pengiriman Cepat',
                    'desc' => 'Order diproses di hari yang sama. Tersedia opsi ekspedisi kilat.',
                ],
            ],
            'cta' => ['label' => 'Jelajahi Katalog', 'url' => url('products')],
            'ceo' => ['avatar' => asset('assets/images/avatar3-2bbdc0fd.png'), 'name' => 'Tim Ogitu', 'role' => 'Customer Success'],
        ];
    }

    /* =========================
     * DATA BUILDER — REKOMENDASI
     * ========================= */

    protected function buildRecommendedItems(): array
    {
        $listings = Cache::remember('home.recommended.listings', 300, function () {
            return VendorListing::query()
                ->select('vendor_listings.*')
                ->join('product_variants', 'product_variants.id', '=', 'vendor_listings.product_variant_id')
                ->join('products', 'products.id', '=', 'product_variants.product_id')
                ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
                ->where('vendor_listings.status', 'active')
                ->where('vendor_listings.qty_available', '>', 0)
                ->where('product_variants.is_active', true)
                ->where('products.is_active', true)
                ->orderByDesc('vendor_listings.promo_price')
                ->orderBy('vendor_listings.price')
                ->limit(12)
                ->with([
                    'product_variant:id,product_id,name,sku,is_active',
                    'product_variant.product:id,brand_id,primary_category_id,type,name,slug,is_active,is_age_restricted',
                    'product_variant.product.brand:id,name',
                    'product_variant.product.brand.shop:id,name,slug',
                ])
                ->get();
        });

        $items = [];
        foreach ($listings as $listing) {
            $variant = $listing->product_variant;
            $product = $variant?->product;
            if (! $product) {
                continue;
            }

            $image = $this->getProductImageUrl($product)
                ?: 'https://res.cloudinary.com/dqta7pszj/image/upload/v1740025626/t3sek3wmopczltto6a9i.svg';

            $rating = Cache::remember(
                "product.{$product->id}.rating_avg",
                600,
                fn () => round((float) ($product->reviews()->avg('rating') ?? 4.6), 1)
            );

            $items[] = [
                'title' => $product->name ?? ($variant->name ?? 'Produk'),
                'image' => $image,
                'rating' => $rating,
                'price' => (int) ($listing->promo_price ?: $listing->price),
                'url' => $this->productUrl($product),
                'shop_name' => $listing->shop?->name,
            ];

            if (count($items) >= 6) {
                break;
            } // sesuai layout 6 kartu
        }

        if (empty($items)) {
            $items = [[
                'title' => 'Ogitu Nova Pod Kit',
                'image' => 'https://res.cloudinary.com/dqta7pszj/image/upload/v1740025626/t3sek3wmopczltto6a9i.svg',
                'rating' => 4.9,
                'price' => 399000,
                'url' => url('catalog/ogitu-nova-pod-kit'),
                'shop_name' => 'Ogitu Official Store',
            ]];
        }

        return $items;
    }

    /* =========================
     * HELPERS
     * ========================= */

    protected function formatCountLabel(int $count): string
    {
        if ($count >= 1_000_000) {
            return number_format($count / 1_000_000, 1).'M Ulasan';
        }
        if ($count >= 1_000) {
            return number_format($count / 1_000, 1).'k Ulasan';
        }

        return number_format($count).' Ulasan';
    }

    protected function mediaUrl(?Medium $m): ?string
    {
        return $m?->url;
    }

    protected function getProductImageUrl(Product $product): ?string
    {
        // Ambil thumbnail produk berdasar urutan 'position' (lihat relasi di model)
        $m = $product->thumbnail ?? $product->media()->first();

        return $m?->url;
    }

    protected function getVariantImageUrl(ProductVariant $variant): ?string
    {
        $m = $variant->thumbnail ?? $variant->media()->first();

        // fallback: media produk induk
        if (! $m) {
            $m = $variant->product?->thumbnail ?? $variant->product?->media()->first();
        }

        return $m?->url;
    }

    protected function productUrl(Product $product): string
    {
        return route('products.detail', ['slug' => $product->slug]);
    }
}

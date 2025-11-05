<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Medium;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\VendorListing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    /**
     * Katalog produk dengan filter & sorting.
     */
    public function index(Request $request)
    {
        // =========================
        // Ambil & normalisasi filter dari query
        // =========================
        $categoryInput = $request->query('category'); // slug / array / "a,b,c"
        $brandInput = $request->query('brand');    // slug / array / "a,b,c"
        $search = trim((string) $request->query('search', ''));
        $sort = (string) $request->query('sort', 'newest');
        $priceStr = (string) ($request->query('price') ?? $request->query('radio') ?? ''); // "min-max" | "all" | ""

        $categorySlugs = $this->toArrayParam($categoryInput);
        $brandSlugs = $this->toArrayParam($brandInput);

        [$minPrice, $maxPrice] = $this->parsePriceRange($priceStr) ?? [null, null];

        // Ubah kategori slug → id (termasuk descendant)
        $categoryIds = $this->expandCategoryIdsFromSlugs($categorySlugs);

        // Brand slug → id
        $brandIds = $this->mapBrandSlugsToIds($brandSlugs);

        // =========================
        // Query utama (VendorListing → ProductVariant → Product)
        // =========================
        $listings = VendorListing::query()
            ->select([
                'vendor_listings.id',
                'vendor_listings.product_variant_id',
                'vendor_listings.price',
                'vendor_listings.promo_price',
                'vendor_listings.qty_available',
                'vendor_listings.status',
            ])
            ->addSelect([
                'effective_price' => \DB::raw('COALESCE(vendor_listings.promo_price, vendor_listings.price)'),
            ])
            ->join('product_variants', 'product_variants.id', '=', 'vendor_listings.product_variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->when($this->hasColumn('products', 'is_active'), fn (Builder $q) => $q->where('products.is_active', true))
            ->when($this->hasColumn('product_variants', 'is_active'), fn (Builder $q) => $q->where('product_variants.is_active', true))
            ->where('vendor_listings.status', 'active')
            ->where('vendor_listings.qty_available', '>', 0);

        // Filter: kategori (berdasar primary_category_id + descendant)
        if (! empty($categoryIds)) {
            $listings->whereIn('products.primary_category_id', $categoryIds);
        }

        // Filter: brand
        if (! empty($brandIds)) {
            $listings->whereIn('products.brand_id', $brandIds);
        }

        // Filter: search (nama produk/brand)
        if ($search !== '') {
            $listings->where(function (Builder $q) use ($search) {
                $q->where('products.name', 'like', '%'.$search.'%')
                    ->orWhere('brands.name', 'like', '%'.$search.'%');
            });
        }

        // Filter: price (Coalesce)
        if ($minPrice !== null && $maxPrice !== null) {
            $listings->whereBetween(\DB::raw('COALESCE(vendor_listings.promo_price, vendor_listings.price)'), [$minPrice, $maxPrice]);
        }

        // =========================
        // Subselect: rating rata2 per product
        // Rantai join yang benar:
        // product_reviews -> order_items -> order_shops -> orders
        // =========================
        $ratingSub = ProductReview::query()
            ->selectRaw('ROUND(AVG(product_reviews.rating), 1)')
            ->join('order_items', 'order_items.id', '=', 'product_reviews.order_item_id')
            ->join('product_variants as rv', 'rv.id', '=', 'order_items.product_variant_id')
            ->join('products as rp', 'rp.id', '=', 'rv.product_id')
            ->leftJoin('order_shops', 'order_shops.id', '=', 'order_items.order_shop_id')   // ✅ perbaikan
            ->leftJoin('orders', 'orders.id', '=', 'order_shops.order_id')                 // ✅ perbaikan
            ->whereColumn('rp.id', 'products.id')
            // Anggap review valid untuk pesanan selesai/terkirim/terbayar:
            ->where(function (Builder $q) {
                if ($this->hasColumn('orders', 'status')) {
                    $q->orWhere('orders.status', 'completed');           // orders.status: pending|processing|completed|cancelled
                }
                if ($this->hasColumn('orders', 'payment_status')) {
                    $q->orWhere('orders.payment_status', 'paid');        // orders.payment_status: unpaid|paid|refunded|partial_refunded
                }
                if ($this->hasColumn('order_shops', 'status')) {
                    $q->orWhere('order_shops.status', 'delivered');      // order_shops.status mencakup 'delivered'
                }
            });

        $listings->addSelect([
            'rating_avg' => $ratingSub->limit(1),
        ]);

        // Sorting
        switch ($sort) {
            case 'price_asc':
                $listings->orderBy('effective_price', 'asc');
                break;
            case 'price_desc':
                $listings->orderBy('effective_price', 'desc');
                break;
            case 'rating_desc':
                $listings->orderBy('rating_avg', 'desc')->orderBy('products.id', 'desc');
                break;
            case 'best_seller':
                if ($this->hasColumn('products', 'total_sold')) {
                    $listings->orderBy('products.total_sold', 'desc');
                } else {
                    $listings->orderByRaw('vendor_listings.promo_price IS NULL')->orderBy('vendor_listings.qty_available', 'asc');
                }
                break;
            case 'newest':
            default:
                $listings->orderBy('vendor_listings.id', 'desc');
                break;
        }

        // Eager-load relasi dengan aman (shop/store opsional)
        $with = [
            'product_variant:id,product_id,name,sku,is_active',
            'product_variant.product:id,brand_id,primary_category_id,name,slug,is_active',
            'product_variant.product.brand:id,name,slug',
        ];
        // Jika model punya relasi shop(), muat itu; kalau tidak dan punya store(), muat store()
        $listingModel = new VendorListing;
        if (method_exists($listingModel, 'shop')) {
            $with[] = 'shop:id,name';
        } elseif (method_exists($listingModel, 'store')) {
            $with[] = 'store:id,name';
        }

        // Paginate
        $perPage = (int) max(1, min(48, (int) $request->query('per_page', 24)));
        $paginator = $listings
            ->with($with)
            ->paginate($perPage)
            ->withQueryString();

        // Map ke struktur yang dipakai Blade (array sederhana)
        $productsForView = $paginator->getCollection()->map(function (VendorListing $row) {
            $variant = $row->product_variant;
            $product = $variant?->product;

            $img = $this->productImageUrl($product) ?: $this->brandLogoUrl($product?->brand) ?: 'https://placehold.co/600x600?text=OGITU';

            $priceNum = (int) ($row->promo_price ?? $row->price);
            $rating = (float) ($row->rating_avg ?? 4.6);

            // store/shop name (fallback "Official")
            $storeName = 'Official';
            if (method_exists($row, 'store')) {
                $storeName = $row->store?->name ?: 'Official';
            } elseif (method_exists($row, 'shop')) {
                $storeName = $row->shop?->name ?: 'Official';
            }

            return [
                'title' => $product?->name ?? ($variant?->name ?? 'Produk'),
                'rating' => number_format($rating, 1),
                'price' => $this->idr($priceNum),
                'image' => $img,
                'url' => $this->productUrl($product),
                'slug' => $product?->slug,
                'store_name' => $storeName,
            ];
        })->values()->all();

        // =========================
        // Sidebar data (kategori/brand/price)
        // =========================
        $sidebarCategories = $this->buildSidebarCategories($categorySlugs);
        $sidebarPrice = $this->buildSidebarPriceRanges($priceStr);
        $sidebarBrands = $this->buildSidebarPopularBrands($brandSlugs, $categoryIds);

        return view('pages.product.catalog', [
            'categories' => $sidebarCategories,
            'priceRanges' => $sidebarPrice,
            'popularBrands' => $sidebarBrands,
            'products' => $productsForView,
            'totalProducts' => $paginator->total(),
            'pagination' => $paginator,
            'activeFilters' => [
                'category' => $categorySlugs,
                'brand' => $brandSlugs,
                'price' => $priceStr,
                'search' => $search,
                'sort' => $sort,
            ],
        ]);
    }

    /**
     * Halaman detail produk.
     */
    public function show(string $slug)
    {
        $product = Product::query()
            ->where('slug', $slug)
            ->when($this->hasColumn('products', 'is_active'), fn ($q) => $q->where('is_active', true))
            ->with([
                'brand:id,name,slug',
                'category:id,name,slug',
                'product_variants:id,product_id,name,sku,is_active',
                'product_variants.vendor_listings' => fn ($q) => $q->where('status', 'active')->where('qty_available', '>', 0),
            ])->firstOrFail();

        // Avg rating via reviews -> order_items -> order_shops -> orders
        $avgRating = ProductReview::query()
            ->join('order_items', 'order_items.id', '=', 'product_reviews.order_item_id')
            ->join('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->leftJoin('order_shops', 'order_shops.id', '=', 'order_items.order_shop_id') // ✅ perbaikan
            ->leftJoin('orders', 'orders.id', '=', 'order_shops.order_id')               // ✅ perbaikan
            ->where('products.id', $product->id)
            ->where(function (Builder $q) {
                if ($this->hasColumn('orders', 'status')) {
                    $q->orWhere('orders.status', 'completed');
                }
                if ($this->hasColumn('orders', 'payment_status')) {
                    $q->orWhere('orders.payment_status', 'paid');
                }
                if ($this->hasColumn('order_shops', 'status')) {
                    $q->orWhere('order_shops.status', 'delivered');
                }
            })
            ->avg('product_reviews.rating');

        // dd($product->toArray());
        return view('pages.product.product-detail', [
            'product' => $product,
            'avgRating' => round((float) $avgRating ?: 4.7, 1),
            'image' => $this->productImageUrl($product) ?: $this->brandLogoUrl($product->brand),
        ]);
    }

    // =====================================================================
    // ============================= HELPERS ===============================
    // =====================================================================

    protected function toArrayParam($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value));
        }
        if (is_string($value) && $value !== '') {
            return array_values(array_filter(array_map('trim', explode(',', $value))));
        }

        return [];
    }

    protected function parsePriceRange(?string $price): ?array
    {
        if (! $price || $price === 'all') {
            return null;
        }
        if (preg_match('/^(\d+)-(\d+)$/', $price, $m)) {
            $a = (int) $m[1];
            $b = (int) $m[2];
            if ($a > $b) {
                [$a, $b] = [$b, $a];
            }

            return [$a, $b];
        }

        return null;
    }

    protected function expandCategoryIdsFromSlugs(array $slugs): array
    {
        if (empty($slugs)) {
            return [];
        }
        $ids = Category::query()->whereIn('slug', $slugs)->pluck('id')->all();
        $all = [];
        foreach ($ids as $id) {
            $all = array_merge($all, $this->descendantsOf($id, includeSelf: true));
        }

        return array_values(array_unique($all));
    }

    protected function descendantsOf(int $parentId, bool $includeSelf = false): array
    {
        $collected = $includeSelf ? [$parentId] : [];
        $frontier = [$parentId];
        while (! empty($frontier)) {
            $children = Category::query()
                ->whereIn('parent_id', $frontier)
                ->when($this->hasColumn('categories', 'is_active'), fn ($q) => $q->where('is_active', true))
                ->pluck('id')->all();

            $children = array_diff($children, $collected);
            if (empty($children)) {
                break;
            }
            $collected = array_merge($collected, $children);
            $frontier = $children;
        }

        return $collected;
    }

    protected function mapBrandSlugsToIds(array $slugs): array
    {
        if (empty($slugs)) {
            return [];
        }

        return Brand::query()->whereIn('slug', $slugs)->pluck('id')->all();
    }

    protected function productUrl(?Product $p): string
    {
        if (! $p) {
            return url('products');
        }
        if (app('router')->has('products.detail')) {
            return route('products.detail', $p->slug);
        }
        if (app('router')->has('products.show')) {
            return route('products.detail', $p->slug);
        }

        return url('products/'.$p->slug);
    }

    protected function productImageUrl(?Product $product): ?string
    {
        try {
            if (! $product) {
                return null;
            }

            foreach (['thumbnail_url', 'image_url', 'cover_url'] as $prop) {
                if (isset($product->$prop) && $product->$prop) {
                    return $product->$prop;
                }
            }

            if (method_exists($product, 'thumbnail') && ($m = $product->thumbnail)) {
                foreach (['url', 'file_url', 'path'] as $p) {
                    if (! empty($m->$p)) {
                        return $m->$p;
                    }
                }
            }

            if (method_exists($product, 'media')) {
                $m = $product->media()->where('collection', 'product_thumbnail')->latest('id')->first();
                if ($m) {
                    foreach (['url', 'file_url', 'path'] as $p) {
                        if (! empty($m->$p)) {
                            return $m->$p;
                        }
                    }
                }
            }

            $m = Medium::query()
                ->where('collection', 'product_thumbnail')
                ->where(function ($q) use ($product) {
                    $q->where('model_id', $product->id)->orWhere('product_id', $product->id);
                })
                ->latest('id')->first();

            if ($m) {
                foreach (['url', 'file_url', 'path'] as $p) {
                    if (! empty($m->$p)) {
                        return $m->$p;
                    }
                }
            }

        } catch (\Throwable $e) {
            // ignore
        }

        return null;
    }

    protected function brandLogoUrl(?Brand $brand): ?string
    {
        if (! $brand) {
            return null;
        }

        foreach (['logo_url', 'image_url'] as $p) {
            if (! empty($brand->$p)) {
                return $brand->$p;
            }
        }

        try {
            if (method_exists($brand, 'media')) {
                $m = $brand->media()->where('collection', 'brand_logo')->latest('id')->first();
                if ($m) {
                    foreach (['url', 'file_url', 'path'] as $p) {
                        if (! empty($m->$p)) {
                            return $m->$p;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
        }

        try {
            $m = Medium::query()
                ->where('collection', 'brand_logo')
                ->where(function ($q) use ($brand) {
                    $q->where('model_id', $brand->id)->orWhere('brand_id', $brand->id);
                })->latest('id')->first();
            if ($m) {
                foreach (['url', 'file_url', 'path'] as $p) {
                    if (! empty($m->$p)) {
                        return $m->$p;
                    }
                }
            }
        } catch (\Throwable $e) {
        }

        return null;
    }

    protected function idr(int|float $n): string
    {
        return 'Rp. '.number_format($n, 0, ',', '.');
    }

    protected function hasColumn(string $table, string $column): bool
    {
        try {
            return \Schema::hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }

    // =========================
    // Sidebar builders
    // =========================
    protected function buildSidebarCategories(array $selectedSlugs): array
    {
        $cats = Cache::remember('catalog.sidebar.categories', 600, function () {
            $q = Category::query()->whereNull('parent_id')->orderBy('name');
            if ($this->hasColumn('categories', 'is_active')) {
                $q->where('is_active', true);
            }

            return $q->get(['id', 'name', 'slug']);
        });

        $arr = [[
            'id' => 'all',
            'label' => 'Semua Kategori',
            'checked' => empty($selectedSlugs),
        ]];

        foreach ($cats as $c) {
            $arr[] = [
                'id' => $c->slug ?: 'cat-'.$c->id,
                'label' => $c->name,
                'checked' => in_array($c->slug, $selectedSlugs, true),
            ];
        }

        return $arr;
    }

    protected function buildSidebarPriceRanges(string $active)
    {
        $ranges = [
            ['id' => 'all_price',  'label' => 'Semua Harga',                 'value' => 'all'],
            ['id' => 'under_100k', 'label' => 'Dibawah Rp100.000',           'value' => '0-100000'],
            ['id' => '100k_250k',  'label' => 'Rp100.000 - Rp250.000',       'value' => '100000-250000'],
            ['id' => '250k_500k',  'label' => 'Rp250.000 - Rp500.000',       'value' => '250000-500000'],
            ['id' => '500k_1jt',   'label' => 'Rp500.000 - Rp1.000.000',     'value' => '500000-1000000'],
            ['id' => 'over_1jt',   'label' => 'Diatas Rp1.000.000',          'value' => '1000000-10000000'],
        ];
        $active = $active !== '' ? $active : '250000-500000';

        return array_map(function ($r) use ($active) {
            $r['checked'] = ($r['value'] === $active);

            return $r;
        }, $ranges);
    }

    protected function buildSidebarPopularBrands(array $selectedBrandSlugs, array $withinCategoryIds = []): array
    {
        $brands = Brand::query()
            ->select('brands.id', 'brands.name', 'brands.slug')
            ->join('products', 'products.brand_id', '=', 'brands.id')
            ->when(! empty($withinCategoryIds), fn ($q) => $q->whereIn('products.primary_category_id', $withinCategoryIds))
            ->when($this->hasColumn('products', 'is_active'), fn ($q) => $q->where('products.is_active', true))
            ->groupBy('brands.id', 'brands.name', 'brands.slug')
            ->orderBy('brands.name')
            ->limit(20)
            ->get();

        $out = [];
        $half = (int) ceil(max(1, $brands->count()) / 2);
        foreach ($brands as $i => $b) {
            $out[] = [
                'id' => $b->slug ?: 'brand-'.$b->id,
                'label' => $b->name,
                'checked' => in_array($b->slug, $selectedBrandSlugs, true),
                'col' => ($i < $half) ? 1 : 2,
            ];
        }

        if (empty($out)) {
            $out = [
                ['id' => 'voopoo', 'label' => 'Voopoo', 'checked' => in_array('voopoo', $selectedBrandSlugs, true), 'col' => 1],
                ['id' => 'smok', 'label' => 'SMOK', 'checked' => in_array('smok', $selectedBrandSlugs, true), 'col' => 1],
                ['id' => 'upods', 'label' => 'Upods', 'checked' => in_array('upods', $selectedBrandSlugs, true), 'col' => 1],
                ['id' => 'exo', 'label' => 'Exo Liquid', 'checked' => in_array('exo', $selectedBrandSlugs, true), 'col' => 2],
                ['id' => 'hexohm', 'label' => 'HexOhm', 'checked' => in_array('hexohm', $selectedBrandSlugs, true), 'col' => 2],
                ['id' => 'rincoe', 'label' => 'Rincoe', 'checked' => in_array('rincoe',$selectedBrandSlugs,true), 'col' => 2],
            ];
        }

        return $out;
    }
}

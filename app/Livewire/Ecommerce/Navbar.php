<?php

namespace App\Livewire\Ecommerce;

use App\Models\Brand;
use App\Models\Cart;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

class Navbar extends Component
{
    public array $branding = [];

    public array $topbar = [];

    public array $dropdownProduk = [];

    public array $mainMenu = [];

    public array $mobileBottom = [];

    public array $mobileSidebar = [];

    public int $cartCount = 0;

    public array $cartData = [];

    public string $search = '';

    protected $listeners = ['cart.updated' => 'refreshCartCount'];

    public function mount(
        ?array $branding = null,
        ?array $topbar = null,
        ?array $topbarLinks = null,
        ?int $cartCount = null,
    ): void {
        // Branding & Topbar
        $this->branding = $branding ?? [
            'home' => url('/'),
            'logo_dark' => asset('assets/images/logo-dark-6dbab3e1.png'),
            'logo_light' => asset('assets/images/logo-light-35c89c2c.png'),
            'alt' => 'Logo Ogitu.com Marketplace Vape',
        ];

        $this->topbar = $topbar ?? [
            'messages' => [
                ['text' => 'Dapatkan sensasi rasa terbaru untuk setiap harinya.'],
                ['text' => 'Gratis Ongkir untuk pembelian di atas Rp300.000', 'cta' => ['label' => 'Klaim Sekarang', 'url' => 'javascript:void(0)']],
            ],
            'links' => $topbarLinks ?? [
                ['label' => 'Bantuan/FAQ', 'url' => route('pages.show', ['slug' => 'faq'])],
                ['label' => 'Hubungi Kami', 'url' => route('pages.show', ['slug' => 'pusat-bantuan'])],
            ],
        ];

        // DB → Dropdown & Mega menus (SEMUA kategori top-level: Liquid, Device, Accessories, dll)
        $this->dropdownProduk = $this->buildDropdownProduk();
        $megaMenus = $this->buildMegaMenus(); // ← dinamis per root kategori

        // Susun mainMenu: Link Beranda, Dropdown Produk, seluruh Mega per kategori, lalu Artikel
        $this->mainMenu = array_merge(
            [
                ['type' => 'link', 'label' => 'Beranda', 'url' => url('/')],
                ['type' => 'dropdown', 'label' => 'Produk', 'items' => $this->dropdownProduk],
            ],
            $megaMenus,
            [
                ['type' => 'link', 'label' => 'Artikel', 'url' => route('articles.index')],
            ]
        );

        // Bottom nav (mobile)
        $this->mobileBottom = [
            ['label' => 'Beranda',      'url' => url('/'),        'icon' => 'fa-solid fa-house'],
            ['label' => 'Semua Produk', 'url' => url('products'), 'icon' => 'fa-solid fa-store'],
            ['label' => 'Keranjang',    'url' => url('cart'),     'icon' => 'fa-solid fa-shopping-cart'],
            ['label' => 'Akun',         'url' => url('profile'),  'icon' => 'fa-regular fa-user'],
        ];

        // Sidebar mobile dari top-level kategori
        $this->mobileSidebar = $this->buildMobileSidebar();

        // Cart - get from database
        $this->cartCount = $cartCount ?? $this->getCartCount();
        $this->cartData = $this->getCartData();
    }

    #[On('cart.updated')]
    public function refreshCartCount(?int $count = null): void
    {
        $this->cartCount = $count ?? $this->getCartCount();
        $this->cartData = $this->getCartData();
    }

    public function removeFromCart(int $cartItemId): void
    {
        $customerId = auth('customer')->id();

        if ($customerId) {
            // Logged in user - get by customer_id
            $cart = Cart::where('customer_id', $customerId)->first();
        } else {
            // Guest user - get by session_id
            $sessionId = Session::getId();
            $cart = Cart::where('session_id', $sessionId)->first();
        }

        if (! $cart) {
            return;
        }

        $cartItem = $cart->cart_items()->where('id', $cartItemId)->first();
        if ($cartItem) {
            $cartItem->delete();
        }

        // Update cart count and data
        $this->cartCount = $this->getCartCount();
        $this->cartData = $this->getCartData();
    }

    /**
     * Get cart count from database based on current user/session
     */
    protected function getCartCount(): int
    {
        $customerId = auth('customer')->id();

        if ($customerId) {
            // Logged in user - get by customer_id
            $cart = Cart::where('customer_id', $customerId)->first();
        } else {
            // Guest user - get by session_id
            $sessionId = Session::getId();
            $cart = Cart::where('session_id', $sessionId)->first();
        }

        if (! $cart) {
            return 0;
        }

        // Sum all quantities from cart items
        return (int) $cart->cart_items()->sum('qty');
    }

    /**
     * Get cart count from database based on current user/session
     */
    protected function getCartData(): array
    {
        $customerId = auth('customer')->id();

        if ($customerId) {
            // Logged in user - get by customer_id
            $cart = Cart::with('cart_items')->where('customer_id', $customerId)->first();
        } else {
            // Guest user - get by session_id
            $sessionId = Session::getId();
            $cart = Cart::with('cart_items')->where('session_id', $sessionId)->first();
        }

        if (! $cart) {
            return [];
        }

        // Sum all quantities from cart items
        return (array) $cart->toArray();
    }

    /* =========================
     |  BUILDERS – MENU & MEGA
     * =========================*/

    protected function buildDropdownProduk(): array
    {
        $cats = Cache::remember('nav.dropdownProduk', 600, function () {
            $q = Category::query();
            if ($this->hasColumn('categories', 'is_active')) {
                $q->where('is_active', true);
            }

            return $q->whereNull('parent_id')
                ->orderBy('name')
                ->limit(6)
                ->get(['id', 'name', 'slug', 'parent_id']);
        });

        if ($cats->isEmpty()) {
            return [
                ['label' => 'Liquid',            'url' => url('products?category=liquid')],
                ['label' => 'Device & Aksesori', 'url' => url('products?category=device')],
            ];
        }

        return $cats->map(fn ($c) => [
            'label' => $c->name,
            'url' => $this->categoryUrl($c),
        ])->values()->all();
    }

    /**
     * Bangun mega-menu untuk SEMUA kategori root (parent_id = null):
     * contoh: Liquid, Device, Accessories, Pods, dll.
     */
    protected function buildMegaMenus(): array
    {
        $roots = Cache::remember('nav.mega.roots', 600, function () {
            $q = Category::query()->whereNull('parent_id')->orderBy('name');
            if ($this->hasColumn('categories', 'is_active')) {
                $q->where('is_active', true);
            }

            return $q->get(['id', 'name', 'slug']);
        });

        if ($roots->isEmpty()) {
            return [];
        }

        $menus = [];
        foreach ($roots as $root) {
            $tabs = $this->buildTabsForRoot($root);
            if (! empty($tabs)) {
                $menus[] = [
                    'type' => 'mega',
                    'label' => $root->name, // ← jadi “Liquid”, “Device & Aksesori”, dst.
                    'tabs' => $tabs,
                ];
            }
        }

        return $menus;
    }

    /**
     * Untuk sebuah root kategori, tab = anak-anaknya.
     * Tiap tab punya kolom Kategori (cucu) + kolom Brand (semua brand pada anak+cucu).
     */
    protected function buildTabsForRoot(Category $root): array
    {
        // Ambil anak (level 1) sebagai tab
        $children = Cache::remember("nav.mega.children.{$root->id}", 600, function () use ($root) {
            $q = Category::query()->where('parent_id', $root->id)->orderBy('name');
            if ($this->hasColumn('categories', 'is_active')) {
                $q->where('is_active', true);
            }

            return $q->get(['id', 'name', 'slug']);
        });

        if ($children->isEmpty()) {
            // Jika root tanpa anak, jadikan 1 tab yang menunjuk dirinya sendiri
            $children = collect([$root]);
        }

        $tabs = [];
        foreach ($children as $child) {
            // Kumpulkan cucu (kategori level 2) untuk kolom kategori
            $grand = Cache::remember("nav.mega.grand.{$child->id}", 600, function () use ($child) {
                $q = Category::query()->where('parent_id', $child->id)->orderBy('name');
                if ($this->hasColumn('categories', 'is_active')) {
                    $q->where('is_active', true);
                }

                return $q->get(['id', 'name', 'slug']);
            });

            // Siapkan kolom Kategori
            $cols = [];
            if ($grand->isEmpty()) {
                $cols[] = [
                    'title' => 'Kategori',
                    'links' => [['label' => $child->name, 'qs' => 'category='.$child->slug]],
                ];
                $categoryIds = [$child->id];
            } else {
                $chunks = $grand->chunk(10)->take(3);
                foreach ($chunks as $chunk) {
                    $cols[] = [
                        'title' => 'Kategori',
                        'links' => $chunk->map(fn ($g) => [
                            'label' => $g->name,
                            'qs' => 'category='.$g->slug,
                        ])->values()->all(),
                    ];
                }
                $categoryIds = array_merge([$child->id], $grand->pluck('id')->all());
            }

            // Ambil SEMUA descendant id (anak, cucu, dst) untuk brand (agar tidak ketinggalan level lebih dalam)
            $descIds = $this->getDescendantIds($child->id, includeSelf: true);
            if (! empty($categoryIds)) {
                $descIds = array_unique(array_merge($descIds, $categoryIds));
            }

            // Kolom Brand (SEMUA brand yang punya produk pada kumpulan kategori tsb)
            $brands = $this->getBrandsForCategories($descIds);
            if ($brands->isNotEmpty()) {
                $brandChunks = $brands->chunk(12); // semua ditampilkan, dipecah per 12 item/kolom
                foreach ($brandChunks as $bchunk) {
                    $cols[] = [
                        'title' => 'Brand',
                        'links' => $bchunk->map(function ($b) use ($child) {
                            return [
                                'label' => $b->name,
                                'qs' => http_build_query([
                                    'category' => $child->slug,                  // tetap berada di tab child
                                    'brand' => $b->slug ?: Str::slug($b->name),
                                ]),
                            ];
                        })->values()->all(),
                    ];
                }
            }

            $key = $child->slug ?: ('cat-'.$child->id);
            $tabs[$key] = [
                'title' => $child->name,
                'columns' => $cols,
            ];
        }

        return $tabs;
    }

    /**
     * Ambil semua brand yang memiliki produk aktif pada kumpulan kategori (by products.primary_category_id).
     */
    protected function getBrandsForCategories(array $categoryIds)
    {
        if (empty($categoryIds)) {
            return collect();
        }

        sort($categoryIds);
        $cacheKey = 'nav.brands.forCats.'.md5(json_encode($categoryIds));

        return Cache::remember($cacheKey, 600, function () use ($categoryIds) {
            $q = Brand::query()
                ->select('brands.id', 'brands.name', 'brands.slug')
                ->join('products', 'products.brand_id', '=', 'brands.id')
                ->whereIn('products.primary_category_id', $categoryIds)
                ->when($this->hasColumn('products', 'is_active'), fn ($qq) => $qq->where('products.is_active', true))
                ->groupBy('brands.id', 'brands.name', 'brands.slug')
                ->orderBy('brands.name', 'asc');

            return $q->get();
        });
    }

    /**
     * Ambil seluruh descendant category id (BFS) mulai dari parent tertentu.
     */
    protected function getDescendantIds(int $parentId, bool $includeSelf = true): array
    {
        $collected = $includeSelf ? [$parentId] : [];
        $frontier = [$parentId];

        while (! empty($frontier)) {
            $children = Category::query()
                ->whereIn('parent_id', $frontier)
                ->when($this->hasColumn('categories', 'is_active'), fn ($q) => $q->where('is_active', true))
                ->pluck('id')
                ->all();

            $children = array_diff($children, $collected);
            if (empty($children)) {
                break;
            }

            $collected = array_merge($collected, $children);
            $frontier = $children;
        }

        return array_values(array_unique($collected));
    }

    /**
     * Sidebar mobile: tampilkan semua top-level kategori sebagai accordion.
     */
    protected function buildMobileSidebar(): array
    {
        $tops = Cache::remember('nav.mobile.tops', 600, function () {
            $q = Category::query()->whereNull('parent_id')->orderBy('name');
            if ($this->hasColumn('categories', 'is_active')) {
                $q->where('is_active', true);
            }

            return $q->get(['id', 'name', 'slug']);
        });

        $rows = [];
        foreach ($tops as $top) {
            $children = Category::query()
                ->where('parent_id', $top->id)
                ->when($this->hasColumn('categories', 'is_active'), fn ($q) => $q->where('is_active', true))
                ->orderBy('name')
                ->get(['id', 'name', 'slug']);

            if ($children->isEmpty()) {
                $rows[] = [
                    'type' => 'link',
                    'label' => $top->name,
                    'url' => $this->categoryUrl($top),
                ];
            } else {
                $rows[] = [
                    'type' => 'accordion',
                    'id' => 'cat-'.$top->slug ?: $top->id,
                    'label' => $top->name,
                    'items' => $children->map(fn ($c) => [
                        'label' => $c->name,
                        'url' => $this->categoryUrl($c),
                    ])->values()->all(),
                ];
            }
        }

        $rows[] = ['type' => 'link', 'label' => 'Blog & Review', 'url' => url('blog')];

        return $rows;
    }

    /* =========================
     |         HELPERS
     * =========================*/

    protected function categoryUrl(Category $c): string
    {
        if (app('router')->has('products.list')) {
            return route('products.list', ['category' => $c->slug]);
        }
        if (app('router')->has('products.index')) {
            return route('products.list', ['category' => $c->slug]);
        }

        return url('products?category='.$c->slug);
    }

    protected function hasColumn(string $table, string $column): bool
    {
        try {
            return \Schema::hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function searchProducts(): void
    {
        if (empty(trim($this->search))) {
            return;
        }

        // Gunakan redirect biasa tanpa navigate untuk menghindari blank page issue
        $this->redirect(route('products.list', ['search' => $this->search]), navigate: false);
    }

    public function render()
    {
        return view('livewire.ecommerce.navbar');
    }
}

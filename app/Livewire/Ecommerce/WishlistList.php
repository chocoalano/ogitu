<?php

namespace App\Livewire\Ecommerce;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
class WishlistList extends Component
{
    public ?Wishlist $wishlist = null;

    public function mount(): void
    {
        // Check authentication
        if (! Auth::guard('customer')->check()) {
            $this->redirect('/login', navigate: true);

            return;
        }

        $this->loadWishlist();
    }

    public function loadWishlist(): void
    {
        $customerId = Auth::guard('customer')->id();

        // Get or create wishlist
        $this->wishlist = Wishlist::firstOrCreate(
            ['customer_id' => $customerId]
        );
    }

    #[Computed]
    public function wishlistItems()
    {
        if (! $this->wishlist) {
            return collect();
        }

        return $this->wishlist->wishlist_items()
            ->with([
                'vendor_listing.product_variant.product.brand',
                'vendor_listing.product_variant.product.media',
                'vendor_listing.shop',
            ])
            ->get();
    }

    public function addToCart(int $wishlistItemId): void
    {
        try {
            $wishlistItem = WishlistItem::with('vendor_listing')->findOrFail($wishlistItemId);
            $customerId = Auth::guard('customer')->id();
            $sessionId = Session::getId();

            // Get or create cart
            $cart = Cart::firstOrCreate(
                Auth::guard('customer')->check()
                    ? ['customer_id' => $customerId]
                    : ['session_id' => $sessionId],
                ['customer_id' => $customerId, 'session_id' => $sessionId]
            );

            // Check if item already in cart
            $existingCartItem = CartItem::where('cart_id', $cart->id)
                ->where('vendor_listing_id', $wishlistItem->vendor_listing_id)
                ->first();

            if ($existingCartItem) {
                // Increment quantity
                $existingCartItem->increment('qty');
                $this->dispatch('cart-success', message: 'Jumlah produk di keranjang ditambah!');
            } else {
                // Add new item to cart
                CartItem::create([
                    'cart_id' => $cart->id,
                    'vendor_listing_id' => $wishlistItem->vendor_listing_id,
                    'qty' => 1,
                    'price_snapshot' => $wishlistItem->vendor_listing->price,
                ]);
                $this->dispatch('cart-success', message: 'Produk berhasil ditambahkan ke keranjang!');
            }

            // Dispatch cart updated event
            $this->dispatch('cart.updated');

            // Optionally remove from wishlist after adding to cart
            // $this->removeFromWishlist($wishlistItemId);

        } catch (\Exception $e) {
            $this->dispatch('cart-error', message: 'Gagal menambahkan produk ke keranjang');
        }
    }

    public function removeFromWishlist(int $wishlistItemId): void
    {
        try {
            $wishlistItem = WishlistItem::where('id', $wishlistItemId)
                ->where('wishlist_id', $this->wishlist->id)
                ->firstOrFail();

            $wishlistItem->delete();

            $this->dispatch('wishlist-success', message: 'Produk berhasil dihapus dari wishlist');

            // Refresh computed property
            unset($this->wishlistItems);

        } catch (\Exception $e) {
            $this->dispatch('wishlist-error', message: 'Gagal menghapus produk dari wishlist');
        }
    }

    #[On('wishlist.updated')]
    public function refreshWishlist(): void
    {
        $this->loadWishlist();
        unset($this->wishlistItems);
    }

    public function render()
    {
        return view('livewire.ecommerce.wishlist-list');
    }
}

<?php

namespace App\Livewire\Ecommerce;

use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class AddToWishlist extends Component
{
    #[Reactive]
    public int $vendorListingId;

    public bool $isInWishlist = false;

    public function mount(): void
    {
        $this->checkWishlistStatus();
    }

    public function checkWishlistStatus(): void
    {
        if (! Auth::guard('customer')->check()) {
            $this->isInWishlist = false;

            return;
        }

        $customerId = Auth::guard('customer')->id();
        $wishlist = Wishlist::where('customer_id', $customerId)->first();

        if (! $wishlist) {
            $this->isInWishlist = false;

            return;
        }

        $this->isInWishlist = WishlistItem::where('wishlist_id', $wishlist->id)
            ->where('vendor_listing_id', $this->vendorListingId)
            ->exists();
    }

    public function toggleWishlist(): void
    {
        if (! Auth::guard('customer')->check()) {
            $this->dispatch('wishlist-error', message: 'Silakan login terlebih dahulu');
            $this->redirect('/login', navigate: true);

            return;
        }

        try {
            $customerId = Auth::guard('customer')->id();

            // Get or create wishlist
            $wishlist = Wishlist::firstOrCreate(
                ['customer_id' => $customerId]
            );

            // Check if item exists
            $wishlistItem = WishlistItem::where('wishlist_id', $wishlist->id)
                ->where('vendor_listing_id', $this->vendorListingId)
                ->first();

            if ($wishlistItem) {
                // Remove from wishlist
                $wishlistItem->delete();
                $this->isInWishlist = false;
                $this->dispatch('wishlist-success', message: 'Produk dihapus dari wishlist');
            } else {
                // Add to wishlist
                WishlistItem::create([
                    'wishlist_id' => $wishlist->id,
                    'vendor_listing_id' => $this->vendorListingId,
                ]);
                $this->isInWishlist = true;
                $this->dispatch('wishlist-success', message: 'Produk ditambahkan ke wishlist');
            }

            // Dispatch event to refresh wishlist page if open
            $this->dispatch('wishlist.updated');

        } catch (\Exception $e) {
            $this->dispatch('wishlist-error', message: 'Terjadi kesalahan');
        }
    }

    public function render()
    {
        return view('livewire.ecommerce.add-to-wishlist');
    }
}

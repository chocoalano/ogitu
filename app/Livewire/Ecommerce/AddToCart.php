<?php

namespace App\Livewire\Ecommerce;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\VendorListing;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class AddToCart extends Component
{
    public ?int $variantId = null;

    public int $qty = 1;

    public array $variantOptions = [];

    public ?int $selectedListingId = null;

    public function mount(?int $variantId = null, array $variantOptions = []): void
    {
        $this->variantOptions = $variantOptions;

        // Set default variant if provided
        if ($variantId) {
            $this->variantId = $variantId;
            $this->findListingForVariant();
        } elseif (! empty($variantOptions)) {
            // Use first variant as default
            $firstVariant = $variantOptions[0] ?? null;
            if ($firstVariant && isset($firstVariant['id'])) {
                $this->variantId = $firstVariant['id'];
                $this->findListingForVariant();
            }
        }
    }

    public function updatedVariantId(): void
    {
        $this->findListingForVariant();
    }

    public function increment(): void
    {
        $this->qty++;
    }

    public function decrement(): void
    {
        if ($this->qty > 1) {
            $this->qty--;
        }
    }

    public function addToCart(): void
    {
        try {
            if (! $this->selectedListingId) {
                $this->dispatch('cart-error', message: 'Silakan pilih varian produk terlebih dahulu.');

                return;
            }

            $listing = VendorListing::with(['product_variant', 'product_variant.product'])
                ->find($this->selectedListingId);

            if (! $listing) {
                $this->dispatch('cart-error', message: 'Produk tidak ditemukan.');

                return;
            }

            if ($listing->status !== 'active' || $listing->qty_available < $this->qty) {
                $this->dispatch('cart-error', message: 'Produk tidak tersedia atau stok tidak cukup.');

                return;
            }

            // Get or create cart
            $cart = $this->getOrCreateCart();

            // Check if item already exists in cart
            $cartItem = CartItem::query()
                ->where('cart_id', $cart->id)
                ->where('vendor_listing_id', $listing->id)
                ->first();

            $variant = $listing->product_variant;
            $product = $variant->product ?? null;

            if ($cartItem) {
                // Update quantity
                $newQty = $cartItem->qty + $this->qty;
                if ($newQty > $listing->qty_available) {
                    $this->dispatch('cart-error', message: 'Jumlah melebihi stok yang tersedia.');

                    return;
                }
                $cartItem->update(['qty' => $newQty]);
            } else {
                // Create new cart item
                CartItem::create([
                    'cart_id' => $cart->id,
                    'vendor_listing_id' => $listing->id,
                    'qty' => $this->qty,
                    'price_snapshot' => $listing->promo_price ?? $listing->price,
                    'variant_snapshot' => [
                        'product_id' => $product?->id,
                        'product_name' => $product?->name,
                        'variant_id' => $variant->id,
                        'variant_name' => $variant->name,
                        'sku' => $variant->sku,
                    ],
                ]);
            }

            // Get updated cart count from database
            $totalItems = (int) $cart->cart_items()->sum('qty');

            // Dispatch event to update navbar
            $this->dispatch('cart.updated', count: $totalItems);

            // Show success notification
            $this->dispatch('cart-success', message: 'Produk berhasil ditambahkan ke keranjang!');

            // Reset quantity
            $this->qty = 1;
        } catch (\Exception $e) {
            // Log error and show user-friendly message
            \Log::error('Add to cart error: '.$e->getMessage(), [
                'listing_id' => $this->selectedListingId,
                'qty' => $this->qty,
            ]);

            $this->dispatch('cart-error', message: 'Terjadi kesalahan saat menambahkan ke keranjang. Silakan coba lagi.');
        }
    }

    protected function findListingForVariant(): void
    {
        if (! $this->variantId) {
            $this->selectedListingId = null;

            return;
        }

        // Find active listing for this variant with available stock
        $listing = VendorListing::query()
            ->where('product_variant_id', $this->variantId)
            ->where('status', 'active')
            ->where('qty_available', '>', 0)
            ->orderBy('price')
            ->first();

        $this->selectedListingId = $listing?->id;
    }

    protected function getOrCreateCart(): Cart
    {
        $customerId = Auth::guard('customer')->id();
        $sessionId = Session::getId();

        if ($customerId) {
            // Logged in user - check if there's a guest cart to merge
            $customerCart = Cart::where('customer_id', $customerId)->first();
            $guestCart = Cart::where('session_id', $sessionId)
                ->whereNull('customer_id')
                ->first();

            if ($customerCart && $guestCart) {
                // Merge guest cart into customer cart
                foreach ($guestCart->cart_items as $guestItem) {
                    $existingItem = $customerCart->cart_items()
                        ->where('vendor_listing_id', $guestItem->vendor_listing_id)
                        ->first();

                    if ($existingItem) {
                        $existingItem->increment('qty', $guestItem->qty);
                    } else {
                        $guestItem->update(['cart_id' => $customerCart->id]);
                    }
                }

                $guestCart->delete();

                return $customerCart;
            } elseif ($guestCart) {
                // Convert guest cart to customer cart
                $guestCart->update([
                    'customer_id' => $customerId,
                    'session_id' => null,
                ]);

                return $guestCart;
            }

            // No guest cart, just create/return customer cart
            return Cart::firstOrCreate(
                ['customer_id' => $customerId],
                ['session_id' => null]
            );
        }

        // Guest user
        return Cart::firstOrCreate(
            ['session_id' => $sessionId],
            ['customer_id' => null]
        );
    }

    public function render()
    {
        return view('livewire.ecommerce.add-to-cart');
    }
}

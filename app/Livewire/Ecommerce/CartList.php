<?php

namespace App\Livewire\Ecommerce;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CartList extends Component
{
    public ?Cart $cart = null;

    public string $couponCode = '';

    public ?Coupon $appliedCoupon = null;

    public function mount(): void
    {
        $this->loadCart();
    }

    #[Computed]
    public function cartItems()
    {
        if (! $this->cart) {
            return collect();
        }

        return $this->cart->cart_items()
            ->with([
                'vendor_listing.product_variant.product.brand',
                'vendor_listing.product_variant.product.media',
                'vendor_listing.shop',
            ])
            ->get();
    }

    public function updateQuantity(int $cartItemId, int $qty): void
    {
        try {
            if ($qty < 1) {
                $this->dispatch('cart-error', message: 'Jumlah minimal adalah 1');

                return;
            }

            $cartItem = CartItem::where('id', $cartItemId)
                ->where('cart_id', $this->cart->id)
                ->first();

            if (! $cartItem) {
                $this->dispatch('cart-error', message: 'Item tidak ditemukan');

                return;
            }

            $listing = $cartItem->vendor_listing;

            if (! $listing || $listing->status !== 'active') {
                $this->dispatch('cart-error', message: 'Produk tidak tersedia');

                return;
            }

            if ($qty > $listing->qty_available) {
                $this->dispatch('cart-error', message: "Stok hanya tersedia {$listing->qty_available} unit");

                return;
            }

            $cartItem->update(['qty' => $qty]);

            // Update navbar cart count
            $this->dispatchCartUpdate();

            // Clear computed property cache
            unset($this->cartItems);

            $this->dispatch('cart-success', message: 'Keranjang berhasil diperbarui');
        } catch (\Exception $e) {
            \Log::error('Update quantity error: '.$e->getMessage(), [
                'cart_item_id' => $cartItemId,
                'qty' => $qty,
            ]);

            $this->dispatch('cart-error', message: 'Terjadi kesalahan saat memperbarui keranjang');
        }
    }

    public function removeItem(int $cartItemId): void
    {
        try {
            $cartItem = CartItem::where('id', $cartItemId)
                ->where('cart_id', $this->cart->id)
                ->first();

            if (! $cartItem) {
                $this->dispatch('cart-error', message: 'Item tidak ditemukan');

                return;
            }

            $cartItem->delete();

            // Update navbar cart count
            $this->dispatchCartUpdate();

            // Clear computed property cache
            unset($this->cartItems);

            // Reload cart
            $this->loadCart();

            $this->dispatch('cart-success', message: 'Item berhasil dihapus dari keranjang');
        } catch (\Exception $e) {
            \Log::error('Remove item error: '.$e->getMessage(), [
                'cart_item_id' => $cartItemId,
            ]);

            $this->dispatch('cart-error', message: 'Terjadi kesalahan saat menghapus item');
        }
    }

    public function applyCoupon(): void
    {
        try {
            if (empty($this->couponCode)) {
                $this->dispatch('cart-error', message: 'Silakan masukkan kode kupon');

                return;
            }

            $coupon = Coupon::where('code', strtoupper($this->couponCode))
                ->where('status', 'active')
                ->where(function ($q) {
                    $q->whereNull('starts_at')
                        ->orWhere('starts_at', '<=', now());
                })
                ->where(function ($q) {
                    $q->whereNull('ends_at')
                        ->orWhere('ends_at', '>=', now());
                })
                ->where(function ($q) {
                    $q->whereNull('max_uses')
                        ->orWhereRaw('used < max_uses');
                })
                ->first();

            if (! $coupon) {
                $this->dispatch('cart-error', message: 'Kode kupon tidak valid atau sudah kadaluarsa');

                return;
            }

            // Check if coupon can be applied based on minimum order
            if ($coupon->min_order && $this->getSubtotal() < $coupon->min_order) {
                $minOrder = 'Rp '.number_format($coupon->min_order, 0, ',', '.');
                $this->dispatch('cart-error', message: "Minimal pembelian {$minOrder} untuk menggunakan kupon ini");

                return;
            }

            $this->appliedCoupon = $coupon;

            $this->dispatch('cart-success', message: 'Kupon berhasil diterapkan!');
        } catch (\Exception $e) {
            \Log::error('Apply coupon error: '.$e->getMessage(), [
                'coupon_code' => $this->couponCode,
            ]);

            $this->dispatch('cart-error', message: 'Terjadi kesalahan saat menerapkan kupon');
        }
    }

    public function removeCoupon(): void
    {
        $this->appliedCoupon = null;
        $this->couponCode = '';
        $this->dispatch('cart-success', message: 'Kupon telah dihapus');
    }

    protected function loadCart(): void
    {
        $customerId = Auth::guard('customer')->id();

        if ($customerId) {
            $this->cart = Cart::where('customer_id', $customerId)->first();
        } else {
            $sessionId = Session::getId();
            $this->cart = Cart::where('session_id', $sessionId)->first();
        }
    }

    protected function dispatchCartUpdate(): void
    {
        if (! $this->cart) {
            $this->dispatch('cart.updated', count: 0);

            return;
        }

        $totalItems = (int) $this->cart->cart_items()->sum('qty');
        $this->dispatch('cart.updated', count: $totalItems);
    }

    protected function getSubtotal(): float
    {
        return $this->cartItems->sum(function ($item) {
            return $item->price_snapshot * $item->qty;
        });
    }

    protected function getDiscount(): float
    {
        if (! $this->appliedCoupon) {
            return 0;
        }

        $subtotal = $this->getSubtotal();

        if ($this->appliedCoupon->type === 'percentage') {
            $discount = ($subtotal * $this->appliedCoupon->value) / 100;

            return $discount;
        }

        // Fixed amount discount
        return min($this->appliedCoupon->value, $subtotal);
    }

    protected function getTax(): float
    {
        // Tax calculation: 11% PPN
        $subtotal = $this->getSubtotal();
        $discount = $this->getDiscount();

        return ($subtotal - $discount) * 0.11;
    }

    protected function getTotal(): float
    {
        $subtotal = $this->getSubtotal();
        $discount = $this->getDiscount();
        $tax = $this->getTax();

        return $subtotal - $discount + $tax;
    }

    public function render()
    {
        return view('livewire.ecommerce.cart-list', [
            'subtotal' => $this->getSubtotal(),
            'discount' => $this->getDiscount(),
            'tax' => $this->getTax(),
            'total' => $this->getTotal(),
        ]);
    }
}

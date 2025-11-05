<?php

namespace App\Livewire\Ecommerce;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\Escrow;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderShop;
use App\Models\Topup;
use App\Models\WalletAccount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;

#[Layout('layouts.app')]
class Checkout extends Component
{
    // Shipping Information
    #[Rule('required|string|max:150')]
    public string $recipient_name = '';

    #[Rule('nullable|string|max:20')]
    public ?string $recipient_phone = '';

    #[Rule('required|string|in:individual,company')]
    public string $recipient_type = 'individual';

    #[Rule('required|string|max:500')]
    public string $address_line1 = '';

    #[Rule('nullable|string|max:500')]
    public ?string $address_line2 = '';

    #[Rule('required|string|max:100')]
    public string $city = '';

    #[Rule('nullable|string|max:100')]
    public ?string $state = '';

    #[Rule('nullable|string|max:20')]
    public ?string $postal_code = '';

    #[Rule('required|string|max:2')]
    public string $country_code = 'ID';

    // Shipping Options
    public ?string $courier_code = null;

    public ?string $service_name = null;

    public float $shipping_cost = 0;

    // Additional Information
    public ?string $notes = '';

    // Payment
    #[Rule('required|string|in:wallet,gateway,cod')]
    public string $payment_method = 'wallet';

    public ?Cart $cart = null;

    public ?Customer $customer = null;

    public ?WalletAccount $wallet = null;

    // Topup
    public ?float $topup_amount = null;

    public bool $showTopupModal = false;

    public function mount(): void
    {
        // Check authentication
        if (! Auth::guard('customer')->check()) {
            $this->redirect('/login', navigate: true);

            return;
        }

        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();
        $this->customer = $customer;
        $this->loadCart();
        $this->loadWallet();
        $this->loadDefaultAddress();

        // Redirect if cart is empty
        if (! $this->cart || $this->cartItems->isEmpty()) {
            $this->dispatch('cart-error', message: 'Keranjang Anda kosong');
            $this->redirect('/carts', navigate: true);
        }
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

    #[Computed]
    public function groupedByShop()
    {
        return $this->cartItems->groupBy(function ($item) {
            return $item->vendor_listing->shop_id;
        });
    }

    public function processCheckout(): void
    {
        $this->validate();

        if ($this->cartItems->isEmpty()) {
            $this->dispatch('checkout-error', message: 'Keranjang Anda kosong');

            return;
        }

        // Validate stock availability
        foreach ($this->cartItems as $item) {
            $listing = $item->vendor_listing;
            if ($item->qty > $listing->qty_available) {
                $this->dispatch('checkout-error', message: "Stok {$listing->product_variant->product->name} tidak mencukupi");

                return;
            }
        }

        // Check wallet balance if using wallet payment
        if ($this->payment_method === 'wallet') {
            $total = $this->getGrandTotal();
            if (! $this->wallet || $this->wallet->balance < $total) {
                $this->dispatch('checkout-error', message: 'Saldo wallet Anda tidak mencukupi. Silakan topup terlebih dahulu.');

                return;
            }
        }

        try {
            DB::beginTransaction();

            // Create or get shipping address
            $shippingAddress = $this->createOrUpdateAddress();

            // Create Order
            $order = $this->createOrder($shippingAddress);

            // Process payment
            if ($this->payment_method === 'wallet') {
                $this->processWalletPayment($order);

                // Clear cart after successful wallet payment
                $this->cart->cart_items()->delete();
                $this->cart->delete();

                DB::commit();

                $this->dispatch('checkout-success', message: 'Pesanan berhasil dibuat! Terima kasih atas pesanan Anda.');
                $this->redirect(route('auth.orders.detail', ['order_no' => $order->order_no]), navigate: true);
            } elseif ($this->payment_method === 'gateway') {
                // Don't clear cart yet for gateway payment
                // Cart will be cleared after payment confirmation

                DB::commit();

                // Generate Midtrans payment URL
                $midtransUrl = $this->generateMidtransPayment($order);

                // Redirect to Midtrans payment page
                $this->js("window.location.href = '{$midtransUrl}'");
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Checkout error: '.$e->getMessage(), [
                'customer_id' => $this->customer->id,
                'trace' => $e->getTraceAsString(),
            ]);

            $this->dispatch('checkout-error', message: 'Terjadi kesalahan saat memproses pesanan. Silakan coba lagi.');
        }
    }

    protected function createOrder(Address $shippingAddress): Order
    {
        // Generate order number
        $orderNo = 'ORD-'.date('Ymd').'-'.strtoupper(substr(uniqid(), -6));

        // Calculate totals
        $subtotal = $this->getSubtotal();
        $shippingTotal = $this->shipping_cost;
        $discountTotal = 0; // TODO: Apply coupon discount
        $taxTotal = $this->getTax();
        $grandTotal = $subtotal + $shippingTotal - $discountTotal + $taxTotal;

        // Create main order
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no' => $orderNo,
            'shipping_address_id' => $shippingAddress->id,
            'billing_address_id' => $shippingAddress->id,
            'subtotal' => $subtotal,
            'shipping_total' => $shippingTotal,
            'discount_total' => $discountTotal,
            'tax_total' => $taxTotal,
            'grand_total' => $grandTotal,
            'payment_method' => $this->payment_method,
            'payment_status' => 'unpaid',
            'status' => 'pending',
        ]);

        // Create order shops and items
        foreach ($this->groupedByShop as $shopId => $items) {
            $shop = $items->first()->vendor_listing->shop;

            // Calculate shop totals
            $shopSubtotal = $items->sum(function ($item) {
                return $item->price_snapshot * $item->qty;
            });

            $shopTax = $shopSubtotal * 0.11; // 11% PPN
            $commissionFee = $shopSubtotal * 0.05; // 5% platform fee

            // Create OrderShop
            $orderShop = OrderShop::create([
                'order_id' => $order->id,
                'shop_id' => $shopId,
                'subtotal' => $shopSubtotal,
                'shipping_cost' => 0, // TODO: Split shipping cost per shop
                'discount_total' => 0,
                'tax_total' => $shopTax,
                'commission_fee' => $commissionFee,
                'status' => 'awaiting_payment',
            ]);

            // Create Order Items
            foreach ($items as $cartItem) {
                $listing = $cartItem->vendor_listing;
                $product = $listing->product_variant->product;

                $itemPrice = $listing->promo_price ?? $listing->price;
                $itemTotal = $itemPrice * $cartItem->qty;

                OrderItem::create([
                    'order_shop_id' => $orderShop->id,
                    'product_variant_id' => $listing->product_variant_id,
                    'vendor_listing_id' => $listing->id,
                    'name' => $product->name,
                    'sku' => $product->sku ?? 'N/A',
                    'qty' => $cartItem->qty,
                    'unit_price' => $itemPrice,
                    'discount_amount' => 0,
                    'tax_amount' => $itemTotal * 0.11,
                    'total' => $itemTotal,
                    'attributes' => $cartItem->variant_snapshot,
                ]);

                // Reduce stock
                $listing->decrement('qty_available', $cartItem->qty);
            }
        }

        return $order;
    }

    protected function processWalletPayment(Order $order): void
    {
        $total = $order->grand_total;

        // Deduct from customer wallet
        $this->wallet->decrement('balance', $total);

        // Create ledger entry for customer (debit)
        LedgerEntry::create([
            'account_id' => $this->wallet->id,
            'transaction_type' => 'purchase_hold',
            'direction' => 'debit',
            'amount' => $total,
            'related_type' => 'order',
            'related_id' => $order->id,
            'status' => 'completed',
            'description' => "Pembayaran pesanan {$order->order_no}",
        ]);

        // Get all order shops for this order
        $orderShops = OrderShop::where('order_id', $order->id)->get();

        // Process each order shop
        foreach ($orderShops as $orderShop) {
            // Get or create shop wallet
            $shopWallet = WalletAccount::firstOrCreate(
                [
                    'owner_type' => 'shop',
                    'owner_id' => $orderShop->shop_id,
                ],
                [
                    'currency' => 'IDR',
                    'balance' => 0,
                    'status' => 'active',
                ]
            );

            // Calculate shop amount (subtotal + tax - commission)
            $shopAmount = $orderShop->subtotal + $orderShop->tax_total - $orderShop->commission_fee;

            // Create escrow (hold payment until delivery)
            $escrow = Escrow::create([
                'order_shop_id' => $orderShop->id,
                'wallet_account_id' => $shopWallet->id,
                'amount_held' => $shopAmount,
                'status' => 'held',
            ]);

            // Update order shop escrow_id and status
            $orderShop->update([
                'escrow_id' => $escrow->id,
                'status' => 'awaiting_fulfillment',
            ]);
        }

        // Update order payment status
        $order->update([
            'payment_status' => 'paid',
            'status' => 'processing',
        ]);
    }

    protected function generateMidtransPayment(Order $order): string
    {
        // Configure Midtrans
        \Midtrans\Config::$serverKey = config('services.midtrans.server_key');
        \Midtrans\Config::$isProduction = config('services.midtrans.is_production', false);
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;

        $params = [
            'transaction_details' => [
                'order_id' => $order->order_no,
                'gross_amount' => (int) round($order->grand_total),
            ],
            'customer_details' => [
                'first_name' => $this->customer->name,
                'email' => $this->customer->email,
                'phone' => $this->recipient_phone ?? $this->customer->phone ?? '',
            ],
            'item_details' => $this->getMidtransItems(),
            'callbacks' => [
                'finish' => route('payment.finish', ['order_id' => $order->order_no]),
            ],
        ];

        // Log params for debugging
        Log::info('Midtrans Params', [
            'order_no' => $order->order_no,
            'params' => $params,
            'config' => [
                'server_key_length' => strlen(config('services.midtrans.server_key')),
                'is_production' => config('services.midtrans.is_production'),
            ],
        ]);

        try {
            $snapToken = \Midtrans\Snap::getSnapToken($params);

            // Log snap token for debugging
            Log::info("Midtrans Snap Token generated for order {$order->order_no}: {$snapToken}");

            // Return redirect URL for Snap
            return "https://app.sandbox.midtrans.com/snap/v2/vtweb/{$snapToken}";
        } catch (\Exception $e) {
            Log::error('Midtrans Snap Token Error: '.$e->getMessage(), [
                'order_no' => $order->order_no,
                'params' => $params,
            ]);
            throw new \Exception('Gagal membuat payment link. Silakan coba lagi.');
        }
    }

    protected function getMidtransItems(): array
    {
        $items = [];

        foreach ($this->cartItems as $item) {
            $listing = $item->vendor_listing;
            $product = $listing->product_variant->product;

            $items[] = [
                'id' => $listing->id,
                'price' => (int) round($item->price_snapshot),
                'quantity' => $item->qty,
                'name' => $product->name,
            ];
        }

        // Add shipping cost
        if ($this->shipping_cost > 0) {
            $items[] = [
                'id' => 'SHIPPING',
                'price' => (int) round($this->shipping_cost),
                'quantity' => 1,
                'name' => 'Biaya Pengiriman',
            ];
        }

        // Add tax
        $tax = $this->getTax();
        if ($tax > 0) {
            $items[] = [
                'id' => 'TAX',
                'price' => (int) round($tax),
                'quantity' => 1,
                'name' => 'Pajak (PPN 11%)',
            ];
        }

        return $items;
    }

    public function processTopup(): void
    {
        $this->validate([
            'topup_amount' => 'required|numeric|min:10000|max:10000000',
        ]);

        try {
            DB::beginTransaction();

            // Create topup record
            $topup = Topup::create([
                'customer_id' => $this->customer->id,
                'amount' => $this->topup_amount,
                'gateway' => 'midtrans',
                'status' => 'pending',
            ]);

            // Generate Midtrans payment
            $midtransUrl = $this->generateMidtransTopup($topup);

            DB::commit();

            $this->showTopupModal = false;
            $this->dispatch('redirect-to-midtrans', url: $midtransUrl);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Topup error: '.$e->getMessage());
            $this->dispatch('topup-error', message: 'Gagal memproses topup. Silakan coba lagi.');
        }
    }

    protected function generateMidtransTopup(Topup $topup): string
    {
        \Midtrans\Config::$serverKey = config('services.midtrans.server_key');
        \Midtrans\Config::$isProduction = config('services.midtrans.is_production', false);
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;

        $params = [
            'transaction_details' => [
                'order_id' => 'TOPUP-'.$topup->id.'-'.time(),
                'gross_amount' => (int) $topup->amount,
            ],
            'customer_details' => [
                'first_name' => $this->customer->name,
                'email' => $this->customer->email,
                'phone' => $this->customer->phone ?? '',
            ],
            'callbacks' => [
                'finish' => url("/payment/topup-finish?topup_id={$topup->id}"),
            ],
        ];

        try {
            $snapToken = \Midtrans\Snap::getSnapToken($params);

            // Update topup with gateway ref
            $topup->update(['gateway_ref' => $snapToken]);

            // Log snap token for debugging
            Log::info("Midtrans Snap Token generated for topup {$topup->id}: {$snapToken}");

            // Return redirect URL for Snap
            return "https://app.sandbox.midtrans.com/snap/v2/vtweb/{$snapToken}";
        } catch (\Exception $e) {
            Log::error('Midtrans Topup Token Error: '.$e->getMessage(), [
                'topup_id' => $topup->id,
                'params' => $params,
            ]);
            throw new \Exception('Gagal membuat payment link untuk topup. Silakan coba lagi.');
        }
    }

    protected function createOrUpdateAddress(): Address
    {
        return Address::create([
            'customer_id' => $this->customer->id,
            'label' => 'Shipping Address',
            'recipient_name' => $this->recipient_name,
            'phone' => $this->recipient_phone,
            'line1' => $this->address_line1,
            'line2' => $this->address_line2,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country_code' => $this->country_code,
            'is_default_shipping' => false,
            'is_default_billing' => false,
        ]);
    }

    protected function loadCart(): void
    {
        $customerId = $this->customer->id;
        $sessionId = session()->getId();

        // Try to find cart by customer_id first
        $customerCart = Cart::where('customer_id', $customerId)->first();

        // Also check if there's a guest cart with current session
        $guestCart = Cart::where('session_id', $sessionId)
            ->whereNull('customer_id')
            ->first();

        // If both exist, merge them
        if ($customerCart && $guestCart) {
            // Move all items from guest cart to customer cart
            foreach ($guestCart->cart_items as $guestItem) {
                // Check if same item exists in customer cart
                $existingItem = $customerCart->cart_items()
                    ->where('vendor_listing_id', $guestItem->vendor_listing_id)
                    ->first();

                if ($existingItem) {
                    // Update quantity
                    $existingItem->increment('qty', $guestItem->qty);
                } else {
                    // Move item to customer cart
                    $guestItem->update(['cart_id' => $customerCart->id]);
                }
            }

            // Delete guest cart
            $guestCart->delete();

            $this->cart = $customerCart;
        } elseif ($guestCart) {
            // Convert guest cart to customer cart
            $guestCart->update([
                'customer_id' => $customerId,
                'session_id' => null,
            ]);

            $this->cart = $guestCart;
        } else {
            // Use customer cart or null
            $this->cart = $customerCart;
        }
    }

    protected function loadWallet(): void
    {
        $this->wallet = WalletAccount::firstOrCreate(
            [
                'owner_type' => 'customer',
                'owner_id' => $this->customer->id,
            ],
            [
                'currency' => 'IDR',
                'balance' => 0,
                'status' => 'active',
            ]
        );
    }

    protected function loadDefaultAddress(): void
    {
        $defaultAddress = Address::where('customer_id', $this->customer->id)
            ->where('is_default_shipping', true)
            ->first();

        if ($defaultAddress) {
            $this->recipient_name = $defaultAddress->recipient_name;
            $this->recipient_phone = $defaultAddress->phone;
            $this->address_line1 = $defaultAddress->line1;
            $this->address_line2 = $defaultAddress->line2;
            $this->city = $defaultAddress->city;
            $this->state = $defaultAddress->state;
            $this->postal_code = $defaultAddress->postal_code;
            $this->country_code = $defaultAddress->country_code;
        } else {
            // Use customer info as default
            $this->recipient_name = $this->customer->name;
            $this->recipient_phone = $this->customer->phone;
        }
    }

    protected function getSubtotal(): float
    {
        return $this->cartItems->sum(function ($item) {
            return $item->price_snapshot * $item->qty;
        });
    }

    protected function getTax(): float
    {
        $subtotal = $this->getSubtotal();

        return $subtotal * 0.11; // 11% PPN
    }

    protected function getGrandTotal(): float
    {
        return $this->getSubtotal() + $this->shipping_cost + $this->getTax();
    }

    public function render()
    {
        return view('livewire.ecommerce.checkout', [
            'subtotal' => $this->getSubtotal(),
            'tax' => $this->getTax(),
            'grandTotal' => $this->getGrandTotal(),
        ]);
    }
}

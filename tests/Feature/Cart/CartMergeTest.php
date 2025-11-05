<?php

use App\Livewire\Ecommerce\AddToCart;
use App\Models\Brand;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\Vendor;
use App\Models\VendorListing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('cart');

beforeEach(function () {
    // Create necessary data
    $this->category = Category::factory()->create();
    $this->brand = Brand::factory()->create();

    $this->product = Product::factory()->create([
        'primary_category_id' => $this->category->id,
        'brand_id' => $this->brand->id,
    ]);    $this->variant = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
    ]);

    $this->vendor = Vendor::factory()->create();
    $this->shop = Shop::factory()->create([
        'vendor_id' => $this->vendor->id,
    ]);

    $this->listing = VendorListing::factory()->create([
        'shop_id' => $this->shop->id,
        'product_variant_id' => $this->variant->id,
        'price' => 100000,
        'qty_available' => 10,
        'status' => 'active',
    ]);

    $this->customer = Customer::factory()->create();
});

it('merges guest cart into customer cart after login', function () {
    // 1. Create existing customer cart
    $customerCart = Cart::create([
        'customer_id' => $this->customer->id,
        'session_id' => null,
    ]);

    CartItem::create([
        'cart_id' => $customerCart->id,
        'vendor_listing_id' => $this->listing->id,
        'qty' => 2,
        'price_snapshot' => 100000,
        'variant_snapshot' => ['sku' => 'TEST-001'],
    ]);

    // 2. Create guest cart with current session
    $sessionId = session()->getId();
    $guestCart = Cart::create([
        'session_id' => $sessionId,
        'customer_id' => null,
    ]);

    CartItem::create([
        'cart_id' => $guestCart->id,
        'vendor_listing_id' => $this->listing->id,
        'qty' => 3,
        'price_snapshot' => 100000,
        'variant_snapshot' => ['sku' => 'TEST-001'],
    ]);

    // Verify both carts exist
    expect($customerCart->cart_items)->toHaveCount(1)
        ->and($guestCart->cart_items)->toHaveCount(1);

    // 3. Login as customer and add to cart - should merge
    actingAs($this->customer, 'customer');

    Livewire::test(AddToCart::class)
        ->set('variantId', $this->variant->id)
        ->set('qty', 1)
        ->call('addToCart');

    // 4. Verify merge happened
    $mergedCart = Cart::where('customer_id', $this->customer->id)->first();

    expect($mergedCart)->not->toBeNull()
        ->and($mergedCart->cart_items)->toHaveCount(1)
        ->and($mergedCart->cart_items->first()->qty)->toBe(6); // 2 + 3 + 1

    // Guest cart should be deleted
    expect(Cart::where('session_id', $sessionId)->exists())->toBeFalse();
});it('converts guest cart to customer cart on first login', function () {
    // 1. Create guest cart
    $guestSessionId = 'guest-session-456';
    session()->setId($guestSessionId);

    $guestCart = Cart::create([
        'session_id' => $guestSessionId,
        'customer_id' => null,
    ]);

    CartItem::create([
        'cart_id' => $guestCart->id,
        'vendor_listing_id' => $this->listing->id,
        'qty' => 2,
        'price_snapshot' => 100000,
        'variant_snapshot' => ['sku' => 'TEST-002'],
    ]);

    // 2. Login (customer has no existing cart)
    actingAs($this->customer, 'customer');

    // 3. Add to cart - should convert guest cart
    Livewire::test(AddToCart::class)
        ->set('variantId', $this->variant->id)
        ->set('qty', 1)
        ->call('addToCart');

    // 4. Verify conversion
    $customerCart = Cart::where('customer_id', $this->customer->id)->first();

    expect($customerCart)->not->toBeNull()
        ->and($customerCart->session_id)->toBeNull()
        ->and($customerCart->cart_items)->toHaveCount(1)
        ->and($customerCart->cart_items->first()->qty)->toBe(3); // 2 + 1

    // Original guest cart should be gone
    expect(Cart::where('session_id', $guestSessionId)->exists())->toBeFalse();
});

it('keeps separate items when merging different products', function () {
    // Create second listing
    $variant2 = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
    ]);

    $listing2 = VendorListing::factory()->create([
        'shop_id' => $this->shop->id,
        'product_variant_id' => $variant2->id,
        'price' => 150000,
        'qty_available' => 10,
        'status' => 'active',
    ]);

    // 1. Guest cart with listing 1
    $guestSessionId = 'guest-session-789';
    session()->setId($guestSessionId);

    $guestCart = Cart::create([
        'session_id' => $guestSessionId,
        'customer_id' => null,
    ]);

    CartItem::create([
        'cart_id' => $guestCart->id,
        'vendor_listing_id' => $this->listing->id,
        'qty' => 2,
        'price_snapshot' => 100000,
        'variant_snapshot' => ['sku' => 'TEST-003'],
    ]);

    // 2. Customer cart with listing 2
    $customerCart = Cart::create([
        'customer_id' => $this->customer->id,
        'session_id' => null,
    ]);

    CartItem::create([
        'cart_id' => $customerCart->id,
        'vendor_listing_id' => $listing2->id,
        'qty' => 1,
        'price_snapshot' => 150000,
        'variant_snapshot' => ['sku' => 'TEST-004'],
    ]);

    // 3. Login and add to cart
    actingAs($this->customer, 'customer');

    Livewire::test(AddToCart::class)
        ->set('variantId', $this->variant->id)
        ->set('qty', 1)
        ->call('addToCart');

    // 4. Should have 2 separate items in customer cart
    $mergedCart = Cart::where('customer_id', $this->customer->id)->first();

    expect($mergedCart->cart_items)->toHaveCount(2);

    // Verify quantities
    $item1 = $mergedCart->cart_items()
        ->where('vendor_listing_id', $this->listing->id)
        ->first();
    $item2 = $mergedCart->cart_items()
        ->where('vendor_listing_id', $listing2->id)
        ->first();

    expect($item1->qty)->toBe(3) // 2 from guest + 1 added
        ->and($item2->qty)->toBe(1); // original customer cart item
});

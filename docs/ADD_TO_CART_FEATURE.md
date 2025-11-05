# Add to Cart Feature - Product Detail Page

## Overview
Fitur Add to Cart pada halaman product detail telah diimplementasikan menggunakan Livewire 3 untuk memberikan pengalaman yang interaktif tanpa reload halaman.

## Components Created

### 1. Livewire Component: `AddToCart`
**Location:** `app/Livewire/Ecommerce/AddToCart.php`

**Features:**
- ✅ Pilih varian produk
- ✅ Increment/Decrement quantity
- ✅ Add to cart (logged in & guest users)
- ✅ Real-time cart count update di navbar
- ✅ Stock validation
- ✅ Duplicate item handling (merge quantities)
- ⏳ Wishlist placeholder (untuk future implementation)

**Properties:**
- `variantId` - ID varian yang dipilih
- `qty` - Jumlah produk
- `variantOptions` - Array varian yang tersedia
- `selectedListingId` - ID listing vendor yang dipilih

**Methods:**
- `mount()` - Initialize component dengan default variant
- `updatedVariantId()` - Hook saat variant berubah
- `increment()` - Tambah quantity
- `decrement()` - Kurangi quantity
- `addToCart()` - Tambahkan item ke cart
- `addToWishlist()` - Placeholder untuk wishlist
- `findListingForVariant()` - Cari listing aktif untuk variant
- `getOrCreateCart()` - Get atau create cart untuk user/guest

### 2. Blade View
**Location:** `resources/views/livewire/ecommerce/add-to-cart.blade.php`

**Features:**
- Radio button untuk pilih varian
- Quantity selector dengan +/- buttons
- Add to cart button dengan loading state
- Wishlist button (placeholder)
- Event listeners untuk success/error notifications

## Integration

### Product Detail Page
**File:** `resources/views/pages/product/product-detail.blade.php`

Replaced static HTML with Livewire component:
```blade
@livewire('ecommerce.add-to-cart', [
    'variantId' => Arr::get($defaultVariant, 'id'),
    'variantOptions' => $variantOptions->toArray()
])
```

### Navbar Component
**File:** `app/Livewire/Ecommerce/Navbar.php`

Already has event listener configured:
```php
#[On('cart.updated')]
public function refreshCartCount(?int $count = null): void
{
    $this->cartCount = $count ?? (int) session('cart.items_count', $this->cartCount);
}
```

## Database Structure

### Cart Table
- `id` - Primary key
- `customer_id` - FK ke customers (nullable untuk guest)
- `session_id` - Session ID untuk guest users
- `created_at`, `updated_at`

### Cart Items Table
- `id` - Primary key
- `cart_id` - FK ke carts
- `vendor_listing_id` - FK ke vendor_listings
- `qty` - Quantity
- `price_snapshot` - Harga saat ditambahkan
- `variant_snapshot` - JSON data varian
- `created_at`, `updated_at`

## Events Dispatched

### 1. `cart.updated`
Dispatched ke navbar untuk update cart count
```php
$this->dispatch('cart.updated', count: $totalItems);
```

### 2. `cart-success`
Show success notification
```php
$this->dispatch('cart-success', message: 'Produk berhasil ditambahkan ke keranjang!');
```

### 3. `cart-error`
Show error notification
```php
$this->dispatch('cart-error', message: 'Error message');
```

### 4. `wishlist-info`
Show wishlist info (placeholder)
```php
$this->dispatch('wishlist-info', message: 'Fitur wishlist akan segera hadir!');
```

## User Flow

### For Logged In Users
1. User memilih varian produk
2. User mengatur quantity
3. Klik "Add to Cart"
4. System creates/updates cart with `customer_id`
5. Cart item created/updated
6. Session updated dengan cart count
7. Event dispatched ke navbar
8. Navbar cart badge updated
9. Success notification shown

### For Guest Users
1. Same flow sebagai logged in users
2. Cart identified by `session_id` instead of `customer_id`
3. Session persists across requests
4. Dapat di-merge saat user login (future feature)

## Validation

### Stock Validation
- Checks if listing is active
- Validates available quantity
- Prevents over-ordering

### Duplicate Item Handling
- Checks if item already in cart
- Merges quantities if exists
- Validates total quantity against stock

## Future Enhancements

### Wishlist Feature
Untuk mengimplementasikan wishlist, perlu:
1. Create `wishlists` table migration
2. Create `Wishlist` model
3. Update `AddToCart` component dengan wishlist logic
4. Create wishlist page

### Cart Merge on Login
Saat guest user login:
1. Merge guest cart dengan user cart
2. Handle duplicate items
3. Clear guest cart

### Better Notifications
Replace `alert()` dengan toast notifications atau modal yang lebih modern.

## Testing

### Manual Testing Steps
1. Buka halaman product detail
2. Pilih varian produk
3. Ubah quantity dengan +/- button
4. Klik "Add to Cart"
5. Verify cart count di navbar bertambah
6. Check di halaman cart apakah item sudah ada
7. Test dengan guest user dan logged in user

### Future Automated Tests
Buat feature tests untuk:
- Adding item to cart (guest & logged in)
- Quantity increment/decrement
- Stock validation
- Duplicate item handling
- Cart count update

## Code Quality
✅ Formatted with Laravel Pint
✅ Follows Laravel conventions
✅ Uses Livewire 3 best practices
✅ No compile errors
✅ Proper type hints and documentation

# Cart List Feature Documentation

## Overview
Implemented a complete shopping cart management page using Livewire 3 with real-time quantity updates, automatic price calculations, coupon application, and seamless integration with the Cart and CartItem models.

## Created Files

### 1. CartList Livewire Component
**File**: `app/Livewire/Ecommerce/CartList.php`

**Features**:
- Display all cart items with product details, variants, and pricing
- Real-time quantity updates with stock validation
- Remove items from cart with confirmation
- Coupon code application with validation
- Automatic price calculations (subtotal, discount, tax, total)
- Event dispatching to update navbar cart badge
- Support for both logged-in and guest users

**Key Methods**:
```php
public function mount(): void                           // Load cart on component initialization
public function updateQuantity(int $id, int $qty): void // Update cart item quantity with validation
public function removeItem(int $cartItemId): void       // Remove item from cart
public function applyCoupon(): void                     // Apply and validate coupon code
public function removeCoupon(): void                    // Remove applied coupon
protected function loadCart(): void                     // Load cart based on auth status
protected function dispatchCartUpdate(): void           // Dispatch event to navbar
protected function getSubtotal(): float                 // Calculate subtotal
protected function getDiscount(): float                 // Calculate discount from coupon
protected function getTax(): float                      // Calculate PPN 11%
protected function getTotal(): float                    // Calculate final total
```

**Computed Properties**:
- `cartItems()` - Eager loads cart items with all necessary relationships

### 2. CartList Blade View
**File**: `resources/views/livewire/ecommerce/cart-list.blade.php`

**Features**:
- Responsive grid layout (2 columns for items, 1 for summary)
- Empty cart state with "Start Shopping" CTA
- Product cards with:
  - Product image, name, brand, shop
  - Variant information (color, size, nicotine, etc.)
  - Stock warning for low inventory
  - Quantity controls (-, input, +)
  - Remove button with confirmation
  - Loading states
- Order summary sidebar with:
  - Coupon input field
  - Price breakdown (subtotal, discount, tax)
  - Total in large, prominent display
  - Checkout button
  - Continue shopping link
- Real-time notifications via Livewire events
- Dark mode support

### 3. Updated Cart Page
**File**: `resources/views/pages/account/cart-list.blade.php`

**Changes**:
- Replaced entire static HTML with single Livewire component
- Clean integration: `@livewire('ecommerce.cart-list')`

## Technical Implementation

### Database Integration
- Uses `Cart` model with `cart_items()` relationship
- Queries based on `customer_id` (logged-in) or `session_id` (guest)
- Eager loading for optimal performance:
  ```php
  ->with([
      'vendor_listing.product_variant.product.brand',
      'vendor_listing.product_variant.product.media',
      'vendor_listing.shop',
  ])
  ```

### Event System
**Dispatched Events**:
1. `cart.updated` - Sent to Navbar component with cart count
2. `cart-success` - Shows success notifications
3. `cart-error` - Shows error notifications

**Event Listeners** (in blade view):
```javascript
$wire.on('cart-success', (event) => { alert(event.message); });
$wire.on('cart-error', (event) => { alert(event.message); });
```

### Quantity Updates
**Validation Checks**:
1. Minimum quantity: 1
2. Maximum quantity: Available stock (`listing->qty_available`)
3. Item belongs to current cart
4. Product listing is active
5. Exception handling with detailed logging

**Update Flow**:
1. User changes quantity via buttons or input
2. `updateQuantity()` method validates
3. Database updated via `CartItem::update()`
4. Event dispatched to navbar
5. Computed property cache cleared
6. Success notification shown

### Coupon System
**Validation**:
- Code must exist in `coupons` table
- Must be active (`is_active = true`)
- Within valid date range (`valid_from` and `valid_until`)
- Minimum purchase amount met

**Discount Calculation**:
- **Percentage**: `(subtotal * value) / 100`
  - Respects `max_discount` if set
- **Fixed Amount**: `min(value, subtotal)`

**Tax Calculation**:
- Applied after discount: `(subtotal - discount) * 0.11` (PPN 11%)

### Price Calculations
**Automatic Recalculation**:
```php
Subtotal = Σ(item.price_snapshot × item.qty)
Discount = Applied based on coupon type
Tax      = (Subtotal - Discount) × 0.11
Total    = Subtotal - Discount + Tax
```

**Real-time Updates**:
- Calculations run on every render
- Values passed to view as variables
- No manual "update cart" button needed

## User Experience

### Loading States
- Individual item loading overlays during updates
- Button disable states while processing
- Spinner animations for visual feedback

### Validation Messages
- Minimum quantity warning
- Maximum stock warning
- Invalid coupon messages
- Minimum purchase requirements
- Item not found errors

### Empty Cart State
- Centered SVG icon
- Friendly message
- Prominent "Start Shopping" CTA button

### Mobile Responsiveness
- Stacks to single column on mobile
- Touch-friendly button sizes
- Readable text and pricing
- Scrollable cart items

## Testing Recommendations

### Feature Tests
```php
it('displays cart items correctly')
it('updates quantity and recalculates totals')
it('removes item from cart')
it('applies valid coupon code')
it('rejects invalid coupon code')
it('prevents quantity exceeding stock')
it('dispatches events to navbar')
it('handles guest and authenticated users')
```

### Browser Tests (Pest v4)
```php
it('updates cart interactively', function() {
    visit('/cart')
        ->assertSee('Keranjang Belanja')
        ->click('+')
        ->assertSee('Keranjang berhasil diperbarui')
        ->fill('couponCode', 'DISC10')
        ->click('Terapkan')
        ->assertSee('Kupon berhasil diterapkan');
});
```

## Integration Points

### Navbar Component
- Listens to `cart.updated` event
- Updates badge count in real-time
- Method: `#[On('cart.updated')] refreshCartCount($count)`

### AddToCart Component
- Creates cart items that appear in CartList
- Dispatches same `cart.updated` event
- Maintains data consistency

### Models Used
- `Cart` - Main cart container
- `CartItem` - Individual items with qty and price
- `VendorListing` - Product availability and pricing
- `ProductVariant` - Product variations
- `Product` - Product details
- `Brand` - Brand information
- `Shop` - Vendor shop details
- `Coupon` - Discount codes

## Future Enhancements
1. Wishlist integration (move to wishlist button)
2. Save for later functionality
3. Multiple shipping addresses
4. Estimated delivery dates
5. Product recommendations in cart
6. Bulk actions (remove all, save all)
7. Share cart functionality
8. Cart expiration for guest users

## Notes
- All prices stored in `price_snapshot` to prevent price changes after adding to cart
- Variant details stored in JSON `variant_snapshot` for historical accuracy
- Stock validation on every update to prevent overselling
- Supports both IDR currency formatting
- Tax rate is hardcoded (11% PPN) - consider moving to config

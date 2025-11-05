# Refactor: Cart Count dari Model Cart, Bukan Session

## Overview
Cart count sekarang diambil langsung dari database (model `Cart` dan `CartItem`) bukan dari session storage. Ini memastikan data cart selalu accurate dan persistent.

## Problem dengan Pendekatan Lama

### Issues:
1. **Session Tidak Reliable**
   - Session bisa hilang/expire
   - Session tidak sync dengan database
   - Data tidak persistent across devices

2. **Data Inconsistency**
   - Cart count di session bisa berbeda dengan database
   - Ketika user refresh page, count bisa tidak match
   - Manual sync diperlukan di berbagai tempat

3. **No Single Source of Truth**
   - Database punya data cart
   - Session juga store count
   - Dua sumber data berbeda bisa conflict

## New Approach: Database-Driven Cart Count

### Architecture

```
┌─────────────────┐
│   Navbar.php    │ ← Displays cart count
│  (Livewire)     │
└────────┬────────┘
         │
         │ getCartCount()
         │
         ▼
┌─────────────────┐     ┌──────────────┐
│   Cart Model    │────▶│  CartItem    │
│                 │     │              │
│ - customer_id   │     │ - cart_id    │
│ - session_id    │     │ - qty        │
└─────────────────┘     └──────────────┘
         ▲
         │
         │ cart_items()->sum('qty')
         │
┌────────┴────────┐
│ AddToCart.php   │ ← Updates cart
│   (Livewire)    │
└─────────────────┘
```

## Changes Made

### 1. Navbar Component (`app/Livewire/Ecommerce/Navbar.php`)

#### Added Imports
```php
use App\Models\Cart;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
```

#### New Method: `getCartCount()`
```php
/**
 * Get cart count from database based on current user/session
 */
protected function getCartCount(): int
{
    $customerId = Auth::guard('web')->id();

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
```

#### Updated Methods

**mount()**
```php
// Before
$this->cartCount = $cartCount ?? (int) session('cart.items_count', 0);

// After
$this->cartCount = $cartCount ?? $this->getCartCount();
```

**refreshCartCount()**
```php
// Before
$this->cartCount = $count ?? (int) session('cart.items_count', $this->cartCount);

// After
$this->cartCount = $count ?? $this->getCartCount();
```

### 2. AddToCart Component (`app/Livewire/Ecommerce/AddToCart.php`)

#### Updated addToCart() Method

**Before:**
```php
// Update cart count in session
$totalItems = CartItem::where('cart_id', $cart->id)->sum('qty');
Session::put('cart.items_count', $totalItems);

// Dispatch event to update navbar
$this->dispatch('cart.updated', count: $totalItems);
```

**After:**
```php
// Get updated cart count from database
$totalItems = (int) $cart->cart_items()->sum('qty');

// Dispatch event to update navbar
$this->dispatch('cart.updated', count: $totalItems);
```

**Changes:**
- ✅ Removed `Session::put()` - no more session storage
- ✅ Use relationship `cart_items()` instead of raw query
- ✅ Cast to int for type safety

## Benefits

### 1. Data Consistency ✅
- Single source of truth (database)
- No sync issues between session and database
- Cart count always accurate

### 2. Persistence ✅
- Data persists across sessions
- Survives session expiration
- Works across multiple devices (for logged in users)

### 3. Reliability ✅
- No dependency on session storage
- Works even if session is cleared
- Guaranteed to match actual cart contents

### 4. Scalability ✅
- Easier to implement cart features (merge, sync, etc.)
- Can query cart data anywhere in the app
- No manual session management needed

### 5. Maintainability ✅
- Less code complexity
- No session sync logic scattered around
- Clear data flow from database to UI

## How It Works

### For Logged In Users
1. User adds item to cart
2. `Cart` record identified by `customer_id`
3. `CartItem` created/updated
4. Count calculated: `cart->cart_items()->sum('qty')`
5. Event dispatched to Navbar
6. Navbar calls `getCartCount()` → queries database
7. Badge updated with fresh count

### For Guest Users
1. User adds item to cart
2. `Cart` record identified by `session_id`
3. Same flow as logged in users
4. Cart tied to Laravel session ID
5. Can be merged when user logs in (future feature)

## Database Queries

### Performance Considerations

**Query for Cart Count:**
```sql
-- For logged in user
SELECT SUM(qty) FROM cart_items 
WHERE cart_id = (SELECT id FROM carts WHERE customer_id = ?)

-- For guest user
SELECT SUM(qty) FROM cart_items 
WHERE cart_id = (SELECT id FROM carts WHERE session_id = ?)
```

**Optimization:**
- Consider adding index on `carts.customer_id`
- Consider adding index on `carts.session_id`
- Could cache count with short TTL if needed

## Testing

### Manual Test Cases

#### Logged In User
- [x] Add item to cart → count updates
- [x] Refresh page → count persists
- [x] Open in another tab → same count
- [x] Clear session → count still correct

#### Guest User
- [x] Add item to cart → count updates
- [x] Refresh page → count persists
- [x] Clear cookies → cart resets (expected)

#### Edge Cases
- [x] No cart exists → returns 0
- [x] Empty cart → returns 0
- [x] Multiple items → correct sum

## Migration Path

### Cleanup (Optional)
If you want to clean up old session data:

```php
// Remove from any controller init
Session::forget('cart.items_count');

// Or in console command
Artisan::command('cart:cleanup-session', function () {
    // Session cleanup if needed
});
```

## Future Enhancements

### Cart Merge on Login
When guest user logs in:
```php
public function mergeGuestCartOnLogin(User $user): void
{
    $sessionId = Session::getId();
    $guestCart = Cart::where('session_id', $sessionId)->first();
    
    if (!$guestCart) {
        return;
    }
    
    $userCart = Cart::firstOrCreate(['customer_id' => $user->id]);
    
    // Move items from guest cart to user cart
    foreach ($guestCart->cart_items as $item) {
        $existing = $userCart->cart_items()
            ->where('vendor_listing_id', $item->vendor_listing_id)
            ->first();
            
        if ($existing) {
            $existing->increment('qty', $item->qty);
        } else {
            $item->update(['cart_id' => $userCart->id]);
        }
    }
    
    // Delete guest cart
    $guestCart->delete();
}
```

### Caching (If Needed)
```php
protected function getCartCount(): int
{
    $customerId = Auth::guard('web')->id();
    $cacheKey = $customerId 
        ? "cart.count.user.{$customerId}"
        : "cart.count.session." . Session::getId();
    
    return Cache::remember($cacheKey, 60, function () use ($customerId) {
        // ... existing logic
    });
}
```

## Summary

### Before
- ❌ Cart count in session
- ❌ Manual sync required
- ❌ Inconsistency issues
- ❌ Not persistent

### After
- ✅ Cart count from database
- ✅ Auto-sync via relationships
- ✅ Always consistent
- ✅ Fully persistent
- ✅ Single source of truth

Cart count sekarang **reliable, accurate, dan maintainable**! 🎯

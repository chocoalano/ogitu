# Bugfix: Cart List Actions Not Working

## Problem
Cart List component had multiple bugs:
1. Actions (removeItem, updateQuantity, removeCoupon, applyCoupon) were not working
2. JavaScript error: `Uncaught SyntaxError: Unexpected token '*'`
3. Loading overlay had invalid Blade syntax in wire:target attribute

## Root Causes

### 1. Invalid wire:target Syntax
```blade
<!-- BEFORE (BROKEN) -->
wire:target="updateQuantity({{ $item->id }}, *), removeItem({{ $item->id }})"
```
The `*` wildcard inside a Blade expression causes a JavaScript syntax error when rendered.

### 2. Complex wire:click Expressions
Livewire 3 has issues with complex expressions in `wire:click` when passing multiple parameters with Blade variables.

## Solutions Applied

### 1. Fixed Loading Overlay
**Before:**
```blade
<td wire:loading wire:target="updateQuantity({{ $item->id }}, *), removeItem({{ $item->id }})" colspan="4">
```

**After:**
```blade
<tr wire:loading wire:target="updateQuantity, removeItem" wire:key="loading-{{ $item->id }}">
    <td colspan="4" class="text-center py-4">
        <!-- spinner -->
    </td>
</tr>
```

Changes:
- Removed parameter wildcards from target
- Simplified to just method names
- Moved to separate `<tr>` for better structure

### 2. Changed wire:click to onclick with $wire
**Before:**
```blade
wire:click="updateQuantity({{ $item->id }}, {{ max(1, $item->qty - 1) }})"
wire:click="removeItem({{ $item->id }})"
wire:click="applyCoupon"
wire:click="removeCoupon"
```

**After:**
```blade
onclick="$wire.updateQuantity({{ $item->id }}, {{ max(1, $item->qty - 1) }})"
onclick="if(confirm('...')) { $wire.removeItem({{ $item->id }}) }"
onclick="$wire.applyCoupon()"
onclick="$wire.removeCoupon()"
```

**Why this works:**
- `$wire` is Livewire 3's JavaScript API for direct method calls
- Avoids Blade parsing issues in attributes
- More reliable for complex expressions with multiple parameters
- Compatible with inline JavaScript like `confirm()`

### 3. Fixed Input onChange
**Before:**
```blade
wire:change="updateQuantity({{ $item->id }}, $event.target.value)"
```

**After:**
```blade
onchange="$wire.updateQuantity({{ $item->id }}, parseInt(this.value) || 1)"
```

Changes:
- Use native `onchange` with `$wire` API
- Added `parseInt()` for type safety
- Fallback to `1` if parsing fails

### 4. Fixed @if Directives
**Before:**
```blade
@if($item->qty <= 1) disabled @endif
```

**After:**
```blade
{{ $item->qty <= 1 ? 'disabled' : '' }}
```

Using ternary operator is more reliable in attributes.

## Testing Checklist
- [x] Click minus button decreases quantity
- [x] Click plus button increases quantity  
- [x] Manual input change updates quantity
- [x] Remove button with confirmation works
- [x] Apply coupon button works
- [x] Remove coupon button works
- [x] Loading states show during actions
- [x] No JavaScript console errors
- [x] Navbar cart count updates after actions

## Technical Notes

### Livewire 3 Best Practices
1. **Use `$wire` for complex method calls**: When passing multiple parameters or using expressions
2. **Keep wire:target simple**: Use method names only, not full expressions
3. **Prefer onclick over wire:click**: For methods with complex parameters
4. **Add type="button"**: Always specify button type to prevent form submission

### Why $wire Works Better
```javascript
// $wire is a proxy object that:
// 1. Calls Livewire component methods
// 2. Handles parameter serialization
// 3. Updates component state
// 4. Triggers reactive updates

$wire.updateQuantity(123, 5)
// Equivalent to calling the PHP method:
// public function updateQuantity(int $id, int $qty)
```

## Related Files
- `/resources/views/livewire/ecommerce/cart-list.blade.php` - Fixed view
- `/app/Livewire/Ecommerce/CartList.php` - Component (no changes needed)

## References
- Livewire 3 Documentation: JavaScript Interaction
- Livewire 3 Documentation: Loading States
- Blade Documentation: Template Syntax

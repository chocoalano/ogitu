# Login Feature with Livewire Validator

## Overview
Implemented a complete customer login system using Livewire 3 with comprehensive validation, rate limiting, session management, and automatic guest cart merging for the `auth:customer` middleware.

## Created Files

### 1. Login Livewire Component
**File**: `app/Livewire/Ecommerce/Auth/Login.php`

**Features**:
- Email and password validation with custom error messages
- Rate limiting (5 attempts per minute per IP)
- Session regeneration on successful login
- Remember me functionality
- Automatic guest cart merging to customer account
- Redirect to intended page after login
- Full integration with `auth:customer` guard

**Key Methods**:
```php
public function mount(): void                 // Check if already authenticated
public function login(): void                 // Handle login process with validation
protected function mergeGuestCart(): void     // Merge guest cart items to customer cart
```

**Validation Rules**:
- **Email**: Required, valid email format
- **Password**: Required, minimum 6 characters
- Custom error messages in Bahasa Indonesia

**Rate Limiting**:
- Maximum 5 login attempts per minute per IP address
- Automatic lockout with countdown message
- Clears on successful login

**Session Management**:
- Regenerates session ID on login to prevent session fixation
- Supports "Remember Me" functionality
- Uses `auth:customer` guard (web guard)

### 2. Login Blade View
**File**: `resources/views/livewire/ecommerce/auth/login.blade.php`

**Features**:
- Clean, modern UI matching application design
- Real-time validation error display
- Password visibility toggle using Alpine.js
- Loading states during submission
- Success notification via Livewire events
- Responsive design with mobile support
- Background decorative elements

**Form Fields**:
1. **Email Input**:
   - Type: email
   - Wire model: `email`
   - Real-time validation
   - Red border on error
   
2. **Password Input**:
   - Type: password (toggleable)
   - Wire model: `password`
   - Eye icon for show/hide
   - Real-time validation

3. **Remember Me Checkbox**:
   - Wire model: `remember`
   - Persistent login session

4. **Submit Button**:
   - Loading spinner during submission
   - Disabled state while processing
   - "Masuk" / "Memproses..." text

### 3. Updated Login Page
**File**: `resources/views/pages/account/login.blade.php`

**Changes**:
- Replaced entire static form with single Livewire component
- Clean integration: `@livewire('ecommerce.auth.login')`
- Uses layout from Livewire component

## Technical Implementation

### Authentication Flow

1. **User submits form** → `wire:submit="login"`
2. **Validate inputs** → `$this->validate()`
3. **Check rate limit** → `RateLimiter::tooManyAttempts()`
4. **Attempt authentication** → `Auth::guard('web')->attempt()`
5. **On success**:
   - Clear rate limiter
   - Regenerate session
   - Merge guest cart
   - Dispatch success event
   - Redirect to intended page
6. **On failure**:
   - Increment rate limiter
   - Show error message

### Rate Limiting Strategy

```php
$throttleKey = 'login-attempt:' . request()->ip();

// Check if too many attempts
if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
    $seconds = RateLimiter::availableIn($throttleKey);
    // Show error with countdown
}

// On successful login
RateLimiter::clear($throttleKey);

// On failed login
RateLimiter::hit($throttleKey, 60); // 60 seconds decay
```

**Benefits**:
- Prevents brute force attacks
- Per-IP tracking
- Automatic expiration after 60 seconds
- User-friendly error messages with countdown

### Guest Cart Merging

**Scenario**: User adds items to cart as guest, then logs in

**Process**:
1. Retrieve guest cart by `session_id`
2. Retrieve customer cart by `customer_id`
3. **If customer has existing cart**:
   - Loop through guest cart items
   - If item exists in customer cart: Add quantities
   - If item doesn't exist: Transfer item to customer cart
   - Delete guest cart
4. **If customer has no cart**:
   - Assign guest cart to customer
   - Update `customer_id`, clear `session_id`

**Code**:
```php
protected function mergeGuestCart(): void
{
    $guestCart = Cart::where('session_id', $guestSessionId)
        ->whereNull('customer_id')
        ->first();
        
    $customerCart = Cart::where('customer_id', $customerId)->first();
    
    if ($customerCart) {
        // Merge items
        foreach ($guestCart->cart_items as $guestItem) {
            $existingItem = $customerCart->cart_items()
                ->where('vendor_listing_id', $guestItem->vendor_listing_id)
                ->first();
                
            if ($existingItem) {
                $existingItem->update(['qty' => $existingItem->qty + $guestItem->qty]);
            } else {
                $guestItem->update(['cart_id' => $customerCart->id]);
            }
        }
        $guestCart->delete();
    } else {
        // Transfer ownership
        $guestCart->update([
            'customer_id' => $customerId,
            'session_id' => null,
        ]);
    }
}
```

### Validation & Error Handling

**Livewire Attributes**:
```php
#[Rule('required|email', message: [
    'required' => 'Email wajib diisi',
    'email' => 'Format email tidak valid',
])]
public string $email = '';

#[Rule('required|min:6', message: [
    'required' => 'Password wajib diisi',
    'min' => 'Password minimal 6 karakter',
])]
public string $password = '';
```

**Error Display in Blade**:
```blade
@error('email')
    <span class="text-sm text-red-500 mt-1">{{ $message }}</span>
@enderror
```

### Security Features

1. **Session Regeneration**:
   - Prevents session fixation attacks
   - `Session::regenerate()` on successful login

2. **Rate Limiting**:
   - Prevents brute force attacks
   - IP-based throttling

3. **Guard Specification**:
   - Uses `auth:customer` guard (web)
   - Separate from admin authentication

4. **Password Hashing**:
   - Laravel's built-in bcrypt hashing
   - Automatic password verification

5. **CSRF Protection**:
   - Automatic with Livewire forms
   - No manual token needed

### UI/UX Features

**Loading States**:
```blade
<span wire:loading.remove wire:target="login">Masuk</span>
<span wire:loading wire:target="login">
    <svg class="animate-spin">...</svg>
    Memproses...
</span>
```

**Password Toggle**:
```blade
<div x-data="{ showPassword: false }">
    <input :type="showPassword ? 'text' : 'password'">
    <button @click="showPassword = !showPassword">
        <i x-show="!showPassword" data-lucide="eye"></i>
        <i x-show="showPassword" data-lucide="eye-off"></i>
    </button>
</div>
```

**Success Notification**:
```javascript
$wire.on('login-success', (event) => {
    alert(event.message || 'Login berhasil!');
});
```

## Integration with Middleware

### Route Protection Example:
```php
Route::middleware(['auth:customer'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/checkout', [CheckoutController::class, 'index']);
});
```

### Redirect After Login:
```php
$this->redirectIntended('/', navigate: true);
```
- Redirects to intended page if set
- Falls back to homepage if no intended URL
- Uses Livewire Wire navigation for SPA-like experience

## Testing Recommendations

### Unit Tests
```php
it('validates email is required')
it('validates email format')
it('validates password is required')
it('validates password minimum length')
it('rate limits login attempts')
it('logs in with valid credentials')
it('fails login with invalid credentials')
it('regenerates session on successful login')
```

### Feature Tests
```php
it('displays login form')
it('shows validation errors')
it('redirects after successful login')
it('merges guest cart on login')
it('remembers user when checkbox checked')
it('clears rate limiter on success')
```

### Browser Tests (Pest v4)
```php
it('logs in successfully', function() {
    visit('/login')
        ->fill('email', 'customer@example.com')
        ->fill('password', 'password')
        ->check('remember')
        ->click('Masuk')
        ->assertSee('Login berhasil')
        ->assertUrl('/');
});
```

## Error Messages

**Bahasa Indonesia**:
- "Email wajib diisi"
- "Format email tidak valid"
- "Password wajib diisi"
- "Password minimal 6 karakter"
- "Email atau password yang Anda masukkan salah"
- "Terlalu banyak percobaan login. Silakan coba lagi dalam X detik"

## Future Enhancements

1. **Social Login** (Google, Facebook)
2. **Two-Factor Authentication (2FA)**
3. **Password Strength Indicator**
4. **Login History Tracking**
5. **Email Verification Requirement**
6. **Suspicious Activity Alerts**
7. **Device Management**
8. **Session Management (logout other devices)**

## Related Files
- `/app/Livewire/Ecommerce/Auth/Login.php` - Login component
- `/resources/views/livewire/ecommerce/auth/login.blade.php` - Login view
- `/resources/views/pages/account/login.blade.php` - Login page
- `/app/Models/Customer.php` - Customer model (authenticatable)
- `/app/Models/Cart.php` - Cart model for merging
- `/app/Models/CartItem.php` - Cart items

## Configuration

### Auth Guard (config/auth.php):
```php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'customers',
    ],
],

'providers' => [
    'customers' => [
        'driver' => 'eloquent',
        'model' => App\Models\Customer::class,
    ],
],
```

## Notes
- Uses `auth:customer` guard (web guard) for customer authentication
- Automatic guest cart merging ensures seamless shopping experience
- Rate limiting protects against brute force attacks
- Session regeneration prevents session fixation
- Alpine.js included with Livewire 3 for password toggle
- All validation messages in Bahasa Indonesia
- Mobile-responsive design

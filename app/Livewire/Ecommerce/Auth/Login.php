<?php

namespace App\Livewire\Ecommerce\Auth;

use App\Models\Cart;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\Support\Facades\RateLimiter as FacadesRateLimiter;
use Illuminate\Support\Facades\Session as FacadesSession;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;

#[Layout('layouts.app')]
class Login extends Component
{
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

    public bool $remember = false;

    public function mount(): void
    {
        // Redirect if already authenticated
        if (FacadesAuth::guard('customer')->check()) {
            $this->redirect('/', navigate: true);
        }
    }

    public function login(): void
    {
        $this->validate();

        // Rate limiting - 5 attempts per minute
        $throttleKey = 'login-attempt:'.request()->ip();

        if (FacadesRateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = FacadesRateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik.",
            ]);
        }

        // Attempt login
        $credentials = [
            'email' => $this->email,
            'password' => $this->password,
        ];

        if (FacadesAuth::guard('customer')->attempt($credentials, $this->remember)) {
            // Clear rate limiter on successful login
            FacadesRateLimiter::clear($throttleKey);

            // Regenerate session
            FacadesSession::regenerate();

            // Merge guest cart if exists
            $this->mergeGuestCart();

            // Dispatch success event
            $this->dispatch('login-success', message: 'Login berhasil! Selamat datang kembali.');

            // Redirect to intended page or home
            $this->redirectIntended('/', navigate: true);
        } else {
            // Increment rate limiter
            FacadesRateLimiter::hit($throttleKey, 60);

            // Failed login
            throw ValidationException::withMessages([
                'email' => 'Email atau password yang Anda masukkan salah.',
            ]);
        }
    }

    protected function mergeGuestCart(): void
    {
        try {
            $guestSessionId = FacadesSession::getId();
            $customerId = FacadesAuth::guard('customer')->id();

            // Check if guest has cart
            $guestCart = Cart::where('session_id', $guestSessionId)
                ->whereNull('customer_id')
                ->first();

            if (! $guestCart) {
                return;
            }

            // Check if customer already has cart
            $customerCart = Cart::where('customer_id', $customerId)->first();

            if ($customerCart) {
                // Merge items from guest cart to customer cart
                foreach ($guestCart->cart_items as $guestItem) {
                    // Check if item already exists in customer cart
                    $existingItem = $customerCart->cart_items()
                        ->where('vendor_listing_id', $guestItem->vendor_listing_id)
                        ->first();

                    if ($existingItem) {
                        // Update quantity
                        $existingItem->update([
                            'qty' => $existingItem->qty + $guestItem->qty,
                        ]);
                    } else {
                        // Move item to customer cart
                        $guestItem->update([
                            'cart_id' => $customerCart->id,
                        ]);
                    }
                }

                // Delete guest cart
                $guestCart->delete();
            } else {
                // Assign guest cart to customer
                $guestCart->update([
                    'customer_id' => $customerId,
                    'session_id' => null,
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Merge guest cart error: '.$e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.ecommerce.auth.login');
    }
}

<?php

namespace App\Livewire\Ecommerce\Auth;

use App\Models\Customer;
use App\Models\MlmMember;
use App\Models\MlmReferral;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;

#[Layout('layouts.app')]
class Register extends Component
{
    #[Rule('required|string|min:3|max:255')]
    public string $name = '';

    #[Rule('required|email|max:255|unique:customers,email')]
    public string $email = '';

    #[Rule('required|string|min:10|max:20')]
    public string $phone = '';

    #[Rule('nullable|date|before:today')]
    public ?string $dob = null;

    #[Rule('required|string|min:8|confirmed')]
    public string $password = '';

    public string $password_confirmation = '';

    #[Rule('nullable|string|exists:customers,customer_code')]
    public string $referral_code = '';

    public ?MlmMember $sponsor = null;

    public bool $agreeTerms = false;

    public function mount(): void
    {
        // Check if referral code is in URL
        if (request()->has('ref')) {
            $this->referral_code = request()->get('ref');
            $this->validateReferralCode();
        }
    }

    public function updatedReferralCode(): void
    {
        // Auto-format: uppercase and trim
        $this->referral_code = strtoupper(trim($this->referral_code));
        $this->validateReferralCode();
    }

    protected function validateReferralCode(): void
    {
        if (! empty($this->referral_code)) {
            // Find customer by customer_code, then get their MLM member
            $sponsorCustomer = Customer::where('customer_code', $this->referral_code)->first();

            if ($sponsorCustomer) {
                // Check if customer has MLM member
                $mlmMember = $sponsorCustomer->mlmMember;

                if ($mlmMember && $mlmMember->status === 'active') {
                    $this->sponsor = $mlmMember;
                    $this->resetErrorBag('referral_code');
                } else {
                    $this->sponsor = null;
                    $this->addError('referral_code', 'Customer ini belum terdaftar sebagai member MLM atau tidak aktif.');
                }
            } else {
                $this->sponsor = null;
                $this->addError('referral_code', 'Kode customer tidak ditemukan.');
            }
        } else {
            $this->sponsor = null;
        }
    }

    public function register()
    {
        // Validate all fields
        $this->validate();

        if (! $this->agreeTerms) {
            $this->addError('agreeTerms', 'Anda harus menyetujui syarat dan ketentuan.');

            return;
        }

        try {
            DB::beginTransaction();

            // Re-validate referral code if provided to ensure sponsor is still set
            $sponsorId = null;
            $sponsorLevel = 0;

            if (! empty($this->referral_code)) {
                $sponsorCustomer = Customer::where('customer_code', $this->referral_code)->first();

                if ($sponsorCustomer) {
                    $sponsor = $sponsorCustomer->mlmMember;

                    if ($sponsor && $sponsor->status === 'active') {
                        $sponsorId = $sponsor->id;
                        $sponsorLevel = $sponsor->level;
                        $this->sponsor = $sponsor; // Ensure sponsor is set
                    }
                }
            }

            // Create customer
            $customer = Customer::create([
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'dob' => $this->dob,
                'password' => Hash::make($this->password),
                'status' => 'active',
            ]);

            // Create MLM member
            $mlmMember = MlmMember::create([
                'customer_id' => $customer->id,
                'sponsor_id' => $sponsorId,
                'level' => $sponsorId ? ($sponsorLevel + 1) : 1,
                'status' => 'active',
            ]);

            // Create referral relationships if has sponsor
            if ($this->sponsor) {
                $this->createReferralChain($mlmMember, $this->sponsor);
            }

            DB::commit();

            // Auto login after register
            Auth::guard('customer')->login($customer);

            // Regenerate session to prevent fixation
            request()->session()->regenerate();

            // Redirect to home with success message
            return redirect()->route('home')->with('success', 'Registrasi berhasil! Selamat datang di Ogitu.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('register', 'Terjadi kesalahan saat registrasi. Silakan coba lagi.');
            report($e);
        }
    }

    protected function createReferralChain(MlmMember $newMember, MlmMember $sponsor): void
    {
        // Create direct referral (level 1)
        MlmReferral::create([
            'sponsor_member_id' => $sponsor->id,
            'downline_member_id' => $newMember->id,
            'level' => 1,
        ]);

        // Update sponsor's downline count
        $sponsor->increment('total_downlines');

        // Create upline chain referrals (up to 5 levels for tracking)
        $currentSponsor = $sponsor;
        $level = 2;
        $maxLevels = 5;

        while ($currentSponsor->sponsor && $level <= $maxLevels) {
            $uplineSponsor = $currentSponsor->sponsor;

            MlmReferral::create([
                'sponsor_member_id' => $uplineSponsor->id,
                'downline_member_id' => $newMember->id,
                'level' => $level,
            ]);

            $uplineSponsor->increment('total_downlines');

            $currentSponsor = $uplineSponsor;
            $level++;
        }
    }

    public function render()
    {
        return view('livewire.ecommerce.auth.register');
    }
}

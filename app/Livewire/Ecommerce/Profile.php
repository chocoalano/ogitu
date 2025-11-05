<?php

namespace App\Livewire\Ecommerce;

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Profile extends Component
{
    use WithFileUploads;

    public Customer $customer;

    // Personal Details
    #[Rule('required|string|max:255')]
    public string $name = '';

    #[Rule('required|email|max:255')]
    public string $email = '';

    #[Rule('nullable|string|max:20')]
    public string $phone = '';

    #[Rule('nullable|date')]
    public ?string $dob = null;

    // Change Password
    #[Rule('required|min:6')]
    public string $current_password = '';

    #[Rule('required|min:6')]
    public string $new_password = '';

    #[Rule('required|same:new_password')]
    public string $new_password_confirmation = '';

    public function mount(): void
    {
        if (! Auth::guard('customer')->check()) {
            $this->redirect('/login', navigate: true);

            return;
        }

        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();
        $this->customer = $customer;
        $this->name = $this->customer->name;
        $this->email = $this->customer->email;
        $this->phone = $this->customer->phone ?? '';
        $this->dob = $this->customer->dob ? $this->customer->dob->format('Y-m-d') : null;
    }

    public function updateProfile(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:customers,email,'.$this->customer->id,
            'phone' => 'nullable|string|max:20',
            'dob' => 'nullable|date',
        ]);

        try {
            $this->customer->update($validated);

            $this->dispatch('profile-success', message: 'Profil berhasil diperbarui!');
        } catch (\Exception $e) {
            $this->dispatch('profile-error', message: 'Gagal memperbarui profil');
        }
    }

    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => 'required|min:6',
            'new_password' => 'required|min:6',
            'new_password_confirmation' => 'required|same:new_password',
        ]);

        try {
            // Verify current password
            if (! Hash::check($this->current_password, $this->customer->password)) {
                $this->addError('current_password', 'Password saat ini tidak sesuai');

                return;
            }

            // Update password
            $this->customer->update([
                'password' => Hash::make($this->new_password),
            ]);

            // Clear password fields
            $this->current_password = '';
            $this->new_password = '';
            $this->new_password_confirmation = '';

            $this->dispatch('password-success', message: 'Password berhasil diperbarui!');
        } catch (\Exception $e) {
            $this->dispatch('password-error', message: 'Gagal memperbarui password');
        }
    }

    public function render()
    {
        return view('livewire.ecommerce.profile');
    }
}

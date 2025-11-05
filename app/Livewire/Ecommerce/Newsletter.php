<?php

namespace App\Livewire\Ecommerce;

use App\Models\Subscriber;
use Livewire\Component;

class Newsletter extends Component
{
    public string $email = '';

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255', 'unique:subscribers,email'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar sebagai subscriber.',
        ];
    }

    public function subscribe(): void
    {
        $this->validate();

        Subscriber::create([
            'email' => $this->email,
            'is_active' => true,
            'subscribed_at' => now(),
        ]);

        session()->flash('newsletter_success', 'Terima kasih! Anda telah berhasil berlangganan newsletter kami.');

        $this->reset('email');
    }

    public function render()
    {
        return view('livewire.ecommerce.newsletter');
    }
}

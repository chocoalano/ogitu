<?php

use App\Livewire\Ecommerce\Newsletter;
use App\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class)->group('newsletter');

it('can render the newsletter component', function () {
    Livewire::test(Newsletter::class)
        ->assertStatus(200)
        ->assertSee('Berlangganan');
});

it('can subscribe with valid email', function () {
    Livewire::test(Newsletter::class)
        ->set('email', 'test@example.com')
        ->call('subscribe')
        ->assertHasNoErrors();

    assertDatabaseHas('subscribers', [
        'email' => 'test@example.com',
        'is_active' => true,
    ]);

    expect(Subscriber::where('email', 'test@example.com')->first())
        ->subscribed_at->not->toBeNull();
});

it('validates required email', function () {
    Livewire::test(Newsletter::class)
        ->set('email', '')
        ->call('subscribe')
        ->assertHasErrors(['email' => 'required']);
});

it('validates email format', function () {
    Livewire::test(Newsletter::class)
        ->set('email', 'invalid-email')
        ->call('subscribe')
        ->assertHasErrors(['email' => 'email']);
});

it('validates unique email', function () {
    Subscriber::factory()->create(['email' => 'existing@example.com']);

    Livewire::test(Newsletter::class)
        ->set('email', 'existing@example.com')
        ->call('subscribe')
        ->assertHasErrors(['email' => 'unique']);
});

it('resets email field after successful subscription', function () {
    Livewire::test(Newsletter::class)
        ->set('email', 'reset@example.com')
        ->call('subscribe')
        ->assertSet('email', '');
});

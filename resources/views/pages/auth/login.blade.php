<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts::guest')] #[Title('Sign in')] class extends Component {
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required')]
    public string $password = '';

    public bool $remember = true;

    public function login()
    {
        $this->validate();

        $key = Str::lower($this->email).'|'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('email', 'Too many attempts. Try again in '.RateLimiter::availableIn($key).' seconds.');

            return;
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($key);
            $this->addError('email', 'That email and password do not match an account. Ask the head teacher if you need one.');

            return;
        }

        RateLimiter::clear($key);
        session()->regenerate();

        return $this->redirect('/', navigate: true);
    }
};
?>

<main class="min-h-screen flex items-center justify-center p-5">
    <div class="w-full max-w-md">
        <x-app-brand class="mb-6 justify-center" />
        <div class="panel p-6 md:p-8">
            <h1 class="heading text-2xl">Sign in</h1>
            <p class="text-muted mt-1 mb-6">Use the school email the head teacher gave you.</p>
            <x-form wire:submit="login" no-separator>
                <x-input label="Email" wire:model="email" type="email" autocomplete="username" autofocus />
                <x-password label="Password" wire:model="password" autocomplete="current-password" right />
                <x-checkbox label="Keep me signed in on this device" wire:model="remember" />
                <x-slot:actions>
                    <x-button label="Sign in" type="submit" spinner="login" class="btn-primary w-full" />
                </x-slot:actions>
            </x-form>
        </div>
        <p class="text-center text-sm text-muted mt-6">Tracks learners' progress on the seven CBC competencies and flags gaps early.</p>
    </div>
</main>

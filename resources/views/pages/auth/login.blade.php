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

<main class="min-h-screen grid lg:grid-cols-[1fr_minmax(0,32rem)]">
    <section class="hidden lg:flex flex-col justify-between p-12 bg-primary text-primary-content">
        <x-app-brand />
        <div class="max-w-xl">
            <p class="display text-6xl">Who needs help.<br>Which competency.<br><span class="text-secondary">What to do.</span></p>
            <p class="mt-6 text-lg text-primary-content/85 max-w-[52ch]">Scores and attendance from your class register, checked every evening against the seven CBC competencies.</p>
        </div>
        <p class="text-sm text-primary-content/75">Verona High School Mutundwe · Kampala</p>
    </section>

    <section class="bg-base-200 flex items-center justify-center p-5 md:p-10">
        <div class="w-full max-w-sm">
            <div class="lg:hidden mb-8 text-primary"><x-app-brand /></div>
            <div class="board p-6 md:p-8">
                <h1 class="display text-4xl">Sign in</h1>
                <p class="text-muted mt-2 mb-6">Use the school email the head teacher gave you.</p>
                <x-form wire:submit="login" no-separator>
                    <x-input label="Email" wire:model="email" type="email" autocomplete="username" autofocus />
                    <x-password label="Password" wire:model="password" autocomplete="current-password" right />
                    <x-checkbox label="Keep me signed in on this device" wire:model="remember" />
                    <x-slot:actions>
                        <x-button label="Sign in" type="submit" spinner="login" class="btn-secondary border-2 border-ink w-full text-base" />
                    </x-slot:actions>
                </x-form>
            </div>
        </div>
    </section>
</main>

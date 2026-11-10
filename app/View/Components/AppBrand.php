<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AppBrand extends Component
{
    public function render(): View|Closure|string
    {
        // The real school crest goes at public/crest.png; until then a placeholder shield shows.
        return <<<'HTML'
                <a href="/" wire:navigate {{ $attributes->class('flex items-center gap-3') }}>
                    <img src="{{ file_exists(public_path('crest.png')) ? '/crest.png' : '/crest-placeholder.svg' }}" alt="" class="h-10 w-auto shrink-0">
                    <span class="leading-tight">
                        <span class="block font-bold">Verona High School</span>
                        <span class="block text-xs text-muted">Competency early warning</span>
                    </span>
                </a>
            HTML;
    }
}

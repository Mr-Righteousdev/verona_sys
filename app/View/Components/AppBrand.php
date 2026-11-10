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
                <a href="/" wire:navigate {{ $attributes->class('flex items-center gap-3 border-2 border-current px-3 py-2.5 w-fit') }}>
                    <img src="{{ file_exists(public_path('crest.png')) ? '/crest.png' : '/crest-placeholder.svg' }}" alt="" class="h-11 w-auto shrink-0">
                    <span>
                        <span class="display block text-2xl">Verona<br>High School</span>
                        <span class="block text-xs mt-1 opacity-85">Competency early warning</span>
                    </span>
                </a>
            HTML;
    }
}

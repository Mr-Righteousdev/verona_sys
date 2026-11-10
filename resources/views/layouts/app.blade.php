<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="verona">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#ffffff">
    <title>{{ isset($title) ? $title.' · '.config('app.name') : config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans antialiased bg-base-200 text-ink">
@php($user = auth()->user())

    {{-- Mobile bar --}}
    <x-nav sticky class="lg:hidden bg-base-100 border-b border-rule">
        <x-slot:brand><x-app-brand /></x-slot:brand>
        <x-slot:actions>
            <label for="main-drawer" class="lg:hidden p-2 cursor-pointer" aria-label="Open menu">
                <x-icon name="o-bars-3" class="size-6" />
            </label>
        </x-slot:actions>
    </x-nav>

    <x-main full-width>
        <x-slot:sidebar drawer="main-drawer" class="bg-base-100 border-r border-rule">
            <div class="flex flex-col h-full">
                <x-app-brand class="mx-5 mt-5 mb-6" />

                <x-menu activate-by-route active-bg-color="bg-navy-soft text-navy font-bold" class="px-3 text-[0.9375rem] [&_li>a]:rounded-md [&_li>a]:px-3 [&_li>a]:py-2">
                    @if ($user->isStudent())
                        <x-menu-item title="My progress" icon="o-chart-bar" link="/me" />
                    @else
                        <x-menu-item title="Dashboard" icon="o-home" link="/dashboard" />
                        <x-menu-item title="Alerts" icon="o-flag" link="/alerts"
                            :badge="($open = \App\Models\Alert::open()->count()) ? $open : null" badge-classes="tag tag-low !h-auto !border-0" />
                        <x-menu-item title="Learners" icon="o-user-group" link="/learners" />
                        <x-menu-item title="Assessments & scores" icon="o-pencil-square" link="/assessments" />
                        <x-menu-item title="Attendance" icon="o-calendar-days" link="/attendance" />
                        <x-menu-item title="Reports" icon="o-document-chart-bar" link="/reports" />
                        @if ($user->isAdmin())
                            <li class="menu-title text-muted px-3 mt-5 text-xs uppercase tracking-wider">School setup</li>
                            <x-menu-item title="Users" icon="o-users" link="/admin/users" />
                            <x-menu-item title="Classes & subjects" icon="o-building-library" link="/admin/classes" />
                            <x-menu-item title="Competencies" icon="o-adjustments-horizontal" link="/admin/competencies" />
                        @endif
                    @endif
                </x-menu>

                @unless (app()->isProduction())
                    <p class="mt-auto mx-5 mb-3 text-xs text-muted">Demo data: learners and scores are fictional.</p>
                @endunless
                <div class="@production mt-auto @endproduction border-t border-rule px-5 py-4 text-sm flex items-start justify-between gap-2">
                    <div>
                        <p class="font-bold">{{ $user->name }}</p>
                        <p class="text-muted">{{ ucfirst($user->role) }}@if ($user->isStudent() && $user->student) · {{ $user->student->classroom->name }}@endif</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="p-1 text-muted hover:text-ink cursor-pointer" title="Sign out" aria-label="Sign out"><x-icon name="o-arrow-right-start-on-rectangle" class="size-5" /></button>
                    </form>
                </div>
            </div>
        </x-slot:sidebar>

        <x-slot:content class="!p-4 md:!px-8 md:!py-7 max-w-[1400px]">
            {{ $slot }}
        </x-slot:content>
    </x-main>

    <x-toast />
</body>
</html>

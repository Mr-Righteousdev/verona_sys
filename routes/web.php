<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::livewire('/login', 'pages::auth.login')->name('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect(auth()->user()->isStudent() ? '/me' : '/dashboard'));

    Route::post('/logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/login');
    })->name('logout');

    Route::middleware('role:admin,teacher')->group(function () {
        Route::livewire('/dashboard', 'pages::dashboard')->name('dashboard');
        Route::livewire('/alerts', 'pages::alerts.index')->name('alerts.index');
        Route::livewire('/alerts/{alert}', 'pages::alerts.show')->name('alerts.show');
        Route::livewire('/learners', 'pages::learners.index')->name('learners.index');
        Route::livewire('/learners/{student}', 'pages::learners.show')->name('learners.show');
        Route::livewire('/assessments', 'pages::assessments.index')->name('assessments.index');
        Route::livewire('/assessments/{assessment}/scores', 'pages::assessments.scores')->name('assessments.scores');
        Route::livewire('/attendance', 'pages::attendance')->name('attendance');
        Route::livewire('/reports', 'pages::reports.index')->name('reports');
        Route::livewire('/reports/learners/{student}', 'pages::reports.learner')->name('reports.learner');
        Route::get('/reports/export/scores', [\App\Http\Controllers\ExportController::class, 'scores'])->name('export.scores');
        Route::get('/reports/export/alerts', [\App\Http\Controllers\ExportController::class, 'alerts'])->name('export.alerts');
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::livewire('/users', 'pages::admin.users')->name('users');
        Route::livewire('/classes', 'pages::admin.classes')->name('classes');
        Route::livewire('/competencies', 'pages::admin.competencies')->name('competencies');
    });

    Route::middleware('role:student')->group(function () {
        Route::livewire('/me', 'pages::me')->name('me');
    });
});

// Local development only: sign in as any user for demos and screenshots.
if (app()->isLocal()) {
    Route::get('/dev/login/{user}', function (\App\Models\User $user) {
        Auth::login($user);

        return redirect(request('to', '/'));
    });
}

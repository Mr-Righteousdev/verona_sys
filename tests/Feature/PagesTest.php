<?php

use App\Models\Alert;
use App\Models\Assessment;
use App\Models\Student;
use App\Models\User;

beforeEach(fn () => $this->seed());

it('redirects guests to login', function () {
    $this->get('/dashboard')->assertRedirect('/login');
    $this->get('/login')->assertOk()->assertSee('Sign in');
});

it('renders every staff page for the head teacher', function () {
    $admin = User::where('role', 'admin')->first();
    $alert = Alert::open()->first();
    $pages = ['/dashboard', '/alerts', "/alerts/{$alert->id}", '/learners', "/learners/{$alert->student_id}",
        '/assessments', '/assessments/'.Assessment::first()->id.'/scores', '/attendance',
        '/admin/users', '/admin/classes', '/admin/competencies', '/reports', "/reports/learners/{$alert->student_id}"];
    foreach ($pages as $page) {
        $this->actingAs($admin)->get($page)->assertOk();
    }
});

it('keeps teachers out of admin and students out of staff pages', function () {
    $this->actingAs(User::where('role', 'teacher')->first())->get('/admin/users')->assertForbidden();
    $student = User::where('role', 'student')->first();
    $this->actingAs($student)->get('/alerts')->assertForbidden();
    $this->actingAs($student)->get('/')->assertRedirect('/me');
});

it('never shows alert language to a flagged learner', function () {
    $student = Student::whereHas('alerts', fn ($q) => $q->where('status', 'open'))->first();
    $this->actingAs($student->user)->get('/me')
        ->assertOk()
        ->assertSee('focus')
        ->assertDontSeeText(['Alert', 'alert', 'severity', 'benchmark of']);
});

it('exports scores and alerts as CSV for Excel', function () {
    $admin = User::where('role', 'admin')->first();
    $csv = $this->actingAs($admin)->get('/reports/export/alerts?class=1')->assertOk()->streamedContent();
    expect($csv)->toContain('Learner,Competency,Severity');
    $this->actingAs($admin)->get('/reports/export/scores')->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

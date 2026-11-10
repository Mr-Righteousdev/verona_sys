<?php

use App\Models\Alert;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Competency;
use App\Models\Subject;
use App\Models\User;
use Livewire\Livewire;

beforeEach(fn () => $this->seed());

it('saves a score sheet and raises alerts for a learner who keeps scoring 1', function () {
    $teacher = User::where('role', 'teacher')->first();
    $classroom = Classroom::first();
    $student = $classroom->students()->first();
    $competency = Competency::first();

    foreach (range(1, 3) as $i) {
        $a = Assessment::create([
            'classroom_id' => $classroom->id, 'subject_id' => Subject::first()->id, 'teacher_id' => $teacher->id,
            'title' => "Extra $i", 'type' => 'test', 'term' => 3, 'assessed_on' => now()->addDays($i - 3),
        ]);
        $a->competencies()->attach($competency);

        Livewire::actingAs($teacher)->test('pages::assessments.scores', ['assessment' => $a])
            ->set("levels.{$student->id}.{$competency->id}", '1.0')
            ->call('save')
            ->assertHasNoErrors();
    }

    expect((float) $a->scores()->where('student_id', $student->id)->value('level'))->toBe(1.0)
        ->and(Alert::open()->where('student_id', $student->id)->where('competency_id', $competency->id)->exists())->toBeTrue();
});

it('rejects a score outside 0.9-3.0', function () {
    $a = Assessment::with('competencies', 'classroom.students')->first();
    Livewire::actingAs(User::where('role', 'teacher')->first())->test('pages::assessments.scores', ['assessment' => $a])
        ->set("levels.{$a->classroom->students->first()->id}.{$a->competencies->first()->id}", '3.4')
        ->call('save')
        ->assertHasErrors();
});

it('records the register with absentees', function () {
    $classroom = Classroom::first();
    $absent = $classroom->students()->first();
    Livewire::actingAs(User::where('role', 'teacher')->first())->test('pages::attendance')
        ->set('classroomId', $classroom->id)
        ->set('date', '2026-09-01')
        ->set("present.{$absent->id}", false)
        ->call('save');

    expect(Attendance::where('student_id', $absent->id)->whereDate('date', '2026-09-01')->value('present'))->toBeFalse()
        ->and(Attendance::whereIn('student_id', $classroom->students()->pluck('id'))->whereDate('date', '2026-09-01')->where('present', true)->count())->toBe($classroom->students()->count() - 1);
});

it('records an intervention and moves the alert to in progress', function () {
    $alert = Alert::where('status', 'open')->first();
    Livewire::actingAs(User::where('role', 'teacher')->first())->test('pages::alerts.show', ['alert' => $alert])
        ->set('action', 'Met the learner after class.')
        ->call('recordIntervention')
        ->assertHasNoErrors();

    expect($alert->fresh()->status)->toBe('in_progress')
        ->and($alert->interventions()->first()->baseline_average)->toEqual($alert->recent_average);
});

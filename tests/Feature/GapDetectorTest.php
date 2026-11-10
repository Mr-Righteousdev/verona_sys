<?php

use App\Models\Alert;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Competency;
use App\Models\Intervention;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\GapDetector;

/** Add one dated assessment per level, `$startDaysAgo` days ago and a week apart. */
function scoreStudent(Student $student, Competency $competency, array $levels, int $startDaysAgo = 100): void
{
    $teacher = User::factory()->create();
    $subject = Subject::firstOrCreate(['name' => 'Maths']);
    foreach ($levels as $i => $level) {
        $a = Assessment::create([
            'classroom_id' => $student->classroom_id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id,
            'title' => "Test $i", 'type' => 'test', 'term' => 3, 'assessed_on' => now()->subDays($startDaysAgo - $i * 7),
        ]);
        Score::create(['assessment_id' => $a->id, 'student_id' => $student->id, 'competency_id' => $competency->id, 'level' => $level]);
    }
}

beforeEach(function () {
    $this->detector = new GapDetector;
    $this->competency = Competency::create(['name' => 'Critical Thinking', 'colour' => '#7b2d8e', 'benchmark' => 1.5, 'intervention' => 'Do X']);
    $classroom = Classroom::create(['name' => 'S1 East', 'level' => 'S1']);
    $this->student = Student::create(['classroom_id' => $classroom->id, 'name' => 'A', 'admission_no' => '1', 'gender' => 'F']);
});

it('maps decimal scores to CBC bands', function () {
    expect(Score::band(0.9))->toBe('Basic')
        ->and(Score::band(1.49))->toBe('Basic')
        ->and(Score::band(1.5))->toBe('Moderate')
        ->and(Score::band(2.5))->toBe('Outstanding');
});

it('flags nothing for a learner on track and labels the trend', function () {
    $r = $this->detector->analyse([2.4, 2.6, 2.8, 2.9], 1.5);
    expect($r['reasons'])->toBe([])->and($r['trend'])->toBe('improving');
});

it('flags a below-benchmark average', function () {
    $r = $this->detector->analyse([1.0, 1.2, 1.0], 1.5);
    expect($r['reasons'])->toHaveCount(1)
        ->and($this->detector->severity($r['reasons'], $r['average'], 1.5))->toBe('high');
});

it('needs four scores before a decline counts', function () {
    expect($this->detector->analyse([2.4, 1.8, 1.7], 1.5)['reasons'])->toBe([]);
});

it('warns early when a decline projects below benchmark', function () {
    // slope -0.4, average 2.0, projected 2.0 - 0.4 * 2.5 = 1.0
    $r = $this->detector->analyse([2.6, 2.2, 1.8, 1.4], 1.5);
    expect($r['average'])->toEqualWithDelta(2.0, 0.001)
        ->and($r['slope'])->toEqualWithDelta(-0.4, 0.001)
        ->and($r['projected'])->toEqualWithDelta(1.0, 0.001)
        ->and($r['trend'])->toBe('declining')
        ->and($r['reasons'])->toHaveCount(2);
});

it('creates, updates and auto-clears alerts', function () {
    scoreStudent($this->student, $this->competency, [1.0, 1.1]);
    $this->detector->run($this->student);
    expect(Alert::open()->count())->toBe(1);

    $this->detector->run($this->student);
    expect(Alert::count())->toBe(1); // updated, not duplicated

    scoreStudent($this->student, $this->competency, [2.5, 2.6, 2.7, 2.8, 2.9], 50);
    $this->detector->run($this->student);
    expect(Alert::first())->status->toBe('resolved')->outcome->toBe('cleared');
});

it('resolves as improved when the learner recovers after an intervention', function () {
    scoreStudent($this->student, $this->competency, [1.0, 1.1]);
    $this->detector->run($this->student);
    $alert = Alert::first();
    Intervention::create(['alert_id' => $alert->id, 'user_id' => User::factory()->create()->id, 'action' => 'Weekly check-in', 'started_on' => now()->subDays(60), 'baseline_average' => 1.05]);
    $alert->update(['status' => 'in_progress']);

    scoreStudent($this->student, $this->competency, [2.0, 2.2, 2.4, 2.5, 2.6], 50);
    $this->detector->run($this->student);
    expect($alert->fresh())->status->toBe('resolved')->outcome->toBe('improved');
});

it('escalates when an intervention shows no improvement', function () {
    scoreStudent($this->student, $this->competency, [1.0, 1.1]);
    $this->detector->run($this->student);
    $alert = Alert::first();
    Intervention::create(['alert_id' => $alert->id, 'user_id' => User::factory()->create()->id, 'action' => 'Weekly check-in', 'started_on' => now()->subDays(60), 'baseline_average' => 1.05]);
    $alert->update(['status' => 'in_progress']);

    scoreStudent($this->student, $this->competency, [1.0, 1.1, 1.0], 50);
    $this->detector->run($this->student);
    expect($alert->fresh())->escalated_at->not->toBeNull()->severity->toBe('high')->status->toBe('in_progress');
});

it('adds low attendance only to an existing flag', function () {
    foreach (range(1, 10) as $d) {
        Attendance::create(['student_id' => $this->student->id, 'date' => now()->subDays($d)->toDateString(), 'present' => $d > 5]);
    }
    scoreStudent($this->student, $this->competency, [2.5, 2.6]);
    $this->detector->run($this->student);
    expect(Alert::count())->toBe(0);

    scoreStudent($this->student, $this->competency, [1.0, 1.0, 1.0, 1.0, 1.0], 50);
    $this->detector->run($this->student);
    expect(Alert::open()->first()->reasons)->toContain('Attendance is 50% over the last 30 days.');
});

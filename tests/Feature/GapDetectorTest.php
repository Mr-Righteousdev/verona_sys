<?php

use App\Models\Alert;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Competency;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\GapDetector;

function scoreStudent(Student $student, Competency $competency, array $levels): void
{
    $teacher = User::factory()->create();
    $subject = Subject::firstOrCreate(['name' => 'Maths']);
    foreach ($levels as $i => $level) {
        $a = Assessment::create([
            'classroom_id' => $student->classroom_id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id,
            'title' => "Test $i", 'type' => 'test', 'term' => 3, 'assessed_on' => now()->subWeeks(count($levels) - $i),
        ]);
        Score::create(['assessment_id' => $a->id, 'student_id' => $student->id, 'competency_id' => $competency->id, 'level' => $level]);
    }
}

beforeEach(function () {
    $this->detector = new GapDetector;
    $this->competency = Competency::create(['name' => 'Critical Thinking', 'colour' => '#7b2d8e', 'benchmark' => 2.0, 'intervention' => 'Do X']);
    $classroom = Classroom::create(['name' => 'S1 East', 'level' => 'S1']);
    $this->student = Student::create(['classroom_id' => $classroom->id, 'name' => 'A', 'admission_no' => '1', 'gender' => 'F']);
});

it('flags nothing for a learner on track', function () {
    expect($this->detector->analyse([3, 2, 3, 3], 2.0)['reasons'])->toBe([]);
});

it('flags a below-benchmark average', function () {
    $r = $this->detector->analyse([1, 2, 1], 2.0);
    expect($r['reasons'])->toHaveCount(1)
        ->and($this->detector->severity($r['reasons'], $r['average']))->toBe('high');
});

it('ignores trend with fewer than four scores', function () {
    expect($this->detector->analyse([3, 2, 2], 2.0)['slope'])->toBeNull();
});

it('warns early when a decline projects below benchmark', function () {
    $r = $this->detector->analyse([3, 3, 2, 2], 2.0);
    expect($r['average'])->toBe(2.5)
        ->and($r['slope'])->toEqualWithDelta(-0.4, 0.001)
        ->and($r['projected'])->toBeLessThan(2.0)
        ->and($r['reasons'])->toHaveCount(2);
});

it('creates, updates and auto-resolves alerts', function () {
    scoreStudent($this->student, $this->competency, [1, 1]);
    $this->detector->run($this->student);
    expect(Alert::open()->count())->toBe(1);

    $this->detector->run($this->student);
    expect(Alert::count())->toBe(1); // updated, not duplicated

    scoreStudent($this->student, $this->competency, [3, 3, 3, 3, 3]);
    $this->detector->run($this->student);
    expect(Alert::open()->count())->toBe(0)
        ->and(Alert::first()->status)->toBe('resolved');
});

it('adds low attendance only to an existing flag', function () {
    foreach (range(1, 10) as $d) {
        Attendance::create(['student_id' => $this->student->id, 'date' => now()->subDays($d), 'present' => $d > 5]);
    }
    scoreStudent($this->student, $this->competency, [3, 3]);
    $this->detector->run($this->student);
    expect(Alert::count())->toBe(0);

    scoreStudent($this->student, $this->competency, [1, 1, 1, 1, 1]);
    $this->detector->run($this->student);
    expect(Alert::open()->first()->reasons)->toContain('Attendance is 50% over the last 30 days.');
});

<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Competency;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\GapDetector;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * SYNTHETIC DEMO DATA. Every learner, teacher, score and attendance record here is fictional.
 * It is for development and demonstration only; never present it as Verona High School data.
 */
class DatabaseSeeder extends Seeder
{
    private const COMPETENCIES = [
        'Communication & Collaboration' => [
            '#1a5fb4',
            'Expresses ideas clearly and works productively with others.',
            'Assign the learner a defined role in group work and ask them to report back to the class; give short feedback on clarity after each presentation.',
        ],
        'Critical Thinking & Problem-Solving' => [
            '#7b2d8e',
            'Analyses situations, evaluates evidence and reaches reasoned solutions.',
            'Use open-ended "why/how" questions in class and a short weekly problem task; review the learner\'s reasoning steps, not just the answer.',
        ],
        'Creativity & Imagination' => [
            '#b9470f',
            'Generates original ideas and applies them in new contexts.',
            'Give the learner a small project with a free choice of approach and praise novel attempts; pair with a creative peer for the next activity of integration.',
        ],
        'Citizenship' => [
            '#0f7a3d',
            'Understands rights and responsibilities and acts with integrity in the community.',
            'Involve the learner in a class responsibility (e.g. class prefect duty, community project) and discuss values in a one-to-one conversation.',
        ],
        'Digital Literacy' => [
            '#0b6f71',
            'Uses digital tools safely and effectively to find, create and share information.',
            'Schedule guided computer-lab time with a step-by-step task sheet; pair the learner with a confident peer.',
        ],
        'Learning to Learn' => [
            '#7f5a00',
            'Plans, monitors and reflects on own learning.',
            'Introduce a simple weekly learning log; meet the learner briefly to set one target and review it the following week.',
        ],
        'Self-Regulation' => [
            '#a3324a',
            'Manages emotions, time and behaviour to achieve goals.',
            'Agree a short personal timetable with the learner, check in on it weekly, and involve the class teacher or counsellor if behaviour concerns persist.',
        ],
    ];

    private const FIRST = ['Aisha', 'Brian', 'Catherine', 'Daniel', 'Esther', 'Francis', 'Grace', 'Hassan', 'Irene', 'Joseph', 'Kevin', 'Lydia', 'Mark', 'Nakato', 'Oscar', 'Patience', 'Ronald', 'Sarah', 'Timothy', 'Winnie', 'Allan', 'Brenda', 'Collins', 'Doreen', 'Emmanuel', 'Faith', 'Gideon', 'Hope', 'Isaac', 'Joan'];

    private const LAST = ['Nabukenya', 'Ssemwogerere', 'Nakimuli', 'Kato', 'Namubiru', 'Mugisha', 'Achieng', 'Okello', 'Nansubuga', 'Lubega', 'Atuhaire', 'Wasswa', 'Nalwoga', 'Kiggundu', 'Akello', 'Byaruhanga', 'Namatovu', 'Ssali', 'Tumusiime', 'Kyomuhendo'];

    public function run(): void
    {
        DB::transaction(function () {
            $this->seed();
            app(GapDetector::class)->runAll();
        });
    }

    private function seed(): void
    {
        mt_srand(2026);
        $password = Hash::make('password');

        User::create(['name' => 'Head Teacher', 'email' => 'admin@verona.test', 'password' => $password, 'role' => 'admin']);

        $teachers = collect(['Mr. Kasozi Peter', 'Ms. Nambi Ruth', 'Mr. Okot James', 'Ms. Akankwasa Joy'])
            ->map(fn ($name, $i) => User::create([
                'name' => $name,
                'email' => $i === 0 ? 'teacher@verona.test' : 'teacher'.($i + 1).'@verona.test',
                'password' => $password,
                'role' => 'teacher',
            ]));

        $competencies = collect(self::COMPETENCIES)->map(fn ($c, $name) => Competency::create([
            'name' => $name, 'colour' => $c[0], 'description' => $c[1], 'intervention' => $c[2], 'benchmark' => 2.0,
        ]))->values();

        $subjects = collect(['English', 'Mathematics', 'Biology', 'History & Political Education', 'Geography', 'ICT', 'Entrepreneurship'])
            ->map(fn ($name) => Subject::create(['name' => $name]));

        $classrooms = collect(['S1 East', 'S1 West', 'S2 East', 'S2 West'])
            ->map(fn ($name, $i) => Classroom::create([
                'name' => $name, 'level' => substr($name, 0, 2), 'class_teacher_id' => $teachers[$i]->id,
            ]));

        $schoolDays = collect(range(0, 41))
            ->map(fn ($d) => Carbon::parse('2026-09-28')->addDays($d))
            ->reject(fn (Carbon $d) => $d->isWeekend())
            ->filter(fn (Carbon $d) => $d->lte(now()))
            ->values();

        $n = 0;
        foreach ($classrooms as $classroom) {
            $students = collect(range(1, 18))->map(function () use ($classroom, &$n, $password) {
                $n++;
                $name = self::FIRST[$n % count(self::FIRST)].' '.self::LAST[($n * 7) % count(self::LAST)];
                $user = User::create([
                    'name' => $name,
                    'email' => $n === 1 ? 'student@verona.test' : 'student'.$n.'@verona.test',
                    'password' => $password,
                    'role' => 'student',
                ]);

                return Student::create([
                    'user_id' => $user->id,
                    'classroom_id' => $classroom->id,
                    'name' => $name,
                    'admission_no' => sprintf('VHS/%s/%03d', $classroom->level, $n),
                    'gender' => $n % 2 ? 'F' : 'M',
                ]);
            });

            // Each learner gets a baseline and trend per competency; ~1 in 10 has a declining competency.
            $profiles = $students->mapWithKeys(fn (Student $s) => [$s->id => $competencies->mapWithKeys(fn ($c) => [
                $c->id => [
                    'base' => 2.1 + mt_rand(0, 90) / 100,
                    'trend' => mt_rand(1, 10) === 1 ? -0.2 - mt_rand(0, 15) / 100 : mt_rand(-4, 6) / 100,
                ],
            ])]);
            $lowAttendance = $students->random(3)->pluck('id')->all();

            foreach ($schoolDays as $day) {
                foreach ($students as $s) {
                    $rate = in_array($s->id, $lowAttendance) ? 60 : 94;
                    Attendance::create(['student_id' => $s->id, 'date' => $day->toDateString(), 'present' => mt_rand(1, 100) <= $rate]);
                }
            }

            foreach (range(0, 6) as $i) {
                $assessment = Assessment::create([
                    'classroom_id' => $classroom->id,
                    'subject_id' => $subjects[$i]->id,
                    'teacher_id' => $teachers[($i + $classroom->id) % 4]->id,
                    'title' => ['Activity of integration', 'Topic test', 'Group project', 'Class observation'][$i % 4].' '.($i + 1),
                    'type' => Assessment::TYPES[$i % 4],
                    'term' => 3,
                    'assessed_on' => Carbon::parse('2026-09-23')->addWeeks($i),
                ]);
                $tagged = $competencies->random(3);
                $assessment->competencies()->attach($tagged->pluck('id'));

                foreach ($students as $s) {
                    foreach ($tagged as $c) {
                        $p = $profiles[$s->id][$c->id];
                        $level = $p['base'] + $p['trend'] * $i + mt_rand(-35, 35) / 100;
                        Score::create([
                            'assessment_id' => $assessment->id,
                            'student_id' => $s->id,
                            'competency_id' => $c->id,
                            'level' => max(1, min(3, (int) round($level))),
                        ]);
                    }
                }
            }
        }
    }
}

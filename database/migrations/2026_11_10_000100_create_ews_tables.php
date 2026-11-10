<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g. "S1 East"
            $table->string('level');          // S1..S4
            $table->foreignId('class_teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('admission_no')->unique();
            $table->string('gender', 1); // M | F
            $table->timestamps();
        });

        Schema::create('competencies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->string('colour', 7); // fixed paint colour used everywhere for this competency
            $table->decimal('benchmark', 3, 1)->default(1.5); // learners averaging below this are flagged (0.9-3.0 scale)
            $table->text('intervention');                       // recommendation shown on alerts
            $table->timestamps();
        });

        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('type'); // assignment | test | project | observation
            $table->unsignedTinyInteger('term');
            $table->date('assessed_on');
            $table->timestamps();
        });

        Schema::create('assessment_competency', function (Blueprint $table) {
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competency_id')->constrained()->cascadeOnDelete();
            $table->primary(['assessment_id', 'competency_id']);
        });

        Schema::create('scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competency_id')->constrained()->cascadeOnDelete();
            $table->decimal('level', 2, 1); // 0.9-3.0: Basic < 1.5 <= Moderate < 2.5 <= Outstanding
            $table->timestamps();
            $table->unique(['assessment_id', 'student_id', 'competency_id']);
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->boolean('present');
            $table->timestamps();
            $table->unique(['student_id', 'date']);
        });

        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competency_id')->constrained()->cascadeOnDelete();
            $table->string('severity');  // low | medium | high
            $table->json('reasons');
            $table->decimal('recent_average', 3, 2);
            $table->decimal('projected_level', 3, 2)->nullable();
            $table->text('recommendation');
            $table->string('status')->default('open'); // open | in_progress | resolved
            $table->string('outcome')->nullable();     // improved | cleared | closed (set when resolved)
            $table->timestamp('escalated_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'competency_id', 'status']);
        });

        Schema::create('interventions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('action');                       // what the teacher did
            $table->date('started_on');
            $table->decimal('baseline_average', 3, 2);    // recent average when the intervention began
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['interventions', 'alerts', 'attendances', 'scores', 'assessment_competency', 'assessments', 'competencies', 'students', 'subjects', 'classrooms'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
